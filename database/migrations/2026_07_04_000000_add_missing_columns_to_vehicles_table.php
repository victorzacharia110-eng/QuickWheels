<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'make')) {
                $table->string('make')->nullable()->after('name');
            }
            if (!Schema::hasColumn('vehicles', 'model')) {
                $table->string('model')->nullable()->after('make');
            }
            if (!Schema::hasColumn('vehicles', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('registration');
            }
            if (!Schema::hasColumn('vehicles', 'color')) {
                $table->string('color')->nullable()->after('registration_number');
            }
            if (!Schema::hasColumn('vehicles', 'mileage')) {
                $table->decimal('mileage', 12, 2)->nullable()->after('color');
            }
            if (!Schema::hasColumn('vehicles', 'fuel_type')) {
                $table->string('fuel_type')->nullable()->after('mileage');
            }
            if (!Schema::hasColumn('vehicles', 'transmission')) {
                $table->string('transmission')->nullable()->after('fuel_type');
            }
            if (!Schema::hasColumn('vehicles', 'seats')) {
                $table->integer('seats')->nullable()->after('transmission');
            }
            if (!Schema::hasColumn('vehicles', 'daily_rate')) {
                $table->decimal('daily_rate', 12, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('vehicles', 'weekly_rate')) {
                $table->decimal('weekly_rate', 12, 2)->nullable()->after('daily_rate');
            }
            if (!Schema::hasColumn('vehicles', 'monthly_rate')) {
                $table->decimal('monthly_rate', 12, 2)->nullable()->after('weekly_rate');
            }
            if (!Schema::hasColumn('vehicles', 'insurance_required')) {
                $table->boolean('insurance_required')->default(false)->after('monthly_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'make', 'model', 'registration_number', 'color', 'mileage',
                'fuel_type', 'transmission', 'seats', 'daily_rate',
                'weekly_rate', 'monthly_rate', 'insurance_required',
            ]);
        });
    }
};
