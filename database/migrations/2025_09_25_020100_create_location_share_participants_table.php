<?php

use App\Enums\LocationParticipantRole;
use App\Enums\LocationParticipantStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_share_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_share_id')->constrained('location_shares')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('role')->default(LocationParticipantRole::VIEWER->value);
            $table->string('status')->default(LocationParticipantStatus::PENDING->value);
            $table->timestampTz('invited_at')->nullable();
            $table->timestampTz('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['location_share_id', 'user_id']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_share_participants');
    }
};