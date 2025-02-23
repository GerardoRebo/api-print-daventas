<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deuda extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = ['ventaticket', 'cliente'];
    //one to one
    public function ventaticket()
    {
        return $this->belongsTo(Ventaticket::class);
    }
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function latestAbono(): HasOne
    {
        return $this->hasOne(Abono::class)->latestOfMany();
    }
}
