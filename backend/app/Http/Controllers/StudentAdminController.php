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
    public function index(Request $request)
    {
        $studentSearch = trim((string) $request->query('student_search', ''));
        $studentProgramId = $request->query('student_program');
        $studentYearLevel = $request->query('student_year_level');

        $studentsQuery = Student::query()
            ->with(['user', 'program'])
            ->join('users', 'users.id', '=', 'students.user_id')
            ->select('students.*');

        if ($studentSearch !== '') {
            $studentSearchLike = '%' . $studentSearch . '%';

            $fullNameExpressions = $this->studentSearchNameExpressions();

            $studentsQuery->where(function ($query) use ($studentSearchLike, $fullNameExpressions) {
                $query->where('students.student_id_number', 'like', $studentSearchLike)
                    ->orWhere('users.first_name', 'like', $studentSearchLike)
                    ->orWhere('users.last_name', 'like', $studentSearchLike)
                    ->orWhere('users.middle_initial', 'like', $studentSearchLike);

                foreach ($fullNameExpressions as $expression) {
                    $query->orWhereRaw($expression . ' like ?', [$studentSearchLike]);
                }
            });
        }

        if ($studentProgramId !== null && $studentProgramId !== '') {
            $studentsQuery->where('students.program_id', $studentProgramId);
        }

        if ($studentYearLevel !== null && $studentYearLevel !== '') {
            $studentsQuery->where('students.year_level', $studentYearLevel);
        }

        $students = $studentsQuery
            ->orderBy('students.student_id_number')
            ->paginate(25)
            ->withQueryString();

        return view('admin.students', [
            'programs' => \App\Models\Program::orderBy('code')->get(),
            'students' => $students,
            'yearLevels' => [1, 2, 3, 4],
            'studentNameExtensions' => User::studentNameExtensionOptions(),
            'studentSearch' => $studentSearch,
            'studentProgramId' => $studentProgramId,
            'studentYearLevel' => $studentYearLevel,
            'hasStudents' => $students->total() > 0,
        ]);
    }

    public function store(Request $request)
    {
        $redirectTo = route('admin.students.index');

        $data = $this->validateForm(
            $request,
            'studentCreate',
            [
                'student_id_number' => $this->studentIdRules(),
                'first_name' => $this->studentNameRules('First name'),
                'middle_initial' => ['nullable', 'string', 'size:1', 'regex:/^\pL$/u'],
                'last_name' => $this->studentNameRules('Last name'),
                'name_extension' => ['nullable', Rule::in(User::studentNameExtensionOptions())],
                'program_id' => ['required', 'exists:programs,id'],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
                'password' => ['required', 'string', 'min:8', 'max:72'],
            ],
            $redirectTo,
            $this->studentValidationMessages(),
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
                'date_of_birth' => $data['date_of_birth'] ?? $this->fallbackDateOfBirthFor($data['student_id_number']),
            ]);
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Student account created successfully.',
        );
    }

    public function update(Request $request, Student $student)
    {
        $redirectTo = route('admin.students.index');

        $data = $this->validateForm(
            $request,
            'studentUpdate',
            [
                'student_id_number' => $this->studentIdRules($student),
                'first_name' => $this->studentNameRules('First name'),
                'middle_initial' => ['nullable', 'string', 'size:1', 'regex:/^\pL$/u'],
                'last_name' => $this->studentNameRules('Last name'),
                'name_extension' => ['nullable', Rule::in(User::studentNameExtensionOptions())],
                'program_id' => ['required', 'exists:programs,id'],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
                'password' => ['nullable', 'string', 'min:8', 'max:72'],
            ],
            $redirectTo,
            $this->studentValidationMessages(),
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
                'date_of_birth' => $data['date_of_birth'] ?? $this->fallbackDateOfBirthFor($data['student_id_number']),
            ]);
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Student account updated successfully.',
        );
    }

    public function destroy(Request $request, Student $student)
    {
        $redirectTo = route('admin.students.index');
        $expected = 'DELETE ' . $student->student_id_number;
        $confirmation = trim((string) $request->input('delete_confirmation', ''));

        if ($confirmation !== $expected) {
            throw $this->formValidationException(
                ['delete_confirmation' => 'Type "' . $expected . '" to confirm student account deletion.'],
                'studentDelete',
                $redirectTo,
            );
        }

        DB::transaction(function () use ($student) {
            $student->load('user');
            $student->user?->delete();
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Student account deleted successfully.',
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

    /**
     * @return array<int, mixed>
     */
    protected function studentIdRules(?Student $student = null): array
    {
        $uniqueRule = Rule::unique('students', 'student_id_number');

        if ($student) {
            $uniqueRule = $uniqueRule->ignore($student->id);
        }

        return [
            'bail',
            'required',
            'string',
            'regex:/^\d+$/',
            'size:7',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $studentId = (string) $value;
                $currentYearPrefix = (int) now()->format('y');
                $enrollmentYearPrefix = (int) substr($studentId, 0, 2);

                if ($enrollmentYearPrefix > $currentYearPrefix) {
                    $fail('Student ID cannot use a future enrollment year.');
                }
            },
            $uniqueRule,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function studentValidationMessages(): array
    {
        return [
            'student_id_number.regex' => 'Student ID cannot contain letters or special characters.',
            'student_id_number.size' => 'Student ID must be exactly 7 digits.',
            'first_name.max' => 'First name must be 60 characters or fewer.',
            'last_name.max' => 'Last name must be 60 characters or fewer.',
            'middle_initial.regex' => 'Middle initial must be one letter.',
            'date_of_birth.date_format' => 'Birthday must use the YYYY-MM-DD format.',
            'date_of_birth.before_or_equal' => 'Birthday cannot be in the future.',
            'date_of_birth.after_or_equal' => 'Birthday is outside the supported range.',
            'password.max' => 'Password must be 72 characters or fewer.',
        ];
    }

    protected function fallbackDateOfBirthFor(string $studentId): string
    {
        $digits = preg_replace('/\D/', '', $studentId) ?: '0';
        $number = (int) substr($digits, -5);
        $year = 2001 + ($number % 8);
        $month = 1 + ($number % 12);
        $day = 1 + ($number % 28);

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * @return array<int, mixed>
     */
    protected function studentNameRules(string $label): array
    {
        return [
            'required',
            'string',
            'max:60',
            function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
                $value = (string) $value;

                if (! preg_match("/^\pL[\pL'\\- ]*$/u", $value)) {
                    $fail($label . ' may only contain letters, spaces, apostrophes, and hyphens.');
                }
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function studentSearchNameExpressions(): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return [
                "TRIM(users.first_name || ' ' || COALESCE(users.middle_initial || ' ', '') || users.last_name)",
                "TRIM(users.last_name || ', ' || users.first_name || ' ' || COALESCE(users.middle_initial, ''))",
                "TRIM(users.first_name || ' ' || users.last_name)",
            ];
        }

        return [
            "TRIM(CONCAT(users.first_name, ' ', COALESCE(CONCAT(users.middle_initial, ' '), ''), users.last_name))",
            "TRIM(CONCAT(users.last_name, ', ', users.first_name, ' ', COALESCE(users.middle_initial, '')))",
            "TRIM(CONCAT(users.first_name, ' ', users.last_name))",
        ];
    }
}
