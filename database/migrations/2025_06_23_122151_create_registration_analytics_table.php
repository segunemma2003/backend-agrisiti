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
        Schema::create('registration_analytics', function (Blueprint $table) {
            $table->id();
             $table->date('date')->unique()->index();
            $table->unsignedInteger('total_registrations')->default(0);
            $table->unsignedInteger('verified_registrations')->default(0);
            $table->unsignedInteger('contacted_registrations')->default(0);
            $table->json('top_interests')->nullable();
            $table->json('top_locations')->nullable();
            $table->json('experience_breakdown')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_analytics');
    }
};
