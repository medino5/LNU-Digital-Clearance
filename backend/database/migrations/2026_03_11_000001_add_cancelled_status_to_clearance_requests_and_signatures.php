<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE clearance_requests MODIFY COLUMN status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending'"
        );

        DB::statement(
            "ALTER TABLE clearance_signatures MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE clearance_requests MODIFY COLUMN status ENUM('pending','completed') NOT NULL DEFAULT 'pending'"
        );

        DB::statement(
            "ALTER TABLE clearance_signatures MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'"
        );
    }
};
