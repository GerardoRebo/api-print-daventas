<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreFacturaArticuloTax extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected function base(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    protected function importe(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
}
