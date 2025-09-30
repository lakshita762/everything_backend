<?php

namespace App\Enums;

enum LocationParticipantRole: string
{
    case VIEWER = 'viewer';
    case TRACKER = 'tracker';
}