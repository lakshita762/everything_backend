<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id','title','is_done','category'];
    protected $casts = ['is_done'=>'boolean'];
    protected $appends = ['date'];

    public function user(){ return $this->belongsTo(User::class); }

    public function getDateAttribute()
    {
        return $this->created_at ? $this->created_at->toIso8601String() : null;
    }
}
