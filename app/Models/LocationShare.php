<?php

namespace App\Models;

use App\Enums\LocationParticipantStatus;
use App\Enums\LocationShareStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LocationShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'session_token',
        'allow_live_tracking',
        'allow_history',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'allow_live_tracking' => 'boolean',
        'allow_history' => 'boolean',
        'expires_at' => 'datetime',
        'status' => LocationShareStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $share) {
            if (!$share->session_token) {
                $share->session_token = Str::uuid()->toString();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LocationShareParticipant::class, 'location_share_id');
    }

    public function activeParticipants(): HasMany
    {
        return $this->participants()->where('status', LocationParticipantStatus::ACCEPTED->value);
    }

    public function points(): HasMany
    {
        return $this->hasMany(LocationPoint::class, 'location_share_id');
    }

    public function latestPoint(): HasOne
    {
        return $this->hasOne(LocationPoint::class, 'location_share_id')->latestOfMany('recorded_at');
    }
}