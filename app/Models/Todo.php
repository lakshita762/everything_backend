<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasFactory;
    
    protected $fillable = ['title','is_done','category'];
    protected $casts = ['is_done'=>'boolean'];
    public function user(){ return $this->belongsTo(User::class); }
}
