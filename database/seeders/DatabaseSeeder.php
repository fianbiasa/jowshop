<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Product::factory()
            ->for($user, 'creator')
            ->digital()
            ->published()
            ->count(3)
            ->create();

        Product::factory()
            ->for($user, 'creator')
            ->physical()
            ->published()
            ->count(3)
            ->create();

        $this->call(FunnelSeeder::class);
    }
}
