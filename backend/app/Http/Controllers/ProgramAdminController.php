<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:255'],
            'org_name' => ['required', 'string', 'max:255'],
        ]);

        Program::create($data);

        return back()->with('success', 'Program created successfully.');
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('programs', 'code')->ignore($program->id)],
            'name' => ['required', 'string', 'max:255'],
            'org_name' => ['required', 'string', 'max:255'],
        ]);

        $program->update($data);

        return back()->with('success', 'Program updated successfully.');
    }
}
