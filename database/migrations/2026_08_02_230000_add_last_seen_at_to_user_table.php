<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user')) {
            return;
        }

        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('email_verified_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user') || !Schema::hasColumn('user', 'last_seen_at')) {
            return;
        }

        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
};
