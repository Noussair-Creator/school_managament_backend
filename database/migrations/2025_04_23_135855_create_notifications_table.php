<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Use UUID for primary key
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The user receiving the notification
            $table->string('type'); // Type identifier (e.g., 'user_registered', 'new_message')
            $table->json('data'); // Store relevant data as JSON
            $table->timestamp('read_at')->nullable(); // Timestamp when it was read (NULL if unread)
            $table->timestamps(); // created_at and updated_at

            $table->index('user_id'); // Index for faster lookup per user
            $table->index(['user_id', 'read_at']); // Index for fetching unread per user
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
