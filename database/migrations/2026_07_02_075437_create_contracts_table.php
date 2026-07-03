<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            
            // Driver/Customer info
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->string('driver_name');
            $table->string('driver_email')->nullable();
            $table->string('driver_phone')->nullable();
            
            // Vehicle info
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->string('vehicle_name');
            $table->string('vehicle_type');
            $table->string('vehicle_registration');
            
            // Contract details
            $table->enum('contract_type', ['hire_purchase', 'rental']);
            $table->enum('payment_frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            
            // Dates
            $table->date('start_date');
            $table->date('end_date');
            
            // Financials
            $table->decimal('weekly_amount', 10, 2)->default(0);
            $table->decimal('daily_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->decimal('deposit', 10, 2)->default(0);
            
            // Status
            $table->enum('status', ['active', 'pending', 'completed', 'expired', 'cancelled'])
                ->default('pending');
            
            // Metadata
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('contract_number');
            $table->index('driver_id');
            $table->index('vehicle_id');
            $table->index('status');
            $table->index('contract_type');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};