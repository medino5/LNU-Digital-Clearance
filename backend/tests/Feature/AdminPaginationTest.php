<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Models\Program;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // IMPORTANT: ensures mis.admin exists
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_students_pagination_and_filters_work_together(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        // USE EXISTING PROGRAM (from seeder)
        $program = Program::firstOrFail();

        Student::factory()->count(120)->create([
            'program_id' => $program->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.index', [
            'student_search' => '',
            'page' => 2,
        ]));

        $response->assertOk();

        // Check pagination UI
        $response->assertSee('Showing');
        $response->assertSee('students');

    }
}