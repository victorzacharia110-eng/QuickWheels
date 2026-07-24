<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('completed_at');
            $table->timestamp('viewed_at')->nullable()->after('submitted_at');
            $table->timestamp('processing_at')->nullable()->after('viewed_at');
            $table->timestamp('confirmed_at')->nullable()->after('processing_at');
            $table->timestamp('verified_at')->nullable()->after('confirmed_at');
            $table->text('technician_signature')->nullable()->after('notes');
            $table->timestamp('technician_signed_at')->nullable()->after('technician_signature');
            $table->text('owner_signature')->nullable()->after('technician_signed_at');
            $table->timestamp('owner_signed_at')->nullable()->after('owner_signature');
        });

        DB::statement("ALTER TABLE maintenances MODIFY COLUMN status ENUM('pending','submitted','viewed','processing','confirmed','verified','completed','cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE maintenances MODIFY COLUMN status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending'");

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn([
                'submitted_at', 'viewed_at', 'processing_at', 'confirmed_at', 'verified_at',
                'technician_signature', 'technician_signed_at', 'owner_signature', 'owner_signed_at',
            ]);
        });
    }
};
