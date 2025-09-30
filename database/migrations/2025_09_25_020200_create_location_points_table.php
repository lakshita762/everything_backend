<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_share_id')->constrained('location_shares')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestampTz('recorded_at');
            $table->timestamps();

            $table->index(['location_share_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_points');
    }
};