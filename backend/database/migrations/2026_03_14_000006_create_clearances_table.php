<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['in_progress', 'flagged', 'completed'])->default('in_progress');
            $table->string('reference_number')->nullable()->unique();
            $table->timestamp('completed_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('student_name');
            $table->string('student_id_number');
            $table->unsignedTinyInteger('year_level');
            $table->string('program_code');
            $table->string('program_name');
            $table->string('organization_name');
            $table->string('semester_label');
            $table->timestamps();

            $table->unique(['student_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearances');
    }
};
