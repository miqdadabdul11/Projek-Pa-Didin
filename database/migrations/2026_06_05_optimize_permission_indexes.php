<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes untuk Spatie Permission untuk faster role queries
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (!Schema::hasIndex('model_has_roles', 'idx_model_type_model_id')) {
                $table->index(['model_type', 'model_id'], 'idx_model_type_model_id');
            }
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            if (!Schema::hasIndex('role_has_permissions', 'idx_role_id')) {
                $table->index('role_id', 'idx_role_id');
            }
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (!Schema::hasIndex('model_has_permissions', 'idx_model_type_model_id')) {
                $table->index(['model_type', 'model_id'], 'idx_model_type_model_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('idx_model_type_model_id');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_role_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_model_type_model_id');
        });
    }
};
