<?php

use App\Enums\TodoListVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('visibility')->default(TodoListVisibility::PRIVATE->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_lists');
    }
};