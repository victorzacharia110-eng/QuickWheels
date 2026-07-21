<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_id')->nullable()->constrained('owners')->onDelete('set null');
            $table->string('document_type'); // contract, license, nida, insurance, other
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name'); // original filename
            $table->string('file_mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->json('ai_analysis')->nullable(); // Gemini AI parsed data
            $table->timestamp('ai_analyzed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // for expiring documents
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'document_type']);
            $table->index(['owner_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
