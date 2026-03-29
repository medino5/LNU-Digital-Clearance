<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Support\StudentNameFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateForm(
            $request,
            'studentCreate',
            [
            'student_id_number' => ['required', 'string', 'max:50', 'unique:students,student_id_number'],
            'first_name' => ['required', 'string', 'max:100', "regex:/^[A-Za-z][A-Za-z'\\-\\s]*$/"],
            'middle_initial' => ['nullable', 'string', 'size:1', 'alpha'],
            'last_name' => ['required', 'string', 'max:100', "regex:/^[A-Za-z][A-Za-z'\\-\\s]*$/"],
            'name_extension' => ['nullable', Rule::in(User::studentNameExtensionOptions())],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'password' => ['required', 'string', 'min:8'],
            ],
            $this->adminSectionUrl('accounts-records'),
        );

        $nameParts = $this->normalizedStudentNameData($data);

        DB::transaction(function () use ($data, $nameParts) {
            $user = User::create([
                'name' => StudentNameFormatter::compose(
                    $nameParts['first_name'],
                    $nameParts['middle_initial'],
                    $nameParts['last_name'],
                    $nameParts['name_extension'],
                ),
                'first_name' => $nameParts['first_name'],
                'middle_initial' => $nameParts['middle_initial'],
                'last_name' => $nameParts['last_name'],
                'name_extension' => $nameParts['name_extension'],
                'username' => $data['student_id_number'],
                'email' => null,
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_STUDENT,
                'is_student' => true,
                'is_staff' => false,
            ]);

            Student::create([
                'user_id' => $user->id,
                'student_id_number' => $data['student_id_number'],
                'program_id' => $data['program_id'],
                'year_level' => $data['year_level'],
            ]);
        });

        return $this->redirectWithMessage(
            $this->adminSectionUrl('accounts-records'),
            'success',
            'Student account created successfully.',
        );
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validateForm(
            $request,
            'studentUpdate',
            [
            'student_id_number' => ['required', 'string', 'max:50', Rule::unique('students', 'student_id_number')->ignore($student->id)],
            'first_name' => ['required', 'string', 'max:100', "regex:/^[A-Za-z][A-Za-z'\\-\\s]*$/"],
            'middle_initial' => ['nullable', 'string', 'size:1', 'alpha'],
            'last_name' => ['required', 'string', 'max:100', "regex:/^[A-Za-z][A-Za-z'\\-\\s]*$/"],
            'name_extension' => ['nullable', Rule::in(User::studentNameExtensionOptions())],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'password' => ['nullable', 'string', 'min:8'],
            ],
            $this->adminSectionUrl('accounts-records'),
        );

        $nameParts = $this->normalizedStudentNameData($data);

        DB::transaction(function () use ($data, $nameParts, $student) {
            $student->user->update([
                'name' => StudentNameFormatter::compose(
                    $nameParts['first_name'],
                    $nameParts['middle_initial'],
                    $nameParts['last_name'],
                    $nameParts['name_extension'],
                ),
                'first_name' => $nameParts['first_name'],
                'middle_initial' => $nameParts['middle_initial'],
                'last_name' => $nameParts['last_name'],
                'name_extension' => $nameParts['name_extension'],
                'username' => $data['student_id_number'],
                'password' => !empty($data['password'])
                    ? Hash::make($data['password'])
                    : $student->user->password,
                'role' => User::ROLE_STUDENT,
                'is_student' => true,
                'is_staff' => false,
            ]);

            $student->update([
                'student_id_number' => $data['student_id_number'],
                'program_id' => $data['program_id'],
                'year_level' => $data['year_level'],
            ]);
        });

        return $this->redirectWithMessage(
            $this->adminSectionUrl('accounts-records'),
            'success',
            'Student account updated successfully.',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     first_name: string,
     *     middle_initial: ?string,
     *     last_name: string,
     *     name_extension: ?string
     * }
     */
    protected function normalizedStudentNameData(array $data): array
    {
        return [
            'first_name' => StudentNameFormatter::normalizeNamePart($data['first_name']) ?? '',
            'middle_initial' => StudentNameFormatter::normalizeMiddleInitial($data['middle_initial'] ?? null),
            'last_name' => StudentNameFormatter::normalizeNamePart($data['last_name']) ?? '',
            'name_extension' => StudentNameFormatter::normalizeExtension($data['name_extension'] ?? null),
        ];
    }
}
