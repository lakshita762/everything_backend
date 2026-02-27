<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_share_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_share_group_id')->constrained('location_share_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('role')->default('viewer');
            $table->timestamps();

            $table->unique(['location_share_group_id', 'email'], 'lsgm_group_email_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_share_group_members');
    }
};
