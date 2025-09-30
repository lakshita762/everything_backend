<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'avatar_url',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function todoListsOwned(): HasMany
    {
        return $this->hasMany(TodoList::class, 'owner_id');
    }

    public function todoListMemberships(): HasMany
    {
        return $this->hasMany(TodoListMembership::class);
    }

    public function todoLists(): BelongsToMany
    {
        return $this->belongsToMany(TodoList::class, 'todo_list_user')
            ->using(TodoListMembership::class)
            ->withPivot(['role', 'status', 'invited_at'])
            ->withTimestamps();
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(TodoTask::class, 'assigned_to');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function locationEntries(): HasMany
    {
        return $this->hasMany(LocationEntry::class);
    }

    public function locationSharesOwned(): HasMany
    {
        return $this->hasMany(LocationShare::class, 'owner_id');
    }

    public function locationShareParticipants(): HasMany
    {
        return $this->hasMany(LocationShareParticipant::class);
    }

    public function locationShares(): BelongsToMany
    {
        return $this->belongsToMany(LocationShare::class, 'location_share_participants')
            ->withPivot(['role', 'status', 'invited_at', 'responded_at'])
            ->withTimestamps();
    }
}