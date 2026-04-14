<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SemesterAdminController extends Controller
{
    public function index()
    {
        return view('admin.semesters', [
            'semesters' => Semester::orderByDesc('is_active')->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $redirectTo = route('admin.semesters.index');

        $data = $this->validateForm(
            $request,
            'semesterCreate',
            [
            'label' => ['required', 'string', 'max:255', 'unique:semesters,label'],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'is_active' => ['nullable', 'boolean'],
            ],
            $redirectTo,
            [
                'academic_year.regex' => 'Academic year must use the YYYY-YYYY format.',
            ],
        );

        DB::transaction(function () use ($data) {
            if (!empty($data['is_active'])) {
                Semester::query()->update(['is_active' => false]);
            }

            Semester::create([
                'label' => $data['label'],
                'academic_year' => $data['academic_year'],
                'is_active' => !empty($data['is_active']),
            ]);
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Semester saved successfully.',
        );
    }

    public function update(Request $request, Semester $semester)
    {
        $redirectTo = route('admin.semesters.index');

        $data = $this->validateForm(
            $request,
            'semesterUpdate',
            [
            'label' => ['required', 'string', 'max:255', Rule::unique('semesters', 'label')->ignore($semester->id)],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'is_active' => ['nullable', 'boolean'],
            ],
            $redirectTo,
            [
                'academic_year.regex' => 'Academic year must use the YYYY-YYYY format.',
            ],
        );

        DB::transaction(function () use ($data, $semester) {
            if (!empty($data['is_active'])) {
                Semester::query()->update(['is_active' => false]);
            }

            $semester->update([
                'label' => $data['label'],
                'academic_year' => $data['academic_year'],
                'is_active' => !empty($data['is_active']),
            ]);
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Semester updated successfully.',
        );
    }
}
