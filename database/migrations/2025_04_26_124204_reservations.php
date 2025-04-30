<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Reservation; // Import the model to use constants

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->comment('Teacher making the reservation');
            $table->foreignId('location_id')->constrained('locations')->comment('Requested room/laboratory');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->text('purpose')->nullable();
            $table->string('status')->default(Reservation::STATUS_PENDING); // Default status
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('Lab Manager who actioned');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            // Add indexes for performance
            $table->index('status');
            $table->index('user_id');
            $table->index('approved_by');
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
