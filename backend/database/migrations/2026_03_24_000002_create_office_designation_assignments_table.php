<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_designation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_designation_id')
                ->constrained('office_designations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['office_designation_id', 'is_active'], 'designation_active_idx');
            $table->index(['user_id', 'is_active'], 'designation_user_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_designation_assignments');
    }
};
