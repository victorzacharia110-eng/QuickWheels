<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('nida_number')->nullable()->unique();
            $table->string('license_number')->nullable()->unique();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])
                ->default('active');
            
            // Dates
            $table->date('joined_date')->nullable();
            $table->date('hire_date')->nullable();
            
            // Vehicle Assignment
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null');
            $table->string('vehicle_name')->nullable();
            
            // Employee Details
            $table->string('employee_id')->nullable()->unique();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('shift')->nullable();
            $table->json('permissions')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('profile_image')->nullable();
            
            // Relationships
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('owner_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employee_id');
            $table->index('status');
            $table->index('department');
            $table->index('vehicle_id');
            $table->index(['status', 'vehicle_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};