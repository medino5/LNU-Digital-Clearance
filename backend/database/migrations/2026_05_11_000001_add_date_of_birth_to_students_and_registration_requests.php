<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('year_level');
        });

        Schema::table('student_registration_requests', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('year_level');
        });
    }

    public function down(): void
    {
        Schema::table('student_registration_requests', function (Blueprint $table) {
            $table->dropColumn('date_of_birth');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('date_of_birth');
        });
    }
};
