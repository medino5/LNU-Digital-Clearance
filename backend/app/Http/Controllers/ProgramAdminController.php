<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateForm(
            $request,
            'programCreate',
            [
            'code' => ['required', 'string', 'max:20', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:255'],
            'org_name' => ['required', 'string', 'max:255'],
            ],
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
        $data = $this->validateForm(
            $request,
            'programUpdate',
            [
            'code' => ['required', 'string', 'max:20', Rule::unique('programs', 'code')->ignore($program->id)],
            'name' => ['required', 'string', 'max:255'],
            'org_name' => ['required', 'string', 'max:255'],
            ],
            $this->adminSectionUrl('academic-configuration'),
        );

        $program->update($data);

        return $this->redirectWithMessage(
            $this->adminSectionUrl('academic-configuration'),
            'success',
            'Program updated successfully.',
        );
    }
}
