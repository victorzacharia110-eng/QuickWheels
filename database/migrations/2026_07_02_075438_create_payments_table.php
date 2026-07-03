<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('owner_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Driver info (denormalized for quick access)
            $table->string('driver_name')->nullable();
            
            // Payment details
            $table->decimal('amount', 10, 2);
            $table->string('method')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_type')->nullable();
            $table->enum('status', ['paid', 'pending', 'failed', 'completed', 'cancelled', 'refunded'])
                ->default('pending');
            
            // Dates
            $table->date('date')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // References
            $table->string('transaction_id')->nullable()->unique();
            $table->string('reference_number')->nullable();
            $table->string('receipt_number')->nullable()->unique();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('contract_id');
            $table->index('booking_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('date');
            $table->index('method');
            $table->index('transaction_id');
            $table->index('receipt_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};