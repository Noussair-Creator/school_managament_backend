<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Import DB facade

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Add the type column after 'capacity' for organization
            $table->string('type')->after('capacity');
        });

        // IMPORTANT: Update existing records to set their type
        DB::table('locations')->update(['type' => 'classroom']);
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
