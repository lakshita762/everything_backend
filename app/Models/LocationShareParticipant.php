<?php

namespace App\Models;

use App\Enums\LocationParticipantRole;
use App\Enums\LocationParticipantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationShareParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_share_id',
        'user_id',
        'email',
        'role',
        'status',
        'invited_at',
        'responded_at',
    ];

    protected $casts = [
        'role' => LocationParticipantRole::class,
        'status' => LocationParticipantStatus::class,
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function share(): BelongsTo
    {
        return $this->belongsTo(LocationShare::class, 'location_share_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}