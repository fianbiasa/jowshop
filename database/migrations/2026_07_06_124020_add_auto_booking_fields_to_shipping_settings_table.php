<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table) {
            $table->string('origin_contact_name')->nullable()->after('origin_label');
            $table->string('origin_contact_phone')->nullable()->after('origin_contact_name');
            $table->text('origin_address')->nullable()->after('origin_contact_phone');
            $table->string('origin_postal_code')->nullable()->after('origin_address');
            $table->boolean('auto_book_shipping')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_settings', function (Blueprint $table) {
            $table->dropColumn([
                'origin_contact_name',
                'origin_contact_phone',
                'origin_address',
                'origin_postal_code',
                'auto_book_shipping',
            ]);
        });
    }
};
