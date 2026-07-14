<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer('child_id');
            $table->string('token_code', 6);
            $table->string('qr_payload');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();

            $table->index('token_code');
            $table->index(['child_id', 'is_used', 'expires_at']);
            $table->foreign('child_id')->references('child_id')->on('child_profile')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_access_tokens');
    }
};
