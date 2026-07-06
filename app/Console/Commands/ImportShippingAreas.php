<?php

namespace App\Console\Commands;

use App\Actions\ImportShippingAreas as ImportShippingAreasAction;
use App\Models\ShippingSetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;

#[Signature('app:import-shipping-areas {--delay-ms=250 : Milliseconds to wait between sub-district requests}')]
#[Description('Mirror RajaOngkir/Komerce\'s province/city/district/sub-district area database locally, so checkout destination search no longer needs to call the provider live. Safe to interrupt and re-run.')]
class ImportShippingAreas extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImportShippingAreasAction $action): int
    {
        $settings = ShippingSetting::query()->where('is_active', true)->first();

        if ($settings === null) {
            $this->error('No active shipping provider is configured. Set one up in Settings → Pengiriman first.');

            return self::FAILURE;
        }

        $this->info('Importing shipping areas — this can take a while (thousands of requests). Safe to Ctrl+C and re-run later.');

        try {
            $stats = $action($settings, (int) $this->option('delay-ms'), function (array $stats): void {
                $this->output->write(
                    "\rDistricts imported: {$stats['districts_imported']} | skipped (already local): {$stats['districts_skipped']} | areas saved: {$stats['areas_upserted']}"
                );
            });
        } catch (\Throwable $exception) {
            $this->newLine(2);

            if ($exception instanceof RequestException && $exception->response->status() === 429) {
                $this->error('RajaOngkir daily quota exceeded (HTTP 429). Progress so far is saved — run this command again once your quota resets (or after upgrading your Komerce plan).');
            } else {
                $this->error("Request to RajaOngkir failed: {$exception->getMessage()}. Progress so far is saved — run this command again to retry.");
            }

            return self::FAILURE;
        }

        $this->newLine(2);
        $this->info("Done. Provinces: {$stats['provinces']}, districts imported: {$stats['districts_imported']}, districts skipped: {$stats['districts_skipped']}, areas saved: {$stats['areas_upserted']}.");

        return self::SUCCESS;
    }
}
