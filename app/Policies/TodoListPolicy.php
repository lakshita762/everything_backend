<?php

namespace App\Policies;

use App\Enums\TodoListMembershipStatus;
use App\Enums\TodoListRole;
use App\Models\TodoList;
use App\Models\User;

class TodoListPolicy
{
    public function view(User $user, TodoList $list): bool
    {
        if ($user->id === $list->owner_id) {
            return true;
        }

        return $list->memberships()
            ->where('user_id', $user->id)
            ->where('status', TodoListMembershipStatus::ACCEPTED->value)
            ->exists();
    }

    public function update(User $user, TodoList $list): bool
    {
        if ($user->id === $list->owner_id) {
            return true;
        }

        return $list->memberships()
            ->where('user_id', $user->id)
            ->where('status', TodoListMembershipStatus::ACCEPTED->value)
            ->whereIn('role', [
                TodoListRole::OWNER->value,
                TodoListRole::EDITOR->value,
            ])
            ->exists();
    }

    public function delete(User $user, TodoList $list): bool
    {
        return $user->id === $list->owner_id;
    }

    public function manageMembers(User $user, TodoList $list): bool
    {
        return $user->id === $list->owner_id;
    }

    public function manageTasks(User $user, TodoList $list): bool
    {
        if ($user->id === $list->owner_id) {
            return true;
        }

        return $list->memberships()
            ->where('user_id', $user->id)
            ->where('status', TodoListMembershipStatus::ACCEPTED->value)
            ->whereIn('role', [
                TodoListRole::OWNER->value,
                TodoListRole::EDITOR->value,
            ])
            ->exists();
    }
}