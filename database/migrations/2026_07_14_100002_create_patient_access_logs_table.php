<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_access_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('clinician_id');
            $table->integer('child_id');
            $table->unsignedBigInteger('temporary_access_token_id');
            $table->timestamp('accessed_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->string('status', 20)->default('active'); // active | ended | expired
            $table->string('ended_by', 20)->nullable(); // guardian | clinician | system
            $table->timestamps();

            $table->index(['clinician_id', 'accessed_at']);
            $table->index(['child_id', 'status']);
            $table->foreign('clinician_id')->references('user_id')->on('user')->onDelete('cascade');
            $table->foreign('child_id')->references('child_id')->on('child_profile')->onDelete('cascade');
            $table->foreign('temporary_access_token_id')->references('id')->on('temporary_access_tokens')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_access_logs');
    }
};
