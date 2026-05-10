<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('remember_token');
            $table->string('profile_photo_mime_type', 80)->nullable()->after('profile_photo_path');
            $table->longText('profile_photo_content')->nullable()->after('profile_photo_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_path',
                'profile_photo_mime_type',
                'profile_photo_content',
            ]);
        });
    }
};
