<?php

use App\Enums\TodoListMembershipStatus;
use App\Enums\TodoListRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_list_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->constrained('todo_lists')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default(TodoListRole::VIEWER->value);
            $table->string('status')->default(TodoListMembershipStatus::PENDING->value);
            $table->timestampTz('invited_at')->nullable();
            $table->timestamps();

            $table->unique(['todo_list_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_list_user');
    }
};