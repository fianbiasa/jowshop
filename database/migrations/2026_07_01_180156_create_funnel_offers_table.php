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
        Schema::create('funnel_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_offer_id')->nullable()->constrained('funnel_offers')->cascadeOnDelete();
            $table->string('trigger_condition')->default('initial');
            $table->string('stage');
            $table->unsignedInteger('sequence')->default(0);
            $table->string('headline');
            $table->text('description')->nullable();
            $table->string('media_url')->nullable();
            $table->decimal('price_override', 12, 2)->nullable();
            $table->string('discount_type')->default('none');
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funnel_offers');
    }
};
