<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_designations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('display_name');
            $table->string('office_type');
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('office_type');
            $table->index('program_id');
            $table->index('year_level');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_designations');
    }
};
