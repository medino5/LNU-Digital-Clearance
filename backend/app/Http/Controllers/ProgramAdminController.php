<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProgramAdminController extends Controller
{
    public function index()
    {
        $programs = Program::withCount('students')->orderBy('code')->get();
        $totalStudents = (int) $programs->sum('students_count');
        $programCount = $programs->count();
        $largestProgram = $programs->sortByDesc('students_count')->first();
        $maxStudents = max((int) ($largestProgram?->students_count ?? 0), 1);

        return view('admin.programs', [
            'programs' => $programs,
            'programStats' => [
                'total_students' => $totalStudents,
                'program_count' => $programCount,
                'programs_with_students' => $programs->where('students_count', '>', 0)->count(),
                'average_students' => $programCount > 0 ? (int) round($totalStudents / $programCount) : 0,
                'largest_program_code' => $largestProgram?->code ?? 'None',
                'largest_program_students' => (int) ($largestProgram?->students_count ?? 0),
                'max_students' => $maxStudents,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProgram(
            $request,
            'programCreate',
            route('admin.programs.index'),
        );

        Program::create($data);

        return $this->redirectWithMessage(
            route('admin.programs.index'),
            'success',
            'Program created successfully.',
        );
    }

    public function update(Request $request, Program $program)
    {
        $data = $this->validateProgram(
            $request,
            'programUpdate',
            route('admin.programs.index'),
            $program,
        );

        $program->update($data);

        return $this->redirectWithMessage(
            route('admin.programs.index'),
            'success',
            'Program updated successfully.',
        );
    }

    public function destroy(Request $request, Program $program)
    {
        $redirectTo = route('admin.programs.index');
        $program->loadCount(['students', 'registrationRequests']);

        if ($program->students_count > 0) {
            return $this->redirectWithMessage(
                $redirectTo,
                'error',
                'Program cannot be deleted while students are assigned to it.',
            );
        }

        if ($program->registration_requests_count > 0) {
            return $this->redirectWithMessage(
                $redirectTo,
                'error',
                'Program cannot be deleted while mobile registration requests are using it.',
            );
        }

        $confirmation = trim((string) $request->input('delete_confirmation', ''));
        $expected = 'DELETE ' . $program->code;

        if ($confirmation !== $expected) {
            throw $this->formValidationException(
                ['delete_confirmation' => 'Type "' . $expected . '" to confirm program deletion.'],
                'programDelete',
                $redirectTo,
            );
        }

        DB::transaction(fn () => $program->delete());

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Program deleted successfully.',
        );
    }

    protected function validateProgram(
        Request $request,
        string $errorBag,
        string $redirectTo,
        ?Program $program = null,
    ): array {
        $normalized = $this->normalizeProgramInput($request);

        $validator = Validator::make(
            $normalized,
            [
                'code' => [
                    'required',
                    'string',
                    'max:15',
                    'regex:/^[A-Z0-9-]+$/',
                    Rule::unique('programs', 'code')->ignore($program?->id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:120',
                    $this->plainTextRule('Program name'),
                ],
                'org_name' => [
                    'required',
                    'string',
                    'max:120',
                    $this->plainTextRule('Organization name'),
                ],
            ],
            [
                'code.regex' => 'Program code may only contain letters, numbers, and hyphens.',
                'code.max' => 'Program code must be 15 characters or fewer.',
                'name.max' => 'Program name must be 120 characters or fewer.',
                'org_name.max' => 'Organization name must be 120 characters or fewer.',
            ],
        );

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->errorBag($errorBag)
                ->redirectTo($redirectTo);
        }

        return $validator->validated();
    }

    /**
     * @return array{code:string,name:string,org_name:string}
     */
    protected function normalizeProgramInput(Request $request): array
    {
        return [
            'code' => Str::upper(trim((string) $request->input('code', ''))),
            'name' => $this->normalizeProgramText($request->input('name')),
            'org_name' => $this->normalizeProgramText($request->input('org_name')),
        ];
    }

    protected function normalizeProgramText(mixed $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $normalized ?? '';
    }

    protected function plainTextRule(string $label): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
            $stringValue = (string) $value;

            if (! preg_match("/^[\pL\pN&'().\- ]+$/u", $stringValue)) {
                $fail($label . ' may only use letters, numbers, spaces, and basic punctuation.');

                return;
            }

            if (! preg_match('/[\pL\pN]/u', $stringValue)) {
                $fail($label . ' must include letters or numbers.');
            }
        };
    }
}
