<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['part', 'service']);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('other');
            $table->decimal('cost', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'replaced'])->default('pending');
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->index('maintenance_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_items');
    }
};
