<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasRejectionReason = Schema::hasColumn('clearance_signatures', 'rejection_reason');
        $hasRemarks = Schema::hasColumn('clearance_signatures', 'remarks');

        if ($hasRejectionReason && $hasRemarks) {
            return;
        }

        Schema::table('clearance_signatures', function (Blueprint $table) use ($hasRejectionReason, $hasRemarks) {
            if (!$hasRejectionReason) {
                $table->text('rejection_reason')->nullable()->after('status');
            }

            if (!$hasRemarks) {
                $table->text('remarks')
                    ->nullable()
                    ->after($hasRejectionReason ? 'status' : 'rejection_reason');
            }
        });
    }

    public function down(): void
    {
        $hasRejectionReason = Schema::hasColumn('clearance_signatures', 'rejection_reason');
        $hasRemarks = Schema::hasColumn('clearance_signatures', 'remarks');

        if (!$hasRejectionReason && !$hasRemarks) {
            return;
        }

        Schema::table('clearance_signatures', function (Blueprint $table) use ($hasRejectionReason, $hasRemarks) {
            if ($hasRejectionReason) {
                $table->dropColumn('rejection_reason');
            }

            if ($hasRemarks) {
                $table->dropColumn('remarks');
            }
        });
    }
};
