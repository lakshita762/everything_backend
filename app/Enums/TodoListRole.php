<?php

namespace App\Enums;

enum TodoListRole: string
{
    case OWNER = 'owner';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public static function editableRoles(): array
    {
        return [self::OWNER, self::EDITOR];
    }
}