<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Business Information
            $table->string('business_name');
            $table->string('business_license')->unique()->nullable();
            $table->text('business_address')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();
            $table->string('business_website')->nullable();
            $table->text('business_description')->nullable();
            
            // Verification
            $table->boolean('is_verified')->default(false);
            $table->string('verification_document')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Tax & Registration
            $table->string('tax_id')->nullable()->unique();
            $table->string('registration_number')->nullable()->unique();
            $table->string('tin_number')->nullable();
            $table->string('vat_number')->nullable();
            
            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            
            // Statistics
            $table->integer('total_vehicles')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->integer('total_bookings')->default(0);
            $table->integer('total_employees')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            
            // Contact
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            $table->json('preferences')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('business_name');
            $table->index('business_license');
            $table->index('is_verified');
            $table->index('tax_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('owners');
    }
};