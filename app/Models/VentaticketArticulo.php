<?php

namespace App\Models;

use App\Services\Cfdi\CfdiCommon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaticketArticulo extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['product'];

    //relacion uno a muchos inversa

    public function ventaticket()
    {
        return $this->belongsTo('App\Models\Ventaticket');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }

    public function departamento()
    {
        return $this->belongsTo('App\Models\Departamento');
    }
    public function files()
    {
        return $this->hasMany(ArticuloFile::class);
    }

    //RELACIÓN UNO A MUCHOS

    public function taxes()
    {
        return $this->hasMany('App\Models\ArticuloTax');
    }
    public function abono_tickets()
    {
        return $this->hasMany('App\Models\AbonoTicket');
    }
    public function abono_articulos()
    {
        return $this->hasMany('App\Models\AbonoArticulo');
    }
    //relacion uno a uno
    public function production_order()
    {
        return $this->hasMany('App\Models\ProductionOrder');
    }
    //metodos
    public function usesConsumable()
    {
        return $this->product->usesConsumable();
    }
    public function addCantidad($cantidad)
    {
        $this->cantidad += $cantidad;
    }
    public function setDescuento()
    {
        $descuentoModel = $this->product->getDescuentoModel($this->cantidad);
        if (!$descuentoModel) {
            $this->descuento = 0;
            $this->importe_descuento = 0;
            return;
        }

        if ($descuentoModel->porcentaje_type) {
            $descuento = $this->precio_usado * ($descuentoModel->descuento / 100);
        } else {
            $descuento = $descuentoModel->descuento;
        }
        $this->descuento = $descuento;
        $this->importe_descuento = $descuento * $this->cantidad;
    }
    public function setImporte()
    {
        //subtotal
        if ($this->usaMedidas()) {
            $importe = $this->precio_usado * $this->area_total;
        } else {
            $importe = $this->precio_usado * $this->cantidad;
        }
        $this->precio_final = $importe;
    }
    public function getGanancia()
    {
        return $this->ganancia;
    }
    public function setGanancia($product)
    {
        $costo = $product->product->pcosto * $this->cantidad;
        $ganancia = $this->precio_final - $this->importe_descuento - $costo;
        $this->ganancia = $ganancia;
    }
    public function incrementInventario($cantidad)
    {
        $almacenId = $this->ventaticket->almacen_id;
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
        $this->precio_usado = $this->getPrecioBase($this->precio_usado);
    }
    function getPrecioBase($precio)
    {
        $taxes = $this->product->taxes;
        if (!$taxes->count()) {
            return $precio;
        }
        $sumaTasas = $taxes->where('tipo', 'traslado')->sum('tasa_cuota') / 100;
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
        $taxesAmount = $this->getCurrentTaxesAmount();
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
        $this->impuesto_traslado = $this->getTrasladoTaxesAmount();
        if ($this->ventaticket->retention) {
            $this->impuesto_retenido = $this->getRetencionTaxesAmount();
        }
    }
    function getTrasladoTaxesAmount()
    {
        $ventaticket = $this->ventaticket;
        $taxes = $this->product->taxes->where('tipo', 'traslado');
        if (!$taxes->count()) {
            return 0;
        }
        $cfidCommon = new CfdiCommon();
        [$productTaxes, $total] = $cfidCommon->calculateTaxesByType($taxes, $this->precio_final, $this->cantidad, 'traslado');

        foreach ($productTaxes as $pt) {
            ArticuloTax::updateOrCreate(
                [
                    'ventaticket_id' => $ventaticket->id,
                    'ventaticket_articulo_id' => $this->id,
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
        return $total;
    }
    function getIvaAmount()
    {
        return $this->taxes->where('c_impuesto', '002')->sum('importe');
    }
    function getRetencionTaxesAmount()
    {
        $ventaticket = $this->ventaticket;
        $rules = $ventaticket->retention_rules;
        if (!$rules->count()) {
            return 0;
        }
        $cantidades = [];
        foreach ($rules as $rule) {
            $baseData = [
                'ventaticket_id' => $ventaticket->id,
                'ventaticket_articulo_id' => $this->id,
                'tax_id' => null
            ];
            if ($rule->isr_percentage) {
                $importe = $this->precio_final * ($rule->isr_percentage / 100);
                $cantidades[] = $importe;
                $baseData = array_merge(
                    $baseData,
                    [
                        'importe' => $importe,
                        'base' => $this->precio_final,
                        'c_impuesto' => '001',
                        'tipo_factor' => 'Tasa',
                        'tasa_o_cuota' => $this->formatPercentage($rule->isr_percentage),
                        'tipo' => 'retenido',
                        'descripcion' => 'ISR',
                    ]
                );
                ArticuloTax::create($baseData);
            }
            if ($rule->iva_percentage) {
                $importe = $this->precio_final * ($rule->iva_percentage / 100);
                $cantidades[] = $importe;
                $baseData = array_merge($baseData, [
                    'importe' => $importe,
                    'base' => $this->precio_final,
                    'c_impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota' =>  $this->formatPercentage($rule->iva_percentage),
                    'tipo' => 'retenido',
                    'descripcion' => 'IVA',
                ]);
                ArticuloTax::create($baseData);
            }
        };
        return array_sum($cantidades);
    }
    function getCurrentTaxesAmount($type = 'traslado')
    {
        $taxes = $this->taxes->where('tipo', $type);
        if (!$taxes->count()) {
            return 0;
        }
        $cantidades = [];
        foreach ($taxes as $tax) {
            $importe = $tax->importe;
            array_push($cantidades, $importe);
        };
        return array_sum($cantidades);
    }
    function getPrecioLista()
    {
        return $this->product->getPrecioVal($this->getAlmacenId());
    }
    function isCurrentPrecioDifferentThanLista(): bool
    {
        $currentPrecio = $this->precio_usado;
        $systemPrecio = $this->getSystemPrecio();
        return $currentPrecio && $currentPrecio != $systemPrecio;
    }
    function getAlmacenId(): int
    {
        return $this->ventaticket->almacen_id;
    }
    function destroyMe()
    {
        // $cantidad = $this->cantidad;
        // if ($this->usaMedidas()) {
        //     $this->incrementInventario($this->area_total);
        // } else {
        //     $this->incrementInventario($cantidad);
        // }
        $this->delete();
    }
    public function enuffInventario()
    {
        $cantidadActual = $this->product->getCantidadActual($this->ventaticket->almacen_id);
        return $cantidadActual >= $this->cantidad;
    }
    function necesitaProduction()
    {
        return !!$this->product->necesita_produccion;
    }
    function esConsumibleGenerico()
    {
        return $this->product->consumible == 'generico';
    }
    function usaMedidas()
    {
        return $this->product->usa_medidas;
    }
    public function createInventarioHistorial($tipo, $descripcion, $user = null)
    {
        if (!$user) {
            $user = $this->ventaticket->user;
        }
        $almacenId = $this->ventaticket->almacen_id;
        $inventarioActual = $this->getCantidadInventario($almacenId);
        if ($this->usaMedidas()) {
            $cantidadEnTicket = $this->cantidad * $this->area;
        } else {
            $cantidadEnTicket = $this->cantidad;
        }

        if ($tipo == "increment") {
            $cantidadAnterior = $inventarioActual;
            $cantidadPosterior = $inventarioActual + $cantidadEnTicket;
            $cantidad = $cantidadEnTicket;
        } else {
            $cantidadAnterior = $inventarioActual + $cantidadEnTicket;
            $cantidadPosterior = $inventarioActual;
            $cantidad = -$cantidadEnTicket;
        }
        InventHistorial::create([
            'user_id' => $user->id,
            'organization_id' => $user->active_organization_id,
            'product_id' => $this->product_id,
            'almacen_id' => $almacenId,
            'cantidad' => $cantidad,
            'cantidad_anterior' => $cantidadAnterior ?? 0,
            'cantidad_despues' => $cantidadPosterior ?? 0,
            'descripcion' => $descripcion,
            'created_at' => getMysqlTimestamp($user->configuration?->time_zone)
        ]);
    }
}
