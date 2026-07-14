<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->increments('log_id');
                $table->unsignedInteger('admin_id')->nullable();
                $table->unsignedInteger('actor_user_id')->nullable();
                $table->string('action_taken')->nullable();
                $table->string('target_entity')->nullable();
                $table->string('ip_address')->nullable();
                $table->json('metadata')->nullable();
            });

            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'actor_user_id')) {
                $table->unsignedInteger('actor_user_id')->nullable()->after('admin_id');
            }
            if (!Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('audit_logs', 'actor_user_id')) {
                $table->dropColumn('actor_user_id');
            }
        });
    }
};
