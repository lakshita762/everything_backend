<?php

namespace App\Enums;

enum TodoListMembershipStatus: string
{
    case ACCEPTED = 'accepted';
    case PENDING = 'pending';
}