<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'section')) {
                $table->string('section', 3)->nullable()->after('year_level');
                $table->index(['program_id', 'year_level', 'section'], 'students_program_year_section_idx');
            }
        });

        Schema::table('student_registration_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('student_registration_requests', 'section')) {
                $table->string('section', 3)->nullable()->after('year_level');
                $table->index(['program_id', 'year_level', 'section'], 'registration_program_year_section_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_registration_requests', function (Blueprint $table) {
            if (Schema::hasColumn('student_registration_requests', 'section')) {
                $table->dropIndex('registration_program_year_section_idx');
                $table->dropColumn('section');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'section')) {
                $table->dropIndex('students_program_year_section_idx');
                $table->dropColumn('section');
            }
        });
    }
};
