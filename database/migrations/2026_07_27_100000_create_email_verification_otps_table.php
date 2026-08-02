<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verification_otps', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->cascadeOnDelete();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_otps');
    }
};
