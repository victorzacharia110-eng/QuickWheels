<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('destination')->nullable()->after('pickup_location');
            $table->timestamp('scheduled_at')->nullable()->after('pickup_time');
            $table->timestamp('accepted_at')->nullable()->after('completed_at');
            $table->timestamp('en_route_at')->nullable()->after('accepted_at');
            $table->timestamp('started_at')->nullable()->after('en_route_at');
            $table->decimal('fare', 10, 2)->nullable()->after('total_amount');
            $table->foreignId('assigned_driver_id')->nullable()->after('employee_id')->constrained('employees')->onDelete('set null');
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('requested','pending','accepted','confirmed','en_route','active','in_progress','completed','cancelled') DEFAULT 'requested'");
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['destination', 'scheduled_at', 'accepted_at', 'en_route_at', 'started_at', 'fare', 'assigned_driver_id']);
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','active','completed','cancelled') DEFAULT 'pending'");
    }
};
