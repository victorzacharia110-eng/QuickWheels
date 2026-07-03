<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // Booking Identification
            $table->string('booking_number')->unique();
            
            // Relationships
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            
            // Dates & Times
            $table->date('start_date');
            $table->date('end_date');
            $table->time('pickup_time')->nullable();
            $table->time('return_time')->nullable();
            
            // Locations
            $table->string('pickup_location')->nullable();
            $table->string('return_location')->nullable();
            $table->string('delivery_address')->nullable();
            
            // Financials
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('deposit_paid', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('daily_rate', 10, 2)->nullable();
            $table->decimal('weekly_rate', 10, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->string('discount_code')->nullable();
            $table->decimal('late_fee', 10, 2)->nullable();
            $table->decimal('cleaning_fee', 10, 2)->nullable();
            $table->decimal('insurance_fee', 10, 2)->nullable();
            
            // Status
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])
                ->default('pending');
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Driver Info
            $table->string('driver_name')->nullable();
            $table->string('driver_license')->nullable();
            $table->integer('driver_age')->nullable();
            $table->string('driver_phone')->nullable();
            
            // Vehicle Options
            $table->boolean('is_driver_required')->default(false);
            $table->boolean('is_delivery_required')->default(false);
            $table->boolean('is_insurance_included')->default(false);
            $table->boolean('is_contract_signed')->default(false);
            
            // Payment Info
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('transaction_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('booking_number');
            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};