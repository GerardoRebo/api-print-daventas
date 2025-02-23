<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    protected $guarded=[];

     //RELACIÓN UNO A MUCHOS
     public function ordencompras(){
        return $this->hasMany('App\Models\OrdenCompra');
    }
    public function lista_de_compras(){
        return $this->hasMany('App\Models\ListaDeCompras');
    }

    //Relación muchos a muchos

    public function products(){
        return $this->belongsToMany('App\Models\Product');
    }
}
