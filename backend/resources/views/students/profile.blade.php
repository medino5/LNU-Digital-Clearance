@extends('layouts.portal', [
    'title' => 'Student Profile',
    'subtitle' => 'Review student information, clearance progress, and clearance history.',
])

@section('page')
    <div class="admin-page management-page">
        <section class="admin-page-header management-header">
            <div>
                <h1>STUDENT PROFILE</h1>
                <p>Review clearance progress, academic details, and historical clearance records.</p>
            </div>

            <a href="{{ $backUrl }}" class="button secondary">{{ $backLabel }}</a>
        </section>

        @include('students.partials.profile-panel')
    </div>

    @include('students.partials.profile-styles')
@endsection
