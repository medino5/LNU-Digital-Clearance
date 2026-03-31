<?php

namespace Tests\Unit;

use App\Support\StudentNameFormatter;
use Tests\TestCase;

class StudentNameFormatterTest extends TestCase
{
    public function test_it_parses_and_recomposes_common_student_name_formats(): void
    {
        // Cover the common name patterns we need to support when migrating
        // legacy single-string names into structured student name fields.
        $simple = StudentNameFormatter::parse('Jane Systems');
        $withInitial = StudentNameFormatter::parse('John A. Doe');
        $compoundLastName = StudentNameFormatter::parse('Danica D. Dela Cruz');
        $withExtension = StudentNameFormatter::parse('Juan M. Dela Cruz Jr.');

        // Simple two-part names should stay straightforward.
        $this->assertSame([
            'first_name' => 'Jane',
            'middle_initial' => null,
            'last_name' => 'Systems',
            'name_extension' => null,
            'composed_name' => 'Jane Systems',
        ], $simple);

        // Names with a middle initial should preserve the initial and rebuild
        // the display name with the expected punctuation.
        $this->assertSame('John', $withInitial['first_name']);
        $this->assertSame('A', $withInitial['middle_initial']);
        $this->assertSame('Doe', $withInitial['last_name']);
        $this->assertSame('John A. Doe', $withInitial['composed_name']);

        // Compound surnames should remain attached to the last-name field
        // instead of being split incorrectly into separate name parts.
        $this->assertSame('Danica', $compoundLastName['first_name']);
        $this->assertSame('D', $compoundLastName['middle_initial']);
        $this->assertSame('Dela Cruz', $compoundLastName['last_name']);
        $this->assertSame('Danica D. Dela Cruz', $compoundLastName['composed_name']);

        // Supported suffixes should be extracted into the extension field and
        // then recomposed cleanly into the final display name.
        $this->assertSame('Juan', $withExtension['first_name']);
        $this->assertSame('M', $withExtension['middle_initial']);
        $this->assertSame('Dela Cruz', $withExtension['last_name']);
        $this->assertSame('Jr', $withExtension['name_extension']);
        $this->assertSame('Juan M. Dela Cruz Jr', $withExtension['composed_name']);
    }
}
