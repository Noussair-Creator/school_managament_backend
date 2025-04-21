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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "Projector Model X", "Microscope Type B"
            $table->text('description')->nullable();
            // $table->string('identifier')->nullable()->unique(); // Optional: SKU or asset tag
            $table->integer('quantity_available')->default(0); // Add ONLY if tracking inventory stock levels
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Who added this material entry
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
