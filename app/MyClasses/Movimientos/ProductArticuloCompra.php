<?php

namespace App\MyClasses\Movimientos;

use App\Exceptions\OperationalException;
use App\Models\ArticulosOc;
use App\Models\Product;

class ProductArticuloCompra
{
    public ?Product $product;
    public function __construct(public $id, public $costo, public $cantidad, $type = 'product')
    {
        if ($type == 'product') {
            $this->product = Product::find($id);
            logger('here');
            logger($this->product);
        } elseif ($type == 'article') {
            $product = ArticulosOc::select('product_id')->find($id);
            $this->product = Product::where('id', $product?->product_id)->first();
        }
        if (!$this->product) {
            throw new OperationalException("No se encontro el articulo", 1);
        }
    }
    public function enuffInventario($almacenId)
    {
        $cantidadActual = $this->product->getCantidadActual($almacenId);
        return $cantidadActual >= $this->cantidad;
    }
    public  function costChanged()
    {
        return !! ($this->costo != $this->product->pcosto);
    }
    public function getTaxes()
    {
        return $this->product->taxes->where('tipo', 'traslado');
    }
    public function esKit()
    {
        return !!$this->product->es_kit;
    }
    public function getComponents()
    {
        $this->product->product_components;
    }
    public function addCantidad($cantidad)
    {
        $this->cantidad += $cantidad;
    }
    public function setcosto($costo)
    {
        $this->costo += $costo;
    }
    public function getPrecio($almacen)
    {
        return $this->product->getPrecioVal($almacen);
    }
}
