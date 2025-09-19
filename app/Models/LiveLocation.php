<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveLocation extends Model
{
    protected $table = 'live_locations';
    public $timestamps = false;
    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'session_id',
        'latitude',
        'longitude',
        'accuracy',
        'timestamp',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'integer',
        'timestamp' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(LiveSession::class, 'session_id', 'session_id');
    }
}
