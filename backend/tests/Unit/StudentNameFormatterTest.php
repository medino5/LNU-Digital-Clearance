<?php

namespace Tests\Unit;

use App\Support\StudentNameFormatter;
use Tests\TestCase;

class StudentNameFormatterTest extends TestCase
{
    public function test_it_parses_and_recomposes_common_student_name_formats(): void
    {
        $simple = StudentNameFormatter::parse('Jane Systems');
        $withInitial = StudentNameFormatter::parse('John A. Doe');
        $compoundLastName = StudentNameFormatter::parse('Danica D. Dela Cruz');
        $withExtension = StudentNameFormatter::parse('Juan M. Dela Cruz Jr.');

        $this->assertSame([
            'first_name' => 'Jane',
            'middle_initial' => null,
            'last_name' => 'Systems',
            'name_extension' => null,
            'composed_name' => 'Jane Systems',
        ], $simple);

        $this->assertSame('John', $withInitial['first_name']);
        $this->assertSame('A', $withInitial['middle_initial']);
        $this->assertSame('Doe', $withInitial['last_name']);
        $this->assertSame('John A. Doe', $withInitial['composed_name']);

        $this->assertSame('Danica', $compoundLastName['first_name']);
        $this->assertSame('D', $compoundLastName['middle_initial']);
        $this->assertSame('Dela Cruz', $compoundLastName['last_name']);
        $this->assertSame('Danica D. Dela Cruz', $compoundLastName['composed_name']);

        $this->assertSame('Juan', $withExtension['first_name']);
        $this->assertSame('M', $withExtension['middle_initial']);
        $this->assertSame('Dela Cruz', $withExtension['last_name']);
        $this->assertSame('Jr', $withExtension['name_extension']);
        $this->assertSame('Juan M. Dela Cruz Jr', $withExtension['composed_name']);
    }
}
