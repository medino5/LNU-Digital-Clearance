<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fallbackProgramId = DB::table('programs')->value('id');

        if (!$fallbackProgramId) {
            return;
        }

        $students = DB::table('users')
            ->where('role', 'student')
            ->orderBy('id')
            ->get();

        foreach ($students as $user) {
            $exists = DB::table('students')
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $studentNumber = $user->username ?: 'S' . str_pad((string) $user->id, 7, '0', STR_PAD_LEFT);
            $programId = $user->program_id ?: $fallbackProgramId;
            $yearLevel = $user->year_level ?: 1;

            DB::table('students')->insert([
                'user_id' => $user->id,
                'student_id_number' => $studentNumber,
                'program_id' => $programId,
                'year_level' => max(1, min(4, (int) $yearLevel)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid deleting user-owned profile data.
    }
};
