<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE clearance_requests MODIFY COLUMN status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending'"
        );

        DB::statement(
            "ALTER TABLE clearance_signatures MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE clearance_requests MODIFY COLUMN status ENUM('pending','completed') NOT NULL DEFAULT 'pending'"
        );

        DB::statement(
            "ALTER TABLE clearance_signatures MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'"
        );
    }
};
