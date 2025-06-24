<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_registrations', function (Blueprint $table) {
           $table->uuid('id')->primary();

            // Personal Information with indexes
            $table->string('first_name', 100)->index();
            $table->string('last_name', 100)->index();
            $table->string('email')->unique()->index();
            $table->string('phone', 20)->index();
            $table->string('location', 200)->index();

            // Experience and Interests
            $table->enum('experience_level', [
                'beginner',
                'intermediate',
                'advanced',
                'professional'
            ])->index();
            $table->json('interests')->nullable();
            $table->text('motivation')->nullable();

            // Tracking Information
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Status Fields
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->boolean('is_contacted')->default(false)->index();

            // Laravel 11 Timestamps
            $table->timestamps();

            // Composite Indexes for better query performance
            $table->index(['created_at', 'is_active'], 'idx_created_active');
            $table->index(['experience_level', 'location'], 'idx_exp_location');
            $table->index(['first_name', 'last_name'], 'idx_full_name');
            $table->index(['email', 'is_verified'], 'idx_email_verified');
            $table->index(['is_active', 'is_contacted'], 'idx_active_contacted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_registrations');
    }
};
