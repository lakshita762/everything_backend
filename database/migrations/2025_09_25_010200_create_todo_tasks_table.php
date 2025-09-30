<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->constrained('todo_lists')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->boolean('is_done')->default(false);
            $table->timestampTz('due_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['todo_list_id', 'is_done']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_tasks');
    }
};