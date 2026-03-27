<?php

namespace App\Http\Controllers;

use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\User;
use App\Support\OfficeDesignationBackfill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeAccountAdminController extends Controller
{
    public function __construct(
        protected OfficeDesignationBackfill $designationBackfill,
    ) {
    }

    public function store(Request $request)
    {
        $data = $this->validateOfficeAccount($request);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['display_name'],
                'username' => $data['username'],
                'email' => null,
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_OFFICE,
                'is_student' => false,
                'is_staff' => true,
            ]);

            $officeAccount = OfficeAccount::create([
                'user_id' => $user->id,
                'display_name' => $data['display_name'],
                'office_type' => $data['office_type'],
                'program_id' => $data['program_id'],
                'year_level' => $data['year_level'],
            ]);

            $this->designationBackfill->syncOfficeAccount($officeAccount->load('program'));
        });

        return back()->with('success', 'Office account created successfully.');
    }

    public function update(Request $request, OfficeAccount $officeAccount)
    {
        $data = $this->validateOfficeAccount($request, $officeAccount);

        DB::transaction(function () use ($data, $officeAccount) {
            $officeAccount->loadMissing('program');
            $previousDesignationKey = $this->designationBackfill->keyForOfficeAccount($officeAccount);

            $officeAccount->user->update([
                'name' => $data['display_name'],
                'username' => $data['username'],
                'password' => !empty($data['password'])
                    ? Hash::make($data['password'])
                    : $officeAccount->user->password,
                'role' => User::ROLE_OFFICE,
                'is_student' => false,
                'is_staff' => true,
            ]);

            $officeAccount->update([
                'display_name' => $data['display_name'],
                'office_type' => $data['office_type'],
                'program_id' => $data['program_id'],
                'year_level' => $data['year_level'],
            ]);

            $officeAccount->refresh()->load('program');
            $designation = $this->designationBackfill->syncOfficeAccount($officeAccount);

            if ($previousDesignationKey !== $designation->key) {
                $previousDesignation = OfficeDesignation::query()
                    ->where('key', $previousDesignationKey)
                    ->first();

                if ($previousDesignation) {
                    OfficeDesignationAssignment::query()
                        ->where('office_designation_id', $previousDesignation->id)
                        ->where('user_id', $officeAccount->user_id)
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'released_at' => now(),
                        ]);
                }
            }
        });

        return back()->with('success', 'Office account updated successfully.');
    }

    protected function validateOfficeAccount(Request $request, ?OfficeAccount $officeAccount = null): array
    {
        $officeTypes = array_keys(OfficeAccount::typeOptions());

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'office_type' => ['required', Rule::in($officeTypes)],
            'program_id' => ['nullable', 'exists:programs,id'],
            'year_level' => ['nullable', 'integer', 'between:1,4'],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($officeAccount?->user_id),
            ],
            'password' => [$officeAccount ? 'nullable' : 'required', 'string', 'min:8'],
        ]);

        $data['program_id'] = in_array($data['office_type'], [
            OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            OfficeAccount::TYPE_ACAD_ORG_ADVISER,
        ], true) ? $data['program_id'] : null;

        $data['year_level'] = $data['office_type'] === OfficeAccount::TYPE_YEAR_LEVEL_TREASURER
            ? $data['year_level']
            : null;

        if (in_array($data['office_type'], [
            OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            OfficeAccount::TYPE_ACAD_ORG_ADVISER,
        ], true) && !$data['program_id']) {
            throw ValidationException::withMessages([
                'program_id' => 'Program scope is required for this office type.',
            ]);
        }

        if ($data['office_type'] === OfficeAccount::TYPE_YEAR_LEVEL_TREASURER && !$data['year_level']) {
            throw ValidationException::withMessages([
                'year_level' => 'Year level scope is required for this office type.',
            ]);
        }

        return $data;
    }
}
