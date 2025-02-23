<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $with = ['almacen_origen', 'almacen_destino', 'proveedor', 'user' ];

    //relacion uno a muchos inversa

    public function user(){

        return $this->belongsTo('App\Models\User');
    }
     public function proveedor(){

        return $this->belongsTo('App\Models\Proveedor');
    }

    public function almacen_origen(){
        return $this->belongsTo('App\Models\Almacen');
    }

    public function almacen_destino(){
        return $this->belongsTo('App\Models\Almacen');
    }

    //RELACIÓN UNO A MUCHOS

    public function inventario_recibos(){
    return $this->hasMany('App\Models\InventarioRecibo');
    }

    public function articulos_ocs(){

        return $this->hasMany('App\Models\ArticulosOc');
    }
    //relacion uno a muchos polimorfica
    public function histories(){
        
        return $this->morphMany('App\Models\History', 'historiable');
    }
    public function getArticulosExtended()
    {
        $almacen = $this->getAlmacenOrigen();
        $movimientoArticulos = ArticulosOc::with('product.product_components.product_hijo')->leftJoin('inventario_balances', function ($join,) use ($almacen) {
            $join->on('articulos_ocs.product_id', '=', 'inventario_balances.product_id')
                ->where('inventario_balances.almacen_id', '=', $almacen);
        })->leftJoin('precios', function ($join) use ($almacen) {
            $join->on('articulos_ocs.product_id', '=', 'precios.product_id')
                ->where('precios.almacen_id', '=',  $almacen);
        })
            ->where('orden_compra_id', $this->id)
            ->join('products', 'articulos_ocs.product_id', '=', 'products.id')
            ->select(
                'products.*',
                'precios.precio',
                'inventario_balances.cantidad_actual',
                'articulos_ocs.cantidad_ordenada',
                'articulos_ocs.id',
                'articulos_ocs.product_id',
                'articulos_ocs.impuestos_al_enviar',
                'articulos_ocs.costo_al_ordenar',
                'articulos_ocs.total_al_ordenar'
            )
            ->orderByDesc('articulos_ocs.id')
            ->get();

        $movimientoArticulos = $movimientoArticulos->map(function ($item, $key) use ($almacen) {
            if ($item->es_kit) {
                $item->cantidad_actual = $item->getCantidadActual($almacen);
            }
            $item->load('taxes');
            $item->taxes;
            return $item;
        });

        return $movimientoArticulos;
    }
    public function getAlmacenOrigen()
    {
        return $this->almacen_origen_id;
    }
}
