<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductConsumible extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $with = ['consumible'];

    //relacion uno a muchos inversa

    public function product()
    {

        return $this->belongsTo('App\Models\Product');
    }

    public function consumible()
    {

        return $this->belongsTo('App\Models\Product');
    }
}
