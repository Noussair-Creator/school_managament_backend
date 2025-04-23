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
        Schema::dropIfExists('database_notifications');
    }
    // The down() method can be empty or recreate the table if needed for rollback
    public function down(): void
    {
        // Schema::create('database_notifications', function (Blueprint $table) { ... }); // Optional rollback
    }
};
