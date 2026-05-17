<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentRegistrationRequest;
use App\Models\User;
use App\Support\StudentNameFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminStudentRegistrationRequestController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:' . implode(',', [
                StudentRegistrationRequest::STATUS_PENDING,
                StudentRegistrationRequest::STATUS_APPROVED,
                StudentRegistrationRequest::STATUS_REJECTED,
            ])],
        ], [
            'search.max' => 'Registration request search must be 50 characters or fewer.',
        ]);

        $status = in_array($request->query('status'), [
            StudentRegistrationRequest::STATUS_PENDING,
            StudentRegistrationRequest::STATUS_APPROVED,
            StudentRegistrationRequest::STATUS_REJECTED,
        ], true) ? $request->query('status') : StudentRegistrationRequest::STATUS_PENDING;

        $search = trim((string) $request->query('search', ''));

        $requestsQuery = StudentRegistrationRequest::query()
            ->with(['program', 'reviewer', 'createdUser'])
            ->where('status', $status);

        if ($search !== '') {
            $searchLike = '%' . $search . '%';

            $requestsQuery->where(function ($query) use ($searchLike) {
                $query->where('student_id_number', 'like', $searchLike)
                    ->orWhere('first_name', 'like', $searchLike)
                    ->orWhere('last_name', 'like', $searchLike)
                    ->orWhere('section', 'like', $searchLike)
                    ->orWhere('email', 'like', $searchLike)
                    ->orWhereHas('program', function ($programQuery) use ($searchLike) {
                        $programQuery
                            ->where('code', 'like', $searchLike)
                            ->orWhere('name', 'like', $searchLike);
                    });
            });
        }

        return view('admin.student-registration-requests', [
            'requests' => $requestsQuery
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'search' => $search,
            'pendingCount' => StudentRegistrationRequest::query()
                ->where('status', StudentRegistrationRequest::STATUS_PENDING)
                ->count(),
            'approvedCount' => StudentRegistrationRequest::query()
                ->where('status', StudentRegistrationRequest::STATUS_APPROVED)
                ->count(),
            'rejectedCount' => StudentRegistrationRequest::query()
                ->where('status', StudentRegistrationRequest::STATUS_REJECTED)
                ->count(),
        ]);
    }

    public function approve(Request $request, StudentRegistrationRequest $registrationRequest)
    {
        $redirectTo = route('admin.registration-requests.index');

        if ($registrationRequest->status !== StudentRegistrationRequest::STATUS_PENDING) {
            return $this->redirectWithMessage(
                $redirectTo,
                'error',
                'This registration request has already been reviewed.',
            );
        }

        try {
            DB::transaction(function () use ($registrationRequest, $request) {
                $this->assertStudentStillAvailable($registrationRequest);

                $user = User::create([
                    'name' => StudentNameFormatter::compose(
                        $registrationRequest->first_name,
                        $registrationRequest->middle_initial,
                        $registrationRequest->last_name,
                        $registrationRequest->name_extension,
                    ),
                    'first_name' => $registrationRequest->first_name,
                    'middle_initial' => $registrationRequest->middle_initial,
                    'last_name' => $registrationRequest->last_name,
                    'name_extension' => $registrationRequest->name_extension,
                    'username' => $registrationRequest->student_id_number,
                    'email' => $registrationRequest->email,
                    'password' => $registrationRequest->password,
                    'role' => User::ROLE_STUDENT,
                    'is_student' => true,
                    'is_staff' => false,
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'student_id_number' => $registrationRequest->student_id_number,
                    'program_id' => $registrationRequest->program_id,
                    'year_level' => $registrationRequest->year_level,
                    'section' => $registrationRequest->section,
                    'date_of_birth' => $registrationRequest->date_of_birth,
                ]);

                $registrationRequest->update([
                    'status' => StudentRegistrationRequest::STATUS_APPROVED,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'created_user_id' => $user->id,
                    'review_note' => null,
                ]);
            });
        } catch (RuntimeException $exception) {
            return $this->redirectWithMessage($redirectTo, 'error', $exception->getMessage());
        }

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Student registration approved and account created.',
        );
    }

    public function reject(Request $request, StudentRegistrationRequest $registrationRequest)
    {
        $redirectTo = route('admin.registration-requests.index');

        if ($registrationRequest->status !== StudentRegistrationRequest::STATUS_PENDING) {
            return $this->redirectWithMessage(
                $redirectTo,
                'error',
                'This registration request has already been reviewed.',
            );
        }

        $registrationRequest->update([
            'status' => StudentRegistrationRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Student registration request rejected.',
        );
    }

    private function assertStudentStillAvailable(StudentRegistrationRequest $registrationRequest): void
    {
        $studentId = $registrationRequest->student_id_number;

        if (Student::where('student_id_number', $studentId)->exists()) {
            throw new RuntimeException('A student account with this ID already exists.');
        }

        if (User::where('username', $studentId)->exists()) {
            throw new RuntimeException('A user account with this student ID already exists.');
        }

        if ($registrationRequest->email && User::where('email', $registrationRequest->email)->exists()) {
            throw new RuntimeException('A user account with this email already exists.');
        }
    }
}
