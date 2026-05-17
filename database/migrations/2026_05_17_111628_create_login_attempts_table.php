<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['username', 'ip_address']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
