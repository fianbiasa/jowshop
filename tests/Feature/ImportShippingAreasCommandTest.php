<?php

namespace Tests\Feature;

use App\Models\ShippingArea;
use App\Models\ShippingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportShippingAreasCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAreaHierarchy(): void
    {
        Http::fake([
            '*/destination/province' => Http::response(['data' => [
                ['id' => 10, 'name' => 'DKI JAKARTA'],
            ]]),
            '*/destination/city/10' => Http::response(['data' => [
                ['id' => 137, 'name' => 'JAKARTA PUSAT', 'zip_code' => '10000'],
            ]]),
            '*/destination/district/137' => Http::response(['data' => [
                ['id' => 1344, 'name' => 'MENTENG', 'zip_code' => '10300'],
            ]]),
            '*/destination/sub-district/1344' => Http::response(['data' => [
                ['id' => 17617, 'name' => 'CIKINI', 'zip_code' => '10330'],
                ['id' => 17620, 'name' => 'MENTENG', 'zip_code' => '10310'],
            ]]),
        ]);
    }

    public function test_command_fails_without_an_active_shipping_setting(): void
    {
        $this->artisan('app:import-shipping-areas')->assertExitCode(1);
    }

    public function test_command_imports_the_full_area_hierarchy(): void
    {
        ShippingSetting::factory()->create();
        $this->fakeAreaHierarchy();

        $this->artisan('app:import-shipping-areas', ['--delay-ms' => 0])->assertSuccessful();

        $this->assertSame(2, ShippingArea::query()->count());

        $menteng = ShippingArea::query()->findOrFail(17620);
        $this->assertSame('DKI JAKARTA', $menteng->province_name);
        $this->assertSame('JAKARTA PUSAT', $menteng->city_name);
        $this->assertSame('MENTENG', $menteng->district_name);
        $this->assertSame('MENTENG', $menteng->subdistrict_name);
        $this->assertSame('10310', $menteng->zip_code);
        $this->assertSame('MENTENG, MENTENG, JAKARTA PUSAT, DKI JAKARTA, 10310', $menteng->label);
    }

    public function test_command_survives_a_city_whose_district_list_is_null(): void
    {
        ShippingSetting::factory()->create();
        Http::fake([
            '*/destination/province' => Http::response(['data' => [
                ['id' => 10, 'name' => 'DKI JAKARTA'],
            ]]),
            '*/destination/city/10' => Http::response(['data' => [
                ['id' => 137, 'name' => 'JAKARTA PUSAT', 'zip_code' => '10000'],
                ['id' => 138, 'name' => 'JAKARTA SELATAN', 'zip_code' => '12000'],
            ]]),
            // Some RajaOngkir responses return a literal `"data": null`
            // instead of an empty list — this must not crash the import.
            '*/destination/district/138' => Http::response(['data' => null]),
            '*/destination/district/137' => Http::response(['data' => [
                ['id' => 1344, 'name' => 'MENTENG', 'zip_code' => '10300'],
            ]]),
            '*/destination/sub-district/1344' => Http::response(['data' => [
                ['id' => 17620, 'name' => 'MENTENG', 'zip_code' => '10310'],
            ]]),
        ]);

        $this->artisan('app:import-shipping-areas', ['--delay-ms' => 0])->assertSuccessful();

        $this->assertSame(1, ShippingArea::query()->count());
    }

    public function test_command_reports_a_friendly_error_when_the_daily_quota_is_exceeded(): void
    {
        ShippingSetting::factory()->create();
        Http::fake([
            '*/destination/province' => Http::response(['meta' => ['message' => 'Daily limit exceeded', 'code' => 429, 'status' => 'error'], 'data' => null], 429),
        ]);

        $this->artisan('app:import-shipping-areas', ['--delay-ms' => 0])->assertFailed();

        $this->assertSame(0, ShippingArea::query()->count());
    }

    public function test_command_stops_immediately_on_quota_exceeded_instead_of_hammering_remaining_requests(): void
    {
        ShippingSetting::factory()->create();
        Http::fake([
            '*/destination/province' => Http::response(['data' => [
                ['id' => 10, 'name' => 'DKI JAKARTA'],
                ['id' => 11, 'name' => 'JAWA BARAT'],
            ]]),
            '*/destination/city/10' => Http::response(['data' => [
                ['id' => 137, 'name' => 'JAKARTA PUSAT', 'zip_code' => '10000'],
            ]]),
            '*/destination/district/137' => Http::response(['meta' => ['message' => 'Daily limit exceeded', 'code' => 429, 'status' => 'error'], 'data' => null], 429),
            '*/destination/city/11' => Http::response(['data' => [
                ['id' => 200, 'name' => 'BANDUNG', 'zip_code' => '40000'],
            ]]),
        ]);

        $this->artisan('app:import-shipping-areas', ['--delay-ms' => 0])->assertFailed();

        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), '/destination/city/11'));
    }

    public function test_command_skips_districts_already_imported_on_a_second_run(): void
    {
        ShippingSetting::factory()->create();
        $this->fakeAreaHierarchy();

        $this->artisan('app:import-shipping-areas', ['--delay-ms' => 0])->assertSuccessful();

        $this->fakeAreaHierarchy();

        $this->artisan('app:import-shipping-areas', ['--delay-ms' => 0])->assertSuccessful();

        Http::assertNotSent(fn ($request) => str_contains((string) $request->url(), '/destination/sub-district/1344'));
        $this->assertSame(2, ShippingArea::query()->count());
    }
}
