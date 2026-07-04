<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\FunnelStatus;
use App\Enums\OfferStage;
use App\Enums\OfferTriggerCondition;
use App\Models\Funnel;
use App\Models\FunnelOffer;
use App\Models\Product;
use App\Models\Salespage;
use App\Models\User;
use Illuminate\Database\Seeder;

class FunnelSeeder extends Seeder
{
    /**
     * Seed the "Kopi" example funnel described in docs/PRD.md §5:
     * Salespage -> Checkout (bump Gula -> declined -> bump Kental Manis)
     * -> Purchase -> Upsell Kopi 1kg -> declined -> Downsell Kopi 250gr.
     */
    public function run(): void
    {
        $user = User::query()->firstOrFail();

        $kopi = Product::factory()->for($user, 'creator')->physical()->published()->create([
            'name' => 'Kopi Robusta 200gr',
            'slug' => 'kopi-robusta-200gr',
            'price' => 45000,
        ]);

        $gula = Product::factory()->for($user, 'creator')->physical()->published()->create([
            'name' => 'Gula Aren 100gr',
            'slug' => 'gula-aren-100gr',
            'price' => 5000,
        ]);

        $kentalManis = Product::factory()->for($user, 'creator')->physical()->published()->create([
            'name' => 'Kental Manis Sachet',
            'slug' => 'kental-manis-sachet',
            'price' => 7000,
        ]);

        $kopi1kg = Product::factory()->for($user, 'creator')->physical()->published()->create([
            'name' => 'Kopi Robusta 1kg',
            'slug' => 'kopi-robusta-1kg',
            'price' => 180000,
        ]);

        $kopi250gr = Product::factory()->for($user, 'creator')->physical()->published()->create([
            'name' => 'Kopi Robusta 250gr',
            'slug' => 'kopi-robusta-250gr',
            'price' => 55000,
        ]);

        $funnel = Funnel::factory()->for($user, 'creator')->published()->create([
            'product_id' => $kopi->id,
            'name' => 'Funnel Kopi Robusta',
            'slug' => 'kopi-robusta',
        ]);

        Salespage::factory()->published()->create([
            'funnel_id' => $funnel->id,
            'title' => 'Kopi Robusta 200gr - Nikmat & Berkualitas',
            'content' => [
                ['type' => 'headline', 'data' => ['text' => 'Kopi Robusta Pilihan, Diseduh dari Biji Terbaik']],
                ['type' => 'benefit_list', 'data' => ['items' => [
                    'Biji kopi pilihan grade A',
                    'Disangrai fresh setiap minggu',
                    'Aroma kuat, rasa tidak asam',
                ]]],
                ['type' => 'cta', 'data' => ['label' => 'Pesan Sekarang']],
            ],
        ]);

        // Order bump chain di checkout: Gula -> (declined) -> Kental Manis.
        $bumpGula = FunnelOffer::factory()->for($funnel)->bump()->create([
            'product_id' => $gula->id,
            'headline' => 'Tambah Gula Aren?',
            'description' => 'Lengkapi kopimu dengan gula aren asli.',
            'price_override' => 5000,
            'sequence' => 1,
        ]);

        FunnelOffer::factory()
            ->childOf($bumpGula, OfferTriggerCondition::Declined)
            ->create([
                'product_id' => $kentalManis->id,
                'headline' => 'Tambah Kental Manis?',
                'description' => 'Atau coba dengan kental manis, lebih creamy.',
                'price_override' => 7000,
                'sequence' => 1,
            ]);

        // Post-purchase: Upsell Kopi 1kg -> (declined) -> Downsell Kopi 250gr.
        $upsell1kg = FunnelOffer::factory()->for($funnel)->upsell()->create([
            'product_id' => $kopi1kg->id,
            'headline' => 'Upgrade ke Kopi Robusta 1kg?',
            'description' => 'Hemat lebih banyak dengan ukuran 1kg.',
            'price_override' => 180000,
            'discount_type' => DiscountType::None,
            'sequence' => 1,
        ]);

        FunnelOffer::factory()
            ->childOf($upsell1kg, OfferTriggerCondition::Declined)
            ->create([
                'stage' => OfferStage::Downsell,
                'product_id' => $kopi250gr->id,
                'headline' => 'Kopi Robusta 250gr saja?',
                'description' => 'Ukuran lebih kecil, tetap nikmat.',
                'price_override' => 55000,
                'sequence' => 1,
            ]);
    }
}
