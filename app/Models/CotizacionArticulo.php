<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionArticulo extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['product'];

    //relacion uno a muchos inversa

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }

    public function departamento()
    {
        return $this->belongsTo('App\Models\Departamento');
    }

    //RELACIÓN UNO A MUCHOS

    public function taxes()
    {
        return $this->hasMany(CotizacionArticuloTax::class);
    }
    public function abono_tickets()
    {
        return $this->hasMany('App\Models\AbonoTicket');
    }
    public function abono_articulos()
    {
        return $this->hasMany('App\Models\AbonoArticulo');
    }
    //metodos
    public function addCantidad($cantidad)
    {
        $this->cantidad += $cantidad;
    }
    public function setDescuento()
    {
        $descuentoModel=$this->product->getDescuentoModel($this->cantidad);
        if (!$descuentoModel) return;

        if ($descuentoModel->porcentaje_type) {
            $descuento = $this->precio * ($descuentoModel->descuento / 100);
        }else{
            $descuento = $descuentoModel->descuento;
        }
        $this->descuento = $descuento;
        $this->importe_descuento = $descuento * $this->cantidad;
    }
    public function setImporte()
    {
        //subtotal
        $importe = $this->precio * $this->cantidad;
        $this->importe = $importe;
    }
    public function incrementInventario($cantidad)
    {
        $almacenId = $this->cotizacion->almacen_id;
        $product = $this->product;
        if ($product) {
            $product->incrementInventario($cantidad, $almacenId);
        }
    }
    public function incrementCantidadDevuelta($cantidad)
    {
        $this->increment("cantidad_devuelta", $cantidad);
    }
    public function setPrecioBase()
    {
        $this->precio = $this->getPrecioBase($this->precio);
    }
    function getPrecioBase($precio)
    {
        $taxes = $this->product->taxes;
        if (!$taxes->count()) {
            return $precio;
        }
        $sumaTasas = $taxes->sum('tasa_cuota') / 100;
        $precioUsado = $precio;
        return $precioUsado / (1 + $sumaTasas);
    }
    function getSystemPrecio()
    {
        $precioLista = $this->getPrecioLista();
        $precioBase = $this->getPrecioBase($precioLista);
        $descuentoModel = $this->product->getDescuentoModel($this->cantidad);
        $descuento = 0;
        if ($descuentoModel) {
            $descuento = $descuentoModel->descuento;
        }
        $taxesAmount = $this->getTaxesAmount();
        return $precioBase - $descuento + $taxesAmount;
    }
    public function getCantidadInventario($almacenId)
    {
        return $this->product->getCantidadActual($almacenId);
    }
    public function getCantidadActual($almacenId)
    {
        return $this->product->getCantidadActual($almacenId);
    }
    public function setDevuelto()
    {
        $this->update(["fue_devuelto" => 1]);
    }
    public function setTaxes()
    {
        $this->impuesto_traslado = $this->getTaxesAmount('traslado');
        $this->impuesto_retenido = $this->getTaxesAmount('retenido');
    }
    function getTaxesAmount($type ='traslado')
    {
        $taxes = $this->product->taxes->where('tipo', $type);
        $this->load('cotizacion');
        $cotizacion = $this->cotizacion;
        if (!$taxes->count()) {
            return 0;
        }
        $productTaxes = [];
        $cantidades = [];
        foreach ($taxes as $tax) {
            if (!$tax->pivot->venta) continue;
            $baseImponible = $this->precio_final - $this->importe_descuento;
            $importe = $baseImponible * ($tax->tasa_cuota / 100);
            array_push($productTaxes, [
                'cotizacion_id' => $cotizacion->id,
                'cotizacion_articulo_id' => $this->id,
                'tax_id' => $tax->id,
                'importe' => $importe,
                'base' => $baseImponible,
                'c_impuesto' => $tax->c_impuesto,
                'tipo_factor' => $tax->tipo_factor,
                'tasa_o_cuota' => $tax->tasa_cuota_str,
                'tipo' => $tax->tipo,
                'descripcion' => $tax->descripcion,
            ]);
            array_push($cantidades, $importe);
        };
        foreach ($productTaxes as $pt) {
            CotizacionArticuloTax::updateOrCreate(
                [
                    'cotizacion_id' => $pt['cotizacion_id'],
                    'cotizacion_articulo_id' => $pt['cotizacion_articulo_id'],
                    'tax_id' => $pt['tax_id']
                ],
                [
                    'importe' => $pt['importe'],
                    'base' => $pt['base'],
                    'c_impuesto' => $pt['c_impuesto'],
                    'tipo_factor' => $pt['tipo_factor'],
                    'tasa_o_cuota' => $pt['tasa_o_cuota'],
                    'tipo' => $pt['tipo'],
                    'descripcion' => $pt['descripcion'],
                ]
            );
        }
        return array_sum($cantidades);
    }
    function getPrecioLista()
    {
        return $this->product->getPrecioVal($this->getAlmacenId());
    }
    function isCurrentPrecioDifferentThanLista(): bool
    {
        $currentPrecio = $this->precio;
        $systemPrecio = $this->getSystemPrecio();
        return $currentPrecio && $currentPrecio != $systemPrecio;
    }
    function getAlmacenId(): int
    {
        return $this->cotizacion->almacen_id;
    }
    function destroyMe() {
        // $cantidad = $this->cantidad;
        // $this->incrementInventario($cantidad);
        $this->delete();
    }
}
