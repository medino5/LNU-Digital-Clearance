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
        Schema::table('programs', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->string('org_name')->nullable()->after('name');
        });

        $programs = DB::table('programs')->orderBy('id')->get();
        $usedCodes = [];

        foreach ($programs as $program) {
            $words = preg_split('/\s+/', trim((string) $program->name)) ?: [];
            $acronym = collect($words)
                ->map(fn ($word) => Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $word), 0, 1))
                ->implode('');

            $baseCode = Str::upper($acronym ?: 'PRG' . $program->id);
            $candidate = $baseCode;
            $suffix = 1;

            while (in_array($candidate, $usedCodes, true)) {
                $candidate = $baseCode . $suffix;
                $suffix++;
            }

            $usedCodes[] = $candidate;

            DB::table('programs')
                ->where('id', $program->id)
                ->update([
                    'code' => $candidate,
                    'org_name' => Str::title($program->name),
                ]);
        }

        Schema::table('programs', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
            $table->string('org_name')->nullable(false)->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'org_name']);
        });
    }
};
