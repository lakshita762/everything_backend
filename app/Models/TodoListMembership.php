<?php

namespace App\Models;

use App\Enums\TodoListMembershipStatus;
use App\Enums\TodoListRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TodoListMembership extends Pivot
{
    protected $table = 'todo_list_user';

    public $incrementing = true;

    protected $fillable = [
        'todo_list_id',
        'user_id',
        'role',
        'status',
        'invited_at',
    ];

    protected $casts = [
        'role' => TodoListRole::class,
        'status' => TodoListMembershipStatus::class,
        'invited_at' => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'todo_list_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}