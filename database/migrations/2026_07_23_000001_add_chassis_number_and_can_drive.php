<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('chassis_number')->nullable()->after('color');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_drive')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('chassis_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_drive');
        });
    }
};
