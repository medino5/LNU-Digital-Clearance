<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->string('academic_year')->nullable()->after('label');
        });

        DB::table('semesters')
            ->orderBy('id')
            ->get()
            ->each(function (object $semester): void {
                $academicYear = null;

                if (preg_match('/(20\d{2}-20\d{2})$/', $semester->label, $matches)) {
                    $academicYear = $matches[1];
                }

                DB::table('semesters')
                    ->where('id', $semester->id)
                    ->update([
                        'academic_year' => $academicYear,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
    }
};
