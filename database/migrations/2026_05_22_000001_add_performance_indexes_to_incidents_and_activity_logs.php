<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->index(['created_by', 'created_at'], 'incidents_created_by_created_at_index');
            $table->index(['status', 'updated_at'], 'incidents_status_updated_at_index');
            $table->index(['category', 'created_at'], 'incidents_category_created_at_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['incident_id', 'created_at'], 'activity_logs_incident_created_at_index');
            $table->index(['user_id', 'created_at'], 'activity_logs_user_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_incident_created_at_index');
            $table->dropIndex('activity_logs_user_created_at_index');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex('incidents_created_by_created_at_index');
            $table->dropIndex('incidents_status_updated_at_index');
            $table->dropIndex('incidents_category_created_at_index');
        });
    }
};
