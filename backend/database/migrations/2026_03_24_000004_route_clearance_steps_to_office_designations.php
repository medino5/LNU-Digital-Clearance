<?php

use App\Models\ClearanceStep;
use App\Support\OfficeDesignationBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->foreignId('office_designation_id')
                ->nullable()
                ->after('office_account_id')
                ->constrained('office_designations')
                ->nullOnDelete();
        });

        $backfill = new OfficeDesignationBackfill();

        ClearanceStep::query()
            ->with('officeAccount.program')
            ->whereNull('office_designation_id')
            ->orderBy('id')
            ->get()
            ->each(function (ClearanceStep $step) use ($backfill): void {
                if (!$step->officeAccount) {
                    return;
                }

                $designation = $backfill->syncOfficeAccount($step->officeAccount);

                $step->update([
                    'office_designation_id' => $designation->id,
                ]);
            });

        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->unique(
                ['clearance_id', 'office_designation_id'],
                'clearance_steps_clearance_designation_unique'
            );
            $table->index('office_designation_id');
        });
    }

    public function down(): void
    {
        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->dropUnique('clearance_steps_clearance_designation_unique');
            $table->dropIndex(['office_designation_id']);
            $table->dropConstrainedForeignId('office_designation_id');
        });
    }
};
