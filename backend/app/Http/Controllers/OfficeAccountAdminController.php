<?php

namespace App\Http\Controllers;

use App\Models\OfficeAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OfficeAccountAdminController extends Controller
{
    public function index(Request $request)
    {
        $officeSearch = trim((string) $request->query('office_search', ''));
        $officeProgramId = $request->query('office_program');
        $officeType = $request->query('office_type');

        $officeAccountsQuery = OfficeAccount::query()
            ->with(['user', 'program']);

        if ($officeSearch !== '') {
            $officeSearchLike = '%' . $officeSearch . '%';

            $officeAccountsQuery->where(function ($query) use ($officeSearchLike, $officeSearch) {
                $query->where('display_name', 'like', $officeSearchLike)
                    ->orWhereHas('user', function ($userQuery) use ($officeSearchLike) {
                        $userQuery->where('username', 'like', $officeSearchLike);
                    })
                    ->orWhereHas('program', function ($programQuery) use ($officeSearchLike) {
                        $programQuery->where('code', 'like', $officeSearchLike)
                            ->orWhere('name', 'like', $officeSearchLike)
                            ->orWhere('org_name', 'like', $officeSearchLike);
                    });

                $normalizedSearch = strtolower($officeSearch);

                foreach (OfficeAccount::typeOptions() as $value => $label) {
                    if (str_contains(strtolower($label), $normalizedSearch) || str_contains(strtolower($value), $normalizedSearch)) {
                        $query->orWhere('office_type', $value);
                    }
                }

                if (str_contains($normalizedSearch, 'year')) {
                    preg_match('/([1-4])/', $normalizedSearch, $matches);

                    if (! empty($matches[1])) {
                        $query->orWhere('year_level', (int) $matches[1]);
                    }
                }

                if (in_array($normalizedSearch, ['university', 'university-wide', 'all'], true)) {
                    $query->orWhere(function ($scopeQuery) {
                        $scopeQuery->whereNull('program_id')
                            ->whereNull('year_level');
                    });
                }
            });
        }

        if ($officeProgramId !== null && $officeProgramId !== '') {
            if ($officeProgramId === 'university') {
                $officeAccountsQuery->whereNull('program_id');
            } else {
                $officeAccountsQuery->where('program_id', $officeProgramId);
            }
        }

        if ($officeType !== null && $officeType !== '') {
            $officeAccountsQuery->where('office_type', $officeType);
        }

        $officeAccounts = $officeAccountsQuery
            ->orderBy('display_name')
            ->get();

        return view('admin.office-accounts', [
            'programs' => \App\Models\Program::orderBy('code')->get(),
            'officeAccounts' => $officeAccounts,
            'officeTypeOptions' => OfficeAccount::typeOptions(),
            'officeAccountTypeOptions' => OfficeAccount::formTypeOptions(),
            'officeAccountEditTypeOptions' => $officeAccounts
                ->mapWithKeys(fn (OfficeAccount $officeAccount) => [
                    $officeAccount->id => OfficeAccount::formTypeOptions($officeAccount),
                ]),
            'yearLevels' => [1, 2, 3, 4],
            'officeTypeScopeMetadata' => OfficeAccount::scopeMetadata(),
            'officeSearch' => $officeSearch,
            'officeProgramId' => $officeProgramId,
            'selectedOfficeType' => $officeType,
            'hasOfficeAccounts' => $officeAccounts->isNotEmpty(),
        ]);
    }

    public function store(Request $request)
    {
        $redirectTo = route('admin.office-accounts.index');

        $data = $this->validateOfficeAccount(
            $request,
            'officeAccountCreate',
            $redirectTo,
        );

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

            OfficeAccount::create([
                'user_id' => $user->id,
                'display_name' => $data['display_name'],
                'office_type' => $data['office_type'],
                'program_id' => $data['program_id'],
                'year_level' => $data['year_level'],
            ]);
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Office account created successfully.',
        );
    }

    public function update(Request $request, OfficeAccount $officeAccount)
    {
        $redirectTo = route('admin.office-accounts.index');

        $data = $this->validateOfficeAccount(
            $request,
            'officeAccountUpdate',
            $redirectTo,
            $officeAccount,
        );

        DB::transaction(function () use ($data, $officeAccount) {
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
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Office account updated successfully.',
        );
    }

    protected function validateOfficeAccount(
        Request $request,
        string $errorBag,
        string $redirectTo,
        ?OfficeAccount $officeAccount = null,
    ): array
    {
        $officeTypes = array_keys(OfficeAccount::formTypeOptions($officeAccount));

        $data = $this->validateForm(
            $request,
            $errorBag,
            [
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
            ],
            $redirectTo,
        );

        $data['display_name'] = trim($data['display_name']);

        $data['program_id'] = OfficeAccount::requiresProgramScopeForType($data['office_type'])
            ? $data['program_id']
            : null;

        $data['year_level'] = OfficeAccount::requiresYearLevelScopeForType($data['office_type'])
            ? $data['year_level']
            : null;

        if (OfficeAccount::requiresProgramScopeForType($data['office_type']) && !$data['program_id']) {
            throw $this->formValidationException(
                ['program_id' => 'Program scope is required for this office type.'],
                $errorBag,
                $redirectTo,
            );
        }

        if (OfficeAccount::requiresYearLevelScopeForType($data['office_type']) && !$data['year_level']) {
            throw $this->formValidationException(
                ['year_level' => 'Year level scope is required for this office type.'],
                $errorBag,
                $redirectTo,
            );
        }

        return $data;
    }
}
