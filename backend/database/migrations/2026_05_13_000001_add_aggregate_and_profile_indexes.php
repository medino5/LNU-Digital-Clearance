<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->index(['semester_id', 'status', 'created_at'], 'clearances_semester_status_created_idx');
            $table->index(['program_code', 'semester_id', 'status'], 'clearances_program_semester_status_idx');
            $table->index(['student_id', 'created_at'], 'clearances_student_created_idx');
        });

        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->index(['clearance_id', 'status', 'signed_at'], 'clearance_steps_clearance_status_signed_idx');
        });

        Schema::table('clearance_step_events', function (Blueprint $table) {
            $table->index(['clearance_step_id', 'created_at'], 'step_events_step_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clearance_step_events', function (Blueprint $table) {
            $table->dropIndex('step_events_step_created_idx');
        });

        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->dropIndex('clearance_steps_clearance_status_signed_idx');
        });

        Schema::table('clearances', function (Blueprint $table) {
            $table->dropIndex('clearances_student_created_idx');
            $table->dropIndex('clearances_program_semester_status_idx');
            $table->dropIndex('clearances_semester_status_created_idx');
        });
    }
};
