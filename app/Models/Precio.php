<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Precio extends Model
{
    use HasFactory;
    protected $guarded=[];

    //relacion uno a muchos inversa
    
    public function product(){

        return $this->belongsTo('App\Models\Product');
    }

    public function almacen(){

        return $this->belongsTo('App\Models\Almacen');
    }
    
}
