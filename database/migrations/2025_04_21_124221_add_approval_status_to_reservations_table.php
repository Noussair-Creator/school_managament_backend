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
        Schema::table('reservations', function (Blueprint $table) {
            // Make teacher_id potentially nullable depending on your logic during approval
            $table->foreignId('teacher_id')->nullable()->change();

            // Status Column
            $table->string('status')->default('pending')->after('teacher_id'); // e.g., pending, approved, rejected, cancelled

            // Approval Tracking Columns
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete(); // Admin who actioned
            $table->timestamp('approved_at')->nullable()->after('approved_by'); // When it was actioned
            $table->text('rejection_reason')->nullable()->after('approved_at'); // Reason if rejected

            // Index status for faster querying
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            //
        });
    }
};
