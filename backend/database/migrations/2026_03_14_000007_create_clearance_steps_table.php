<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_account_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['awaiting_action', 'approved', 'flagged'])->default('awaiting_action');
            $table->text('remarks')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('office_label');
            $table->string('office_type');
            $table->string('scope_label')->nullable();
            $table->timestamps();

            $table->unique(['clearance_id', 'office_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_steps');
    }
};
