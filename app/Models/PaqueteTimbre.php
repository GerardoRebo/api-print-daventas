<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaqueteTimbre extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'cantidad',
        'price',
        'discount',
        'total',
        'active',
    ];
    public function paymentIntent()
    {
        return $this->morphOne(PaymentIntent::class, 'payable');
    }
}
