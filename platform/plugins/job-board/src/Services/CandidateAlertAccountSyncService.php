<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\CandidateAlert;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CandidateAlertAccountSyncService
{
    public function resolveAccount(?string $email, ?string $phone): ?Account
    {
        $email = $this->normalizeEmail($email);
        $phone = $this->normalizePhone($phone);

        if ($email !== null) {
            $account = Account::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->get()
                ->sortByDesc(fn (Account $account) => (int) $account->isJobSeeker())
                ->first();

            if ($account instanceof Account) {
                return $account;
            }
        }

        if ($phone === null) {
            return null;
        }

        $suffix = substr($phone, -7);

        return Account::query()
            ->where(function ($query) use ($suffix): void {
                $query->where('phone', 'like', '%' . $suffix . '%')
                    ->orWhere('whatsapp_number', 'like', '%' . $suffix . '%')
                    ->orWhere('call_numbers', 'like', '%' . $suffix . '%')
                    ->orWhere('whatsapp_numbers', 'like', '%' . $suffix . '%');
            })
            ->get()
            ->filter(fn (Account $account) => $this->accountHasMatchingPhone($account, $phone))
            ->sortByDesc(fn (Account $account) => (int) $account->isJobSeeker())
            ->first();
    }

    public function syncUploadedCvToAccount(Account $account, UploadedFile $file): string
    {
        $path = $file->store('resumes', 'public');

        $account->resume = $path;
        $account->save();

        return $path;
    }

    public function syncStoredAlertCvToAccount(Account $account, ?string $storedPath): ?string
    {
        $storedPath = trim((string) $storedPath);

        if ($storedPath === '' || ! Storage::disk('local')->exists($storedPath)) {
            return null;
        }

        $extension = pathinfo($storedPath, PATHINFO_EXTENSION);
        $filename = Str::uuid()->toString() . ($extension !== '' ? '.' . strtolower($extension) : '');
        $targetPath = 'resumes/' . $filename;
        $stream = Storage::disk('local')->readStream($storedPath);

        if ($stream === false) {
            return null;
        }

        try {
            Storage::disk('public')->put($targetPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $account->resume = $targetPath;
        $account->save();

        return $targetPath;
    }

    public function sendRegistrationPromptEmail(CandidateAlert $alert, ?string &$error = null): bool
    {
        $email = $this->normalizeEmail($alert->candidate_email);

        if ($email === null) {
            $error = 'This VIP does not have a valid email address on file.';

            return false;
        }

        $registerUrl = route('public.account.register');
        $name = trim((string) $alert->candidate_name) ?: 'there';

        $body = "Hi {$name},\n\n"
            . "We have activated your Wakanda Jobs VIP alert and you will continue receiving matching jobs on WhatsApp.\n\n"
            . "We also noticed you do not yet have a Wakanda Jobs account.\n\n"
            . "Creating your free account gives you better chances because you can:\n"
            . "- keep one CV/profile on file\n"
            . "- apply faster to jobs\n"
            . "- update your skills and experience for better matching\n"
            . "- access more candidate features and benefits\n\n"
            . "Create your free account here:\n{$registerUrl}\n\n"
            . "Wakanda Jobs — wakandajobs.com";

        try {
            Mail::raw($body, function ($message) use ($alert, $email): void {
                $message->to($email, $alert->candidate_name)
                    ->subject('Your Wakanda Jobs VIP Alert Is Active — Create Your Free Account');
            });
            return true;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            report($exception);
        }

        return false;
    }

    /**
     * Auto-create a Wakanda Jobs candidate account for a VIP alert that has no
     * matching account, using the same fields/flow as the Auto Apply "Setup for
     * Candidate" account creation. Requires a valid email since that becomes the
     * login identifier; returns [null, null] if email is missing/invalid or an
     * account with that email already exists.
     *
     * @return array{0: ?Account, 1: ?string}
     */
    public function createAccountForAlert(string $candidateName, ?string $email, ?string $phone): array
    {
        $email = $this->normalizeEmail($email);

        if ($email === null) {
            return [null, null];
        }

        if (Account::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return [null, null];
        }

        $phone = trim((string) $phone);
        [$firstName, $lastName] = $this->splitName($candidateName);
        $plainPassword = Str::random(10);

        $account = Account::query()->forceCreate([
            'type' => AccountTypeEnum::JOB_SEEKER,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'call_numbers' => $phone !== '' ? [$phone] : [],
            'whatsapp_number' => $phone,
            'whatsapp_numbers' => $phone !== '' ? [$phone] : [],
            'password' => Hash::make($plainPassword),
            'is_public_profile' => false,
            'confirmed_at' => now(),
            'profile_updated_at' => now(),
        ]);

        return [$account, $plainPassword];
    }

    public function sendAccountCreatedEmail(Account $account, CandidateAlert $alert, string $plainPassword, ?string &$error = null): bool
    {
        $email = $this->normalizeEmail($account->email);

        if ($email === null) {
            $error = 'This candidate does not have a valid email address on file.';

            return false;
        }

        $duration = CandidateAlert::$durations[$alert->duration_days] ?? ['label' => $alert->duration_days . ' Days'];
        $loginUrl = route('public.account.login');
        $dashboardUrl = route('public.account.dashboard');

        $body = "Hi {$account->first_name},\n\n"
            . "We have created your Wakanda Jobs account and activated your VIP job alert.\n\n"
            . "Subscription: {$duration['label']} — K{$alert->price}\n"
            . "Expires: " . ($alert->expires_at?->format('d M Y') ?? 'N/A') . "\n\n"
            . "Your Wakanda Jobs account details:\n"
            . "Email: {$account->email}\n"
            . "Temporary password: {$plainPassword}\n\n"
            . "Login here:\n{$loginUrl}\n\n"
            . "Your dashboard:\n{$dashboardUrl}\n\n"
            . "Wakanda Jobs — wakandajobs.com";

        try {
            Mail::raw($body, function ($message) use ($account, $email): void {
                $message->to($email, trim((string) $account->name) ?: $account->first_name)
                    ->subject('Your Wakanda Jobs Account Is Ready — VIP Alert Active');
            });

            return true;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            report($exception);
        }

        return false;
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }

    public function syncAlertWithAccount(CandidateAlert $alert, ?Account $account, bool $preferAccountCv = true): void
    {
        if (! $account instanceof Account) {
            return;
        }

        $updates = [
            'account_id' => $account->getKey(),
            'candidate_name' => $alert->candidate_name ?: trim((string) $account->name),
            'candidate_email' => $alert->candidate_email ?: $account->email,
            'candidate_phone' => $alert->candidate_phone ?: ($account->whatsapp_number ?: $account->phone),
        ];

        if ($preferAccountCv && $account->resume) {
            $updates['cv_path'] = null;
        }

        $alert->fill($updates);
        $alert->save();
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $digits = ltrim((string) $digits, '0');

        return strlen($digits) >= 7 ? $digits : null;
    }

    private function phonesMatch(string $left, ?string $right): bool
    {
        $right = $this->normalizePhone($right);

        if ($right === null) {
            return false;
        }

        return $left === $right || str_ends_with($left, $right) || str_ends_with($right, $left);
    }

    private function accountHasMatchingPhone(Account $account, string $phone): bool
    {
        foreach (array_merge(
            $account->call_numbers ?? [],
            $account->whatsapp_numbers ?? [],
            [$account->phone, $account->whatsapp_number]
        ) as $candidatePhone) {
            if ($this->phonesMatch($phone, is_string($candidatePhone) ? $candidatePhone : null)) {
                return true;
            }
        }

        return false;
    }
}
