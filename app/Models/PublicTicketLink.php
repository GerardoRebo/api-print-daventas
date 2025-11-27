<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicTicketLink extends Model
{
    use HasFactory;
    protected $fillable = ['ventaticket_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function ventaticket()
    {
        return $this->belongsTo(Ventaticket::class);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}
