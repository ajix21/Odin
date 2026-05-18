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
        \DB::statement("ALTER TABLE search_logs MODIFY COLUMN tool ENUM(
            'getcontact','leakosint','toutatis',
            'email-osint','ip-geo','multicheck',
            'phone-info','whois'
        ) NOT NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE search_logs MODIFY COLUMN tool ENUM('getcontact','leakosint') NOT NULL");
    }
};
