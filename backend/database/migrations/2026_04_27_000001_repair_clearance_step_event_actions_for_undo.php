<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $actions = [
        'generated',
        'approved',
        'flagged',
        'resubmitted',
        'undo_approval',
        'undo_flag',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('clearance_step_events')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $quotedActions = implode(
                ', ',
                array_map(fn (string $action) => "'" . $action . "'", $this->actions)
            );

            DB::statement("ALTER TABLE clearance_step_events MODIFY action ENUM($quotedActions) NOT NULL");

            return;
        }

        $this->rebuildActionColumn();
    }

    public function down(): void
    {
        // Undo actions are now part of the runtime contract, so this repair is intentionally not reversed.
    }

    protected function rebuildActionColumn(): void
    {
        $temporaryTable = 'clearance_step_events_repair_temp';

        Schema::dropIfExists($temporaryTable);

        Schema::create($temporaryTable, function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role');
            $table->enum('action', $this->actions);
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
