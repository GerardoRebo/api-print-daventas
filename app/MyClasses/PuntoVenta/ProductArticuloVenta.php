<?php

namespace App\MyClasses\PuntoVenta;

use App\Models\Product;

class ProductArticuloVenta
{
    public $product;
    public function __construct(
        public $id,
        public $precio,
        public $cantidad,

    ) {
        $this->product = Product::with(['descuentos'])->find($id);
    }
    public function enuffInventario($almacenId)
    {
        $cantidadActual = $this->product->getCantidadActual($almacenId);
        return $cantidadActual >= $this->cantidad;
    }
    public function getDescuentoModel()
    {
        return $this->product->getDescuentoModel($this->cantidad);
    }
    public function esKit()
    {
        return !!$this->product->es_kit;
    }
    public function getComponents()
    {
        return $this->product->product_components;
    }
    public function getDescuentos()
    {
        return $this->product->descuentos;
    }
    public function setPrecio($precio)
    {
        $this->precio += $precio;
    }
    public function getTaxes()
    {
        return $this->product->taxes;
    }
    
}
