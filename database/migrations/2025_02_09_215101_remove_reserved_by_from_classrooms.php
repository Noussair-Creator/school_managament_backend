<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

     public function up()
     {
         Schema::table('classrooms', function (Blueprint $table) {
             $table->dropForeign(['reserved_by']); // Drop foreign key
             $table->dropColumn('reserved_by'); // Drop column
         });
     }
    /**
     * Reverse the migrations.
     */

     public function down()
     {
         Schema::table('classrooms', function (Blueprint $table) {
             $table->unsignedBigInteger('reserved_by')->nullable();
             $table->foreign('reserved_by')->references('id')->on('users')->onDelete('set null');
         });
     }
};
