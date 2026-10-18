<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('doctor_profile')) {
            return;
        }

        // Avoid ->after() so this works even when column order differs on Hostinger.
        Schema::table('doctor_profile', function (Blueprint $table) {
            if (!Schema::hasColumn('doctor_profile', 'clinic_address')) {
                $table->string('clinic_address', 500)->nullable();
            }
            if (!Schema::hasColumn('doctor_profile', 'operating_hours')) {
                $table->string('operating_hours', 255)->nullable();
            }
            if (!Schema::hasColumn('doctor_profile', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('doctor_profile', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('doctor_profile', 'is_partnered_directory_visible')) {
                $table->boolean('is_partnered_directory_visible')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('doctor_profile')) {
            return;
        }

        Schema::table('doctor_profile', function (Blueprint $table) {
            $cols = [];
            foreach (['clinic_address', 'operating_hours', 'latitude', 'longitude', 'is_partnered_directory_visible'] as $col) {
                if (Schema::hasColumn('doctor_profile', $col)) {
                    $cols[] = $col;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
