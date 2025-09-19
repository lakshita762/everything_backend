<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('live_locations', function (Blueprint $table) {
            $table->uuid('session_id')->primary();
            $table->foreign('session_id')->references('session_id')->on('live_sessions')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('accuracy')->nullable();
            $table->timestamp('timestamp')->useCurrent();
        });

        // optional history table for append-only storage
        Schema::create('live_location_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('session_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('accuracy')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->index('session_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_location_history');
        Schema::dropIfExists('live_locations');
    }
};
