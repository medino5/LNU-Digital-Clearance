<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id_number' => ['required', 'string', 'max:50', 'unique:students,student_id_number'],
            'name' => ['required', 'string', 'max:255'],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
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

        return back()->with('success', 'Student account created successfully.');
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'student_id_number' => ['required', 'string', 'max:50', Rule::unique('students', 'student_id_number')->ignore($student->id)],
            'name' => ['required', 'string', 'max:255'],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($data, $student) {
            $student->user->update([
                'name' => $data['name'],
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

        return back()->with('success', 'Student account updated successfully.');
    }
}
