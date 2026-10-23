<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('child_inventory')) {
            Schema::create('child_inventory', function (Blueprint $table) {
                $table->increments('inventory_id');
                $table->unsignedInteger('child_id');
                $table->string('item_key');
                $table->string('item_name');
                $table->unsignedInteger('cost')->default(0);
                $table->timestamp('purchase_date')->nullable();
                $table->unique(['child_id', 'item_key']);
            });
        } else {
            Schema::table('child_inventory', function (Blueprint $table) {
                if (!Schema::hasColumn('child_inventory', 'item_key')) {
                    $table->string('item_key')->nullable();
                }
                if (!Schema::hasColumn('child_inventory', 'item_name')) {
                    $table->string('item_name')->nullable();
                }
                if (!Schema::hasColumn('child_inventory', 'cost')) {
                    $table->unsignedInteger('cost')->default(0);
                }
                if (!Schema::hasColumn('child_inventory', 'purchase_date')) {
                    $table->timestamp('purchase_date')->nullable();
                }
            });
        }

        if (Schema::hasTable('child_profile') && !Schema::hasColumn('child_profile', 'equipped_skin_key')) {
            Schema::table('child_profile', function (Blueprint $table) {
                $table->string('equipped_skin_key')->default('skin_frog_green');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('child_profile') && Schema::hasColumn('child_profile', 'equipped_skin_key')) {
            Schema::table('child_profile', function (Blueprint $table) {
                $table->dropColumn('equipped_skin_key');
            });
        }
        // child_inventory predates this migration on live installs; keep table.
    }
};
