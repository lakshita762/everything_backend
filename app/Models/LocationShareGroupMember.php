<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationShareGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_share_group_id',
        'user_id',
        'email',
        'role',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LocationShareGroup::class, 'location_share_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
