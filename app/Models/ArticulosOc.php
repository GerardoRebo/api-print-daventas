<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticulosOc extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['product'];

    //RELACIÓN UNO A MUCHOS

    //relacion uno a muchos inversa

    public function orden_compra()
    {
        return $this->belongsTo('App\Models\OrdenCompra');
    }
    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }
    public function taxes()
    {
        return $this->hasMany('App\Models\ArticuloOcTax');
    }
    function getDelta($cantidad) {
       return $cantidad - $this->cantidad_ordenada; 
    }
    public function updateImporte()
    {
        $this->total_al_ordenar = ($this->costo_al_ordenar * $this->cantidad_ordenada);
    }
    public function incrementInventario($cantidad, $almacenId) {
        $this->product->incrementInventario($cantidad, $almacenId);
    }
    public function getCantidadActual($almacenId)
    {
        return $this->product->getCantidadActual($almacenId);
    }
}
