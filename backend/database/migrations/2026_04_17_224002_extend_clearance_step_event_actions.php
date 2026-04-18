<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->rebuildActionConstraint([
            'generated',
            'approved',
            'flagged',
            'resubmitted',
            'undo_approval',
            'undo_flag',
        ]);
    }

    public function down(): void
    {
        $this->rebuildActionConstraint([
            'generated',
            'approved',
            'flagged',
            'resubmitted',
        ]);
    }

    /**
     * @param  array<int, string>  $actions
     */
    protected function rebuildActionConstraint(array $actions): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $quotedActions = implode(
                ', ',
                array_map(fn (string $action) => "'" . $action . "'", $actions)
            );

            DB::statement(
                "ALTER TABLE clearance_step_events MODIFY action ENUM($quotedActions) NOT NULL"
            );

            return;
        }

        $temporaryTable = 'clearance_step_events_temp';

        Schema::create($temporaryTable, function (Blueprint $table) use ($actions) {
            $table->id();
            $table->foreignId('clearance_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role');
            $table->enum('action', $actions);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        DB::statement(
            "INSERT INTO {$temporaryTable} (id, clearance_step_id, actor_user_id, actor_role, action, remarks, created_at, updated_at)
             SELECT id, clearance_step_id, actor_user_id, actor_role, action, remarks, created_at, updated_at
             FROM clearance_step_events"
        );

        Schema::drop('clearance_step_events');
        Schema::rename($temporaryTable, 'clearance_step_events');
    }
};
