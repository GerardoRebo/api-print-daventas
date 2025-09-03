<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'plan_price_id',
        'name',
        'price',
        'meses',
    ];
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
