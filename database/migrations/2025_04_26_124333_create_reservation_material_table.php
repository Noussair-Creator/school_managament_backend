<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationMaterialTable extends Migration
{
    public function up()
    {
        Schema::create('reservation_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->integer('quantity_requested');
            $table->timestamps();

            // Optional: If you want to add unique constraint to prevent duplicate material entries for the same reservation
            $table->unique(['reservation_id', 'material_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservation_material');
    }
}
