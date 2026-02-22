<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->enum('type', [
                'academic',
                'non_academic',
                'year_level',
                'global'
            ]);

            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('year_level')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};