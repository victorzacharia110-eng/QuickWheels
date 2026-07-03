<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('customer')->after('phone');
            $table->string('nida_number')->nullable()->unique()->after('role');
            $table->string('profile_image')->nullable()->after('nida_number');
            $table->boolean('is_active')->default(true)->after('profile_image');
            $table->timestamp('last_login')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'nida_number', 'profile_image', 'is_active', 'last_login']);
        });
    }
};
