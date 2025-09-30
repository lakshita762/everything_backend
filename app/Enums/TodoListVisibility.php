<?php

namespace App\Enums;

enum TodoListVisibility: string
{
    case PRIVATE = 'private';
    case SHARED = 'shared';
    case PUBLIC = 'public';
}