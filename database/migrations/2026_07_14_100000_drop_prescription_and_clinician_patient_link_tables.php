<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('prescription');
        Schema::dropIfExists('clinician_patient_link');
    }

    public function down(): void
    {
        Schema::create('clinician_patient_link', function (Blueprint $table) {
            $table->increments('link_id');
            $table->unsignedInteger('doctor_id')->nullable();
            $table->unsignedInteger('child_id')->nullable();
            $table->string('linkage_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('linkage_date')->useCurrent();
        });

        Schema::create('prescription', function (Blueprint $table) {
            $table->increments('recommendation_id');
            $table->unsignedInteger('link_id')->nullable();
            $table->text('advice_text')->nullable();
            $table->dateTime('date_issued')->nullable();
        });
    }
};
