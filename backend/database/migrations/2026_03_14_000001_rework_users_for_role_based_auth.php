<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('role')->nullable()->after('password');
        });

        $existingUsers = DB::table('users')->orderBy('id')->get();
        $usedUsernames = [];

        foreach ($existingUsers as $user) {
            $baseUsername = $user->email
                ? Str::before($user->email, '@')
                : 'user-' . $user->id;

            $baseUsername = Str::slug($baseUsername, '_');
            $baseUsername = $baseUsername !== '' ? $baseUsername : 'user_' . $user->id;

            $candidate = $baseUsername;
            $suffix = 1;

            while (in_array($candidate, $usedUsernames, true)) {
                $candidate = $baseUsername . '_' . $suffix;
                $suffix++;
            }

            $usedUsernames[] = $candidate;

            $role = match (true) {
                (bool) ($user->is_student ?? false) => 'student',
                (bool) ($user->is_staff ?? false) => 'office',
                default => 'admin',
            };

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'username' => $candidate,
                    'role' => $role,
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('username')->nullable(false)->change();
            $table->string('role')->default('student')->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->dropColumn(['username', 'role']);
        });
    }
};
