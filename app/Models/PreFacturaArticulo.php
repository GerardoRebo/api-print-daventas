<?php

namespace App\Models;

use App\MyClasses\Factura\ComprobanteConceptoImpuestosRetencion;
use App\MyClasses\Factura\ComprobanteConceptoImpuestosTraslado;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Product $product
 */
class PreFacturaArticulo extends Model
{
    use HasFactory;

    protected $with = ['product'];

    public function preFactura(): BelongsTo
    {
        return $this->belongsTo(PreFactura::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product');
    }
    public function taxes()
    {
        return $this->hasMany('App\Models\PreFacturaArticuloTax');
    }

    protected function cantidad(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 3),
        );
    }
    protected function precio(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    protected function descuento(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    protected function impuesto(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    protected function importe(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    public function setNewTaxesTraslado()
    {
        $baseProducto = $this->importe;
        $taxes = $this->product->taxes;

        $cfidCommon = new CfdiCommon();
        [$productTaxes, $total] = $cfidCommon->calculateTaxesByType($taxes, $baseProducto, $this->cantidad);

        $this->addTaxes($productTaxes);
        $this->impuesto_traslado = $total;
    }
    public function setNewTaxesRetenido()
    {
        $ventaticket = $this->preFactura->ventaticket;
        $rules = $ventaticket->retention_rules;
        if (!$rules->count()) {
            return 0;
        }
        $cantidades = [];
        foreach ($rules as $rule) {
            $baseData = [
                'pre_factura_id' => $this->pre_factura_id,
                'pre_factura_articulo_id' => $this->id,
                'tax_id' => null
            ];
            if ($rule->isr_percentage) {
                //importe in this case is the calculation $this->importe is the base
                $importe = $this->importe * ($rule->isr_percentage / 100);
                $cantidades[] = $importe;
                $baseData = array_merge(
                    $baseData,
                    [
                        'importe' => $importe,
                        'base' => $this->importe, //base
                        'c_impuesto' => '001',
                        'tipo_factor' => 'Tasa',
                        'tasa_o_cuota' => $this->formatPercentage($rule->isr_percentage),
                        'tipo' => 'retenido',
                    ]
                );
                PreFacturaArticuloTax::create($baseData);
            }
            if ($rule->iva_percentage) {
                $importe = $this->importe * ($rule->iva_percentage / 100);
                $cantidades[] = $importe;
                $baseData = array_merge($baseData, [
                    'importe' => $importe,
                    'base' => $this->importe,
                    'c_impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota' =>  $this->formatPercentage($rule->iva_percentage),
                    'tipo' => 'retenido',
                ]);
                PreFacturaArticuloTax::create($baseData);
            }
        };
        $this->impuesto_retenido = array_sum($cantidades);
    }
    public function setTaxes($taxes)
    {
        $productTaxes = [];
        $cantidadesTraslado = [];
        $cantidadesRetencion = [];
        foreach ($taxes as $articuloTax) {
            array_push($productTaxes, [
                'pre_factura_articulo_id' => $this->id,
                'tax_id' => $articuloTax->tax_id,
                'c_impuesto' => $articuloTax->c_impuesto,
                'tipo_factor' => $articuloTax->tipo_factor,
                'tasa_o_cuota' => $articuloTax->tasa_o_cuota,
                'tipo' => $articuloTax->tipo,
                'importe' => $articuloTax->importe,
                'base' => $articuloTax->base,
            ]);
            if ($articuloTax->tipo == 'retenido') {
                array_push($cantidadesRetencion, $articuloTax->importe);
            } else {
                array_push($cantidadesTraslado, $articuloTax->importe);
            }
        };
        $this->addTaxes($productTaxes);
        $this->impuesto_traslado = array_sum($cantidadesTraslado);
        $this->impuesto_retenido = array_sum($cantidadesRetencion);
    }
    private function addTaxes($productTaxes)
    {
        foreach ($productTaxes as $pt) {
            PreFacturaArticuloTax::updateOrCreate(
                [
                    'pre_factura_id' => $this->pre_factura_id,
                    'c_impuesto' => $pt['c_impuesto'],
                    'tipo_factor' => $pt['tipo_factor'],
                    'tipo' => $pt['tipo'],
                    'tasa_o_cuota' => $pt['tasa_o_cuota'],
                    'pre_factura_articulo_id' => $pt['pre_factura_articulo_id'],
                    'tax_id' => $pt['tax_id']
                ],
                ['importe' => $pt['importe'], 'base' => $pt['base']]
            );
        }
    }
    function setImporte()
    {
        $this->importe = $this->cantidad * $this->precio;
    }
    function setBaseImpositiva()
    {
        $taxes = $this->product->taxes;
        $sumaTasas = $taxes->sum('tasa_cuota') / 100;
        $this->precio = ($this->precio - $this->descuento) / (1 + $sumaTasas);
        $this->setDescuento();
        $this->setImporte();
    }
    public function setDescuento()
    {
        $descuentoModel = $this->product->getDescuentoModel($this->cantidad);
        if (!$descuentoModel) return;
        if ($descuentoModel->porcentaje_type) {
            $descuento = $this->precio * ($descuentoModel->descuento / 100);
        } else {
            $descuento = $descuentoModel->descuento;
        }

        $this->descuento = $descuento * $this->cantidad;
    }
    function getConceptoImpuestos()
    {
        $lstImpuestosRetenidos = [];
        $lstImpuestosTrasladados = [];
        foreach ($this->taxes as $taxArticulo) {
            if ($taxArticulo->tipo == 'retenido') {
                $oImpuesto = new ComprobanteConceptoImpuestosRetencion();
            } else {
                $oImpuesto = new ComprobanteConceptoImpuestosTraslado();
            }
            $oImpuesto->Base = $taxArticulo->base;
            $oImpuesto->TipoFactor =  $taxArticulo->tipo_factor;
            $oImpuesto->Impuesto =  $taxArticulo->c_impuesto;
            $oImpuesto->Importe = $taxArticulo->importe;
            $oImpuesto->TasaOCuota = $taxArticulo->tasa_o_cuota;
            if ($taxArticulo->tipo == 'retenido') {
                $lstImpuestosRetenidos[] = $oImpuesto;
            } else {
                $lstImpuestosTrasladados[] = $oImpuesto;
            }
        }
        return [
            "retenidos" => $lstImpuestosRetenidos,
            "traslados" => $lstImpuestosTrasladados,
        ];
    }
}
