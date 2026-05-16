<?php

namespace Botble\JobBoard\Commands;

use Botble\JobBoard\Facades\Currency as CurrencyFacade;
use Botble\JobBoard\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand('cms:currencies:update-rates', 'Update job board currency exchange rates')]
class UpdateCurrencyRatesCommand extends Command
{
    protected $signature = 'cms:currencies:update-rates
        {--base= : Base currency code. Defaults to the configured default currency.}
        {--endpoint=https://open.er-api.com/v6/latest : Exchange-rate API endpoint.}';

    public function handle(): int
    {
        $baseCurrency = strtoupper((string) ($this->option('base') ?: CurrencyFacade::getDefaultCurrency()?->title ?: 'ZMW'));
        $endpoint = rtrim((string) $this->option('endpoint'), '/');

        if (! preg_match('/^[A-Z]{3}$/', $baseCurrency)) {
            $this->components->error(sprintf('Invalid base currency code: %s', $baseCurrency));

            return self::FAILURE;
        }

        try {
            $response = Http::timeout(20)
                ->retry(2, 1000)
                ->acceptJson()
                ->get(sprintf('%s/%s', $endpoint, $baseCurrency));
        } catch (Throwable $exception) {
            $this->components->error(sprintf('Could not fetch exchange rates: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->components->error(sprintf('Exchange-rate provider returned HTTP %s.', $response->status()));

            return self::FAILURE;
        }

        $payload = $response->json();
        $rates = (array) data_get($payload, 'rates', []);

        if (data_get($payload, 'result') !== 'success' || strtoupper((string) data_get($payload, 'base_code')) !== $baseCurrency || empty($rates)) {
            $this->components->error('Exchange-rate provider returned an invalid payload.');

            return self::FAILURE;
        }

        $updated = 0;
        $skipped = [];

        Currency::query()
            ->orderBy('title')
            ->get()
            ->each(function (Currency $currency) use ($rates, $baseCurrency, &$updated, &$skipped): void {
                $code = strtoupper((string) $currency->title);
                $rate = $code === $baseCurrency ? 1 : data_get($rates, $code);

                if (! is_numeric($rate) || (float) $rate <= 0) {
                    $skipped[] = $code;

                    return;
                }

                $rate = round((float) $rate, 8);

                if ((float) $currency->exchange_rate === $rate) {
                    return;
                }

                $currency->exchange_rate = $rate;
                $currency->save();

                $updated++;
            });

        setting()->set([
            'job_board_currency_rates_last_updated_at' => now()->toDateTimeString(),
            'job_board_currency_rates_source_updated_at' => (string) data_get($payload, 'time_last_update_utc'),
            'job_board_currency_rates_base' => $baseCurrency,
            'job_board_currency_rates_provider' => $endpoint,
        ])->save();

        $this->components->info(sprintf(
            'Updated %s currencies using %s as base.',
            number_format($updated),
            $baseCurrency
        ));

        if ($skipped) {
            $this->components->warn(sprintf('Skipped currencies without provider rates: %s', implode(', ', $skipped)));
        }

        if ($sourceUpdatedAt = data_get($payload, 'time_last_update_utc')) {
            $this->line(sprintf('Provider timestamp: %s', $sourceUpdatedAt));
        }

        return self::SUCCESS;
    }
}
