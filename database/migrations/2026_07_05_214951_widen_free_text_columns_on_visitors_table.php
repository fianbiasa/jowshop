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
        // These are free-text values captured from real (untrusted, unbounded)
        // request data — referrer/landing_url/user_agent from the browser,
        // utm_* from the ad platform's link tagging. Meta's newer fbclid
        // format alone regularly pushes a full landing URL past 191 chars,
        // which under strict SQL mode throws instead of truncating, turning
        // a routine ad-click visit into a 500. None of these columns are
        // indexed or queried by exact match, so widening them to text is safe.
        Schema::table('visitors', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->change();
            $table->text('referrer')->nullable()->change();
            $table->text('landing_url')->nullable()->change();
            $table->text('utm_source')->nullable()->change();
            $table->text('utm_medium')->nullable()->change();
            $table->text('utm_campaign')->nullable()->change();
            $table->text('utm_term')->nullable()->change();
            $table->text('utm_content')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->change();
            $table->string('referrer')->nullable()->change();
            $table->string('landing_url')->nullable()->change();
            $table->string('utm_source')->nullable()->change();
            $table->string('utm_medium')->nullable()->change();
            $table->string('utm_campaign')->nullable()->change();
            $table->string('utm_term')->nullable()->change();
            $table->string('utm_content')->nullable()->change();
        });
    }
};
