@extends('layouts.portal', [
    'title' => 'Routing',
    'subtitle' => 'Assign current holders to each active designation.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))

    <div class="admin-page">
        @include('admin.partials.page-feedback')

        @include('admin.partials.designation-routing', [
            'designations' => $designations,
            'activeFormKey' => $activeFormKey,
        ])
    </div>
@endsection



