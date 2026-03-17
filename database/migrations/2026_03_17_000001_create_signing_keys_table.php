<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signing_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('private_key'); // Encrypted by model
            $table->text('public_key');
            $table->string('algorithm', 50)->default('RS256');
            $table->enum('status', ['active', 'retired', 'revoked'])->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('activated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_keys');
    }
};
