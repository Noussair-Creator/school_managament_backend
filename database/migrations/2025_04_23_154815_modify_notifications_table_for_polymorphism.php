<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // --- Add standard polymorphic columns ---
            $table->morphs('notifiable'); // Adds `notifiable_id` (usually BIGINT UNSIGNED) and `notifiable_type` (VARCHAR)

            // --- Make user_id nullable and then drop it (or just drop it if no data needs migration) ---
            // $table->unsignedBigInteger('user_id')->nullable()->change(); // Make nullable first if migrating data
            // You might need DBAL: composer require doctrine/dbal
            // Then potentially migrate data from user_id to notifiable_id/type if needed

            $table->dropForeign(['user_id']); // Drop foreign key constraint
            $table->dropIndex('notifications_user_id_index'); // Drop index if it exists
            $table->dropIndex('notifications_user_id_read_at_index'); // Drop composite index
            $table->dropColumn('user_id'); // Drop the old column

            // --- Add index for new polymorphic columns ---
            $table->index(['notifiable_id', 'notifiable_type']);
            // Optional: Add composite index including read_at if needed for your queries
            // $table->index(['notifiable_id', 'notifiable_type', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // --- Reverse the changes ---
            $table->dropIndex('notifications_notifiable_id_notifiable_type_index');
            // $table->dropIndex('notifications_notifiable_id_notifiable_type_read_at_index'); // Drop composite if added

            $table->dropMorphs('notifiable'); // Remove notifiable_id and notifiable_type

            // --- Re-add user_id column ---
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Re-add foreign key
            $table->index('user_id');
            $table->index(['user_id', 'read_at']);
        });
    }
};
