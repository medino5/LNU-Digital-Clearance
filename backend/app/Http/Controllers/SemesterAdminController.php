<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SemesterAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255', 'unique:semesters,label'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            if (!empty($data['is_active'])) {
                Semester::query()->update(['is_active' => false]);
            }

            Semester::create([
                'label' => $data['label'],
                'is_active' => !empty($data['is_active']),
            ]);
        });

        return back()->with('success', 'Semester saved successfully.');
    }

    public function update(Request $request, Semester $semester)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255', Rule::unique('semesters', 'label')->ignore($semester->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $semester) {
            if (!empty($data['is_active'])) {
                Semester::query()->update(['is_active' => false]);
            }

            $semester->update([
                'label' => $data['label'],
                'is_active' => !empty($data['is_active']),
            ]);
        });

        return back()->with('success', 'Semester updated successfully.');
    }
}
