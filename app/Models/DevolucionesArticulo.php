<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolucionesArticulo extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $with = ['product'];

    //relacion uno a muchos inversa

    public function ventaticket(){

        return $this->belongsTo('App\Models\Ventaticket');
    }
    public function devolucione(){

        return $this->belongsTo('App\Models\Devolucione');
    }

    public function product(){

        return $this->belongsTo('App\Models\Product');
    }
    
}
