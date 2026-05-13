@extends('layouts.portal', [
    'title' => 'Student Profile',
    'subtitle' => 'Review student information, clearance progress, and clearance history.',
])

@section('page')
    <div class="admin-page management-page student-profile-page-shell">
        <a href="{{ $backUrl }}" class="student-profile-back-link">← {{ $backLabel }}</a>

        <section class="admin-page-header management-header">
            <div>
                <h1>Student Profile</h1>
                <p>Review clearance progress, academic details, and historical clearance records.</p>
            </div>
        </section>

        @include('students.partials.profile-panel')
    </div>

    @include('students.partials.profile-styles')
@endsection



