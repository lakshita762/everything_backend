<?php

namespace App\Models;

use App\Enums\TodoListInviteStatus;
use App\Enums\TodoListRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TodoListInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'email',
        'role',
        'token',
        'status',
        'expires_at',
        'invited_at',
    ];

    protected $casts = [
        'role' => TodoListRole::class,
        'status' => TodoListInviteStatus::class,
        'expires_at' => 'datetime',
        'invited_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invite) {
            if (!$invite->token) {
                $invite->token = Str::uuid()->toString();
            }

            if (!$invite->invited_at) {
                $invite->invited_at = now();
            }
        });
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(TodoList::class, 'todo_list_id');
    }
}