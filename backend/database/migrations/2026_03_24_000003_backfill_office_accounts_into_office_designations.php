<?php

use App\Support\OfficeDesignationBackfill;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new OfficeDesignationBackfill())->run();
    }

    public function down(): void
    {
        // Intentionally left blank so we do not remove current designation assignments.
    }
};
