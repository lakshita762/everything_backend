<?php

namespace App\Enums;

enum LocationParticipantStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REVOKED = 'revoked';
}