<?php

use App\Enums\TodoListInviteStatus;
use App\Enums\TodoListRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_list_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->constrained('todo_lists')->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default(TodoListRole::VIEWER->value);
            $table->string('token')->unique();
            $table->string('status')->default(TodoListInviteStatus::PENDING->value);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('invited_at')->nullable();
            $table->timestamps();

            $table->index(['todo_list_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_list_invites');
    }
};