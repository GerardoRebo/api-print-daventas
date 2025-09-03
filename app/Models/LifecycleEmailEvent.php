<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LifecycleEmailEvent extends Model
{
    use HasFactory;
    use HasFactory;


    protected $fillable = [
        'user_id',
        'stage',
        'sent_at',
    ];


    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
