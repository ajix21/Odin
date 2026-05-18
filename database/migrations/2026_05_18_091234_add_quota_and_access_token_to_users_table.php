<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Daily search quota — null = unlimited
            $table->unsignedSmallInteger('daily_search_limit')->nullable()->after('api_token');
            // SHA-256 hash of personal access token for API
            $table->string('access_token_hash', 64)->nullable()->unique()->after('daily_search_limit');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_search_limit', 'access_token_hash']);
        });
    }
};
