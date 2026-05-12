<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->index(
                ['office_designation_id', 'status', 'signed_at'],
                'clearance_steps_office_status_signed_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('clearance_steps', function (Blueprint $table) {
            $table->dropIndex('clearance_steps_office_status_signed_idx');
        });
    }
};
