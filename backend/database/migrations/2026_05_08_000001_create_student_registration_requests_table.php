<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_registration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('student_id_number', 7);
            $table->string('first_name', 60);
            $table->string('middle_initial', 1)->nullable();
            $table->string('last_name', 60);
            $table->string('name_extension', 12)->nullable();
            $table->string('email', 120)->nullable();
            $table->foreignId('program_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->string('password');
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id_number', 'status']);
            $table->index(['program_id', 'year_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_registration_requests');
    }
};
