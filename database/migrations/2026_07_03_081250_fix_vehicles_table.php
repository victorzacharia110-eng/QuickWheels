<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('type')->nullable()->after('name');
            $table->string('registration')->nullable()->after('type');
            $table->year('year')->nullable()->after('registration');
            $table->decimal('price', 12, 2)->default(0)->after('year');
            $table->string('status')->default('available')->after('price');
            $table->text('description')->nullable()->after('status');
            $table->string('image')->nullable()->after('description');
            $table->json('tags')->nullable()->after('image');
            $table->boolean('is_active')->default(true)->after('tags');

            $table->foreignId('owner_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');

            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'type', 'registration', 'year', 'price', 'status',
                'description', 'image', 'tags', 'is_active', 'owner_id',
                'employee_id', 'deleted_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
