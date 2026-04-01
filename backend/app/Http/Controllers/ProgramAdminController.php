<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProgramAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateProgram(
            $request,
            'programCreate',
            $this->adminSectionUrl('academic-configuration'),
        );

        Program::create($data);

        return $this->redirectWithMessage(
            $this->adminSectionUrl('academic-configuration'),
            'success',
            'Program created successfully.',
        );
    }

    public function update(Request $request, Program $program)
    {
        $data = $this->validateProgram(
            $request,
            'programUpdate',
            $this->adminSectionUrl('academic-configuration'),
            $program,
        );

        $program->update($data);

        return $this->redirectWithMessage(
            $this->adminSectionUrl('academic-configuration'),
            'success',
            'Program updated successfully.',
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
                    'max:20',
                    'regex:/^[A-Z0-9-]+$/',
                    Rule::unique('programs', 'code')->ignore($program?->id),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    $this->plainTextRule('Program name'),
                ],
                'org_name' => [
                    'required',
                    'string',
                    'max:255',
                    $this->plainTextRule('Organization name'),
                ],
            ],
            [
                'code.regex' => 'Program code may only contain letters, numbers, and hyphens.',
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
