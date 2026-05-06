<?php

namespace App\Support;

use Illuminate\Support\Str;

class StudentNameFormatter
{
    /**
     * @return array<int, string>
     */
    public static function extensionOptions(): array
    {
        return ['Jr', 'Sr', 'II', 'III', 'IV'];
    }

    public static function compose(
        ?string $firstName,
        ?string $middleInitial,
        ?string $lastName,
        ?string $nameExtension,
        ?string $fallback = null,
    ): string {
        $parts = [];

        $firstName = self::normalizeNamePart($firstName);
        $middleInitial = self::normalizeMiddleInitial($middleInitial);
        $lastName = self::normalizeNamePart($lastName);
        $nameExtension = self::normalizeExtension($nameExtension);

        if ($firstName !== null) {
            $parts[] = $firstName;
        }

        if ($middleInitial !== null) {
            $parts[] = $middleInitial . '.';
        }

        if ($lastName !== null) {
            $parts[] = $lastName;
        }

        if ($nameExtension !== null) {
            $parts[] = $nameExtension;
        }

        $composed = trim(implode(' ', $parts));

        if ($composed !== '') {
            return $composed;
        }

        return trim((string) $fallback);
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
    public static function parse(string $name): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($name)) ?? '';
        $tokens = $normalized === '' ? [] : (preg_split('/\s+/', $normalized) ?: []);

        $extension = null;

        if ($tokens !== [] && ($candidate = self::normalizeExtension(end($tokens) ?: null)) !== null) {
            $extension = $candidate;
            array_pop($tokens);
        }

        $firstName = null;
        $middleInitial = null;
        $lastName = null;

        if ($tokens !== []) {
            $firstName = self::normalizeNamePart(array_shift($tokens));
        }

        if ($tokens !== [] && ($candidate = self::normalizeMiddleInitial($tokens[0] ?? null)) !== null) {
            $middleInitial = $candidate;
            array_shift($tokens);
        }

        if ($tokens !== []) {
            $lastName = self::normalizeNamePart(implode(' ', $tokens));
        }

        return [
            'first_name' => $firstName,
            'middle_initial' => $middleInitial,
            'last_name' => $lastName,
            'name_extension' => $extension,
            'composed_name' => self::compose(
                $firstName,
                $middleInitial,
                $lastName,
                $extension,
                $normalized,
            ),
        ];
    }

    public static function normalizeNamePart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function normalizeMiddleInitial(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::upper(trim($value));
        $normalized = rtrim($normalized, '.');

        return preg_match('/^\pL$/u', $normalized) === 1 ? $normalized : null;
    }

    public static function normalizeExtension(?string $value): ?string
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
}
