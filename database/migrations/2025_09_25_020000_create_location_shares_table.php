<?php

use App\Enums\LocationShareStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('session_token')->unique();
            $table->boolean('allow_live_tracking')->default(true);
            $table->boolean('allow_history')->default(true);
            $table->string('status')->default(LocationShareStatus::ACTIVE->value);
            $table->timestampTz('expires_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_shares');
    }
};