<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('workshop_address')->nullable()->after('address');
            $table->decimal('workshop_latitude', 10, 7)->nullable()->after('workshop_address');
            $table->decimal('workshop_longitude', 10, 7)->nullable()->after('workshop_latitude');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('next_service_date')->nullable()->after('mileage');
            $table->text('next_service_notes')->nullable()->after('next_service_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['workshop_address', 'workshop_latitude', 'workshop_longitude']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['next_service_date', 'next_service_notes']);
        });
    }
};
