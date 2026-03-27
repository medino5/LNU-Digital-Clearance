<?php

namespace Database\Seeders;

use App\Models\OfficeAccount;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OfficeDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['username' => 'treasurer3'],
            [
                'name' => 'Pedro Reyes',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_OFFICE,
                'is_student' => false,
                'is_staff' => true,
            ]
        );

        OfficeAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => 'Pedro Reyes',
                'office_type' => OfficeAccount::TYPE_YEAR_LEVEL_TREASURER,
                'program_id' => null,
                'year_level' => 3,
            ]
        );
    }
}