<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->dropUnique('clearance_steps_clearance_id_office_account_id_unique');
            $table->dropConstrainedForeignId('office_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->foreignId('office_account_id')
                ->nullable()
                ->after('clearance_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unique(
                ['clearance_id', 'office_account_id'],
                'clearance_steps_clearance_id_office_account_id_unique'
            );
        });
    }
};
