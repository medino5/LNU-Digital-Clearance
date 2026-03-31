<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_initial', 1)->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_initial');
            $table->string('name_extension', 10)->nullable()->after('last_name');
        });

        $students = DB::table('users')
            ->where('role', 'student')
            ->orWhere('is_student', true)
            ->get(['id', 'name']);

        foreach ($students as $student) {
            $parsed = $this->parseName((string) $student->name);

            DB::table('users')
                ->where('id', $student->id)
                ->update([
                    'first_name' => $parsed['first_name'],
                    'middle_initial' => $parsed['middle_initial'],
                    'last_name' => $parsed['last_name'],
                    'name_extension' => $parsed['name_extension'],
                    'name' => $parsed['composed_name'],
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'middle_initial',
                'last_name',
                'name_extension',
            ]);
        });
    }

    /**
     * @return array{
     *     first_name: ?string,
     *     middle_initial: ?string,
     *     last_name: ?string,
     *     name_extension: ?string,
     *     composed_name: string
     * }
     */
    protected function parseName(string $name): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($name)) ?? '';
        $tokens = $normalized === '' ? [] : (preg_split('/\s+/', $normalized) ?: []);

        $extension = null;

        if ($tokens !== [] && ($candidate = $this->normalizeExtension(end($tokens) ?: null)) !== null) {
            $extension = $candidate;
            array_pop($tokens);
        }

        $firstName = null;
        $middleInitial = null;
        $lastName = null;

        if ($tokens !== []) {
            $firstName = $this->normalizeNamePart(array_shift($tokens));
        }

        if ($tokens !== [] && ($candidate = $this->normalizeMiddleInitial($tokens[0] ?? null)) !== null) {
            $middleInitial = $candidate;
            array_shift($tokens);
        }

        if ($tokens !== []) {
            $lastName = $this->normalizeNamePart(implode(' ', $tokens));
        }

        return [
            'first_name' => $firstName,
            'middle_initial' => $middleInitial,
            'last_name' => $lastName,
            'name_extension' => $extension,
            'composed_name' => $this->composeName(
                $firstName,
                $middleInitial,
                $lastName,
                $extension,
                $normalized,
            ),
        ];
    }

    protected function composeName(
        ?string $firstName,
        ?string $middleInitial,
        ?string $lastName,
        ?string $nameExtension,
        string $fallback,
    ): string {
        $parts = [];

        if ($firstName) {
            $parts[] = $firstName;
        }

        if ($middleInitial) {
            $parts[] = $middleInitial . '.';
        }

        if ($lastName) {
            $parts[] = $lastName;
        }

        if ($nameExtension) {
            $parts[] = $nameExtension;
        }

        $composed = trim(implode(' ', $parts));

        return $composed !== '' ? $composed : $fallback;
    }

    protected function normalizeNamePart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function normalizeMiddleInitial(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));
        $normalized = rtrim($normalized, '.');

        return preg_match('/^[A-Z]$/', $normalized) === 1 ? $normalized : null;
    }

    protected function normalizeExtension(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));
        $normalized = rtrim($normalized, '.');

        return match ($normalized) {
            'JR' => 'Jr',
            'SR' => 'Sr',
            'II' => 'II',
            'III' => 'III',
            'IV' => 'IV',
            default => null,
        };
    }
};
