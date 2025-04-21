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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            // --- Polymorphic Relationship to Location ---
            // $table->unsignedBigInteger('reservable_id'); // ID of the reserved Location
            // $table->string('reservable_type');          // Class name ('App\Models\Location')
            // COMBINED & INDEXED: Use the morphs helper
            $table->morphs('reservable'); // Creates reservable_id (unsignedBigInt) and reservable_type (string) and indexes them

            // --- User who MADE the reservation (Lab Manager) ---
            // Foreign key to the users table. Assumes Lab Manager's user record is required.
            $table->foreignId('user_id')
                ->comment('ID of the User (Lab Manager) who created the reservation') // Corrected comment
                ->constrained('users') // Links to 'id' on 'users' table
                ->cascadeOnDelete(); // If the Lab Manager user is deleted, cascade delete their reservations. (Consider restrict or set null if needed)

            // --- Teacher ASSIGNED to the reservation (Optional?) ---
            // Foreign key to the users table for the teacher using the slot.
            $table->foreignId('teacher_id')
                ->comment('ID of the User (Teacher) assigned to this reservation slot')
                ->nullable() // Make it nullable if a teacher isn't always assigned
                ->constrained('users') // Links to 'id' on 'users' table
                ->nullOnDelete(); // If the Teacher user is deleted, set teacher_id to NULL in this reservation.

            // --- Reservation Time ---
            // Using dateTime for better timezone handling, but timestamp is also okay.
            $table->dateTime('start_time'); // Reservation start date and time
            $table->dateTime('end_time');   // Reservation end date and time

            $table->timestamps(); // created_at and updated_at

            // --- Optional: Additional Indexes for Performance ---
            // Indexing start/end times is crucial for availability checks.
            $table->index(['start_time', 'end_time']);
            // $table->index('user_id'); // Included in foreignId usually, but explicit doesn't hurt
            // $table->index('teacher_id'); // Included in foreignId usually
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
