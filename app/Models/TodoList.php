<?php

namespace App\Models;

use App\Enums\TodoListMembershipStatus;
use App\Enums\TodoListRole;
use App\Enums\TodoListVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TodoList extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'visibility',
    ];

    protected $casts = [
        'visibility' => TodoListVisibility::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $list) {
            if (!$list->slug) {
                $list->slug = Str::slug($list->name . '-' . Str::random(6));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TodoTask::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TodoListMembership::class);
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'todo_list_user')
            ->using(TodoListMembership::class)
            ->withPivot(['role', 'status', 'invited_at'])
            ->withTimestamps();
    }

    public function acceptedCollaborators(): BelongsToMany
    {
        return $this->collaborators()->wherePivot('status', TodoListMembershipStatus::ACCEPTED->value);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TodoListInvite::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('owner_id', $user->id)
            ->orWhereHas('collaborators', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('todo_list_user.status', TodoListMembershipStatus::ACCEPTED->value);
            });
    }

    public function canUserManage(User $user): bool
    {
        if ($user->id === $this->owner_id) {
            return true;
        }

        $membership = $this->memberships
            ->where('user_id', $user->id)
            ->first();

        if (!$membership) {
            return false;
        }

        if ($membership->status !== TodoListMembershipStatus::ACCEPTED) {
            return false;
        }

        return in_array($membership->role, [TodoListRole::OWNER, TodoListRole::EDITOR], true);
    }
}