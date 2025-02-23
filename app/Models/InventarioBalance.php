<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioBalance extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $with = ['almacen'];

    //relacion uno a muchos inversa
    
    public function product(){

        return $this->belongsTo('App\Models\Product');
    }

    public function almacen(){

        return $this->belongsTo('App\Models\Almacen');
    }
}
