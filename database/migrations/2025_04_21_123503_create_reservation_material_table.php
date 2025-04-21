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
        Schema::create('reservation_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete(); // If reservation deleted, link removed
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete(); // If material deleted, link removed
            $table->integer('quantity_requested')->default(1); // How many units are needed
            // $table->integer('quantity_approved')->nullable(); // Optional: If approval differs from request
            $table->timestamps(); // Optional: track when link was made

            // Ensure a reservation cannot have the same material listed twice
            $table->unique(['reservation_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_material');
    }
};
