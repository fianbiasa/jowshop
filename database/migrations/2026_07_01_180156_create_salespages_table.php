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
        Schema::create('salespages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('content');
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->boolean('generated_by_ai')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salespages');
    }
};
