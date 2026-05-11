<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\StudentRegistrationRequest;
use App\Models\User;
use App\Support\StudentNameFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentRegistrationController extends Controller
{
    public function options()
    {
        return response()->json([
            'programs' => Program::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'org_name'])
                ->map(fn (Program $program) => [
                    'id' => $program->id,
                    'code' => $program->code,
                    'name' => $program->name,
                    'org_name' => $program->org_name,
                ]),
            'year_levels' => collect([1, 2, 3, 4])
                ->map(fn (int $yearLevel) => [
                    'value' => $yearLevel,
                    'label' => $this->yearLevelLabel($yearLevel),
                ]),
            'name_extensions' => User::studentNameExtensionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'student_id_number' => trim((string) $request->input('student_id_number', '')),
            'first_name' => trim((string) $request->input('first_name', '')),
            'middle_initial' => trim((string) $request->input('middle_initial', '')),
            'last_name' => trim((string) $request->input('last_name', '')),
            'name_extension' => filled($request->input('name_extension'))
                ? trim((string) $request->input('name_extension'))
                : null,
            'email' => filled($request->input('email'))
                ? strtolower(trim((string) $request->input('email')))
                : null,
        ]);

        $data = $request->validate(
            [
                'student_id_number' => $this->studentIdRules(),
                'first_name' => $this->studentNameRules('First name'),
                'middle_initial' => ['nullable', 'string', 'size:1', 'regex:/^\pL$/u'],
                'last_name' => $this->studentNameRules('Last name'),
                'name_extension' => ['nullable', Rule::in(User::studentNameExtensionOptions())],
                'email' => [
                    'nullable',
                    'string',
                    'max:120',
                    'email',
                    'ends_with:@lnu.edu.ph',
                    Rule::unique('users', 'email'),
                    Rule::unique('student_registration_requests', 'email')
                        ->where('status', StudentRegistrationRequest::STATUS_PENDING),
                ],
                'program_id' => ['required', 'integer', 'exists:programs,id'],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
                'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            ],
            [
                'student_id_number.regex' => 'Student ID cannot contain letters or special characters.',
                'student_id_number.size' => 'Student ID must be exactly 7 digits.',
                'middle_initial.regex' => 'Middle initial must be one letter.',
                'email.ends_with' => 'Use your LNU institutional email ending in @lnu.edu.ph.',
                'date_of_birth.required' => 'Birthday is required.',
                'date_of_birth.date_format' => 'Birthday must use the YYYY-MM-DD format.',
                'date_of_birth.before_or_equal' => 'Birthday cannot be in the future.',
                'date_of_birth.after_or_equal' => 'Birthday is outside the supported range.',
                'password.confirmed' => 'Password confirmation does not match.',
            ],
        );

        $nameParts = [
            'first_name' => StudentNameFormatter::normalizeNamePart($data['first_name']) ?? '',
            'middle_initial' => StudentNameFormatter::normalizeMiddleInitial($data['middle_initial'] ?? null),
            'last_name' => StudentNameFormatter::normalizeNamePart($data['last_name']) ?? '',
            'name_extension' => StudentNameFormatter::normalizeExtension($data['name_extension'] ?? null),
        ];

        $registrationRequest = StudentRegistrationRequest::create([
            'student_id_number' => $data['student_id_number'],
            'first_name' => $nameParts['first_name'],
            'middle_initial' => $nameParts['middle_initial'],
            'last_name' => $nameParts['last_name'],
            'name_extension' => $nameParts['name_extension'],
            'email' => filled($data['email'] ?? null) ? strtolower($data['email']) : null,
            'program_id' => $data['program_id'],
            'year_level' => $data['year_level'],
            'date_of_birth' => $data['date_of_birth'],
            'password' => Hash::make($data['password']),
            'status' => StudentRegistrationRequest::STATUS_PENDING,
        ])->load('program');

        return response()->json([
            'message' => 'Registration submitted. Please wait for admin approval before signing in.',
            'registration_request' => [
                'id' => $registrationRequest->id,
                'status' => $registrationRequest->status,
                'name' => $registrationRequest->displayName(),
                'student_id_number' => $registrationRequest->student_id_number,
                'program' => [
                    'id' => $registrationRequest->program->id,
                    'code' => $registrationRequest->program->code,
                    'name' => $registrationRequest->program->name,
                    'org_name' => $registrationRequest->program->org_name,
                ],
                'year_level' => $registrationRequest->year_level,
                'year_level_label' => $registrationRequest->yearLevelLabel(),
                'date_of_birth' => $registrationRequest->date_of_birth?->toDateString(),
            ],
        ], 202);
    }

    /**
     * @return array<int, mixed>
     */
    private function studentIdRules(): array
    {
        return [
            'bail',
            'required',
            'string',
            'regex:/^\d+$/',
            'size:7',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $currentYearPrefix = (int) now()->format('y');
                $enrollmentYearPrefix = (int) substr((string) $value, 0, 2);

                if ($enrollmentYearPrefix > $currentYearPrefix) {
                    $fail('Student ID cannot use a future enrollment year.');
                }
            },
            Rule::unique('students', 'student_id_number'),
            Rule::unique('users', 'username'),
            function (string $attribute, mixed $value, \Closure $fail): void {
                $hasPendingRequest = StudentRegistrationRequest::query()
                    ->where('student_id_number', (string) $value)
                    ->where('status', StudentRegistrationRequest::STATUS_PENDING)
                    ->exists();

                if ($hasPendingRequest) {
                    $fail('A registration request for this student ID is already pending admin approval.');
                }
            },
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function studentNameRules(string $label): array
    {
        return [
            'required',
            'string',
            'max:60',
            function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
                if (! preg_match("/^\pL[\pL'\\- ]*$/u", (string) $value)) {
                    $fail($label . ' may only contain letters, spaces, apostrophes, and hyphens.');
                }
            },
        ];
    }

    private function yearLevelLabel(int $yearLevel): string
    {
        return match ($yearLevel) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => $yearLevel . 'th Year',
        };
    }
}
