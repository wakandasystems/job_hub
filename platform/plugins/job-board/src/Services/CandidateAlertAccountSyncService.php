<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\CandidateAlert;
use Illuminate\Http\UploadedFile;
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
                    ->orWhere('whatsapp_number', 'like', '%' . $suffix . '%');
            })
            ->get()
            ->filter(fn (Account $account) => $this->phonesMatch($phone, $account->phone) || $this->phonesMatch($phone, $account->whatsapp_number))
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
}
