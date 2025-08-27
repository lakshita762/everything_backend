<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationEntry extends Model
{
    protected $fillable = ['title','latitude','longitude'];
    public function user(){ return $this->belongsTo(User::class); }
}

