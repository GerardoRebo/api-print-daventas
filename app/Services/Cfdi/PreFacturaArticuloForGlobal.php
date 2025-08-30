<?php

namespace App\Services\Cfdi;

use App\Exceptions\OperationalException;

class PreFacturaArticuloForGlobal
{
    public $pre_factura_id, $product_id, $cantidad, $precio, $descuento, $importe, $concepto, $impuesto_traslado;
    function __construct(public $product, public $preFactura) {}
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
    public function setTaxes($taxes)
    {
        $productTaxes = [];
        $cantidadesTraslado = [];
        foreach ($taxes as $articuloTax) {
            array_push($productTaxes, [
                'tax_id' => $articuloTax->tax_id,
                'c_impuesto' => $articuloTax->c_impuesto,
                'tipo_factor' => $articuloTax->tipo_factor,
                'tasa_o_cuota' => $articuloTax->tasa_o_cuota,
                'tipo' => $articuloTax->tipo,
                'importe' => $articuloTax->importe,
                'base' => $articuloTax->base,
            ]);
            if ($articuloTax->tipo == 'traslado') {
                array_push($cantidadesTraslado, $articuloTax->importe);
            }
        };
        $this->addTaxes($productTaxes);
        $this->impuesto_traslado = array_sum($cantidadesTraslado);
    }
    public function setNewTaxesTraslado()
    {
        $baseProducto = $this->importe;
        $taxes = $this->product->taxes;

        [$productTaxes, $total] = $this->calculateTaxesByType($taxes, $baseProducto, $this->cantidad, 'traslado');

        $this->addTaxes($productTaxes);
        $this->impuesto_traslado = $total;
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
    private function addTaxes($productTaxes)
    {
        foreach ($productTaxes as $pt) {
            if ($pt['tipo'] === 'retenido') throw new OperationalException("Producto con impuesto retenido, no procede en factura global", 1);
            if ($pt['tipo'] === 'traslado') {
                $this->concepto->addTraslado([
                    'Base' => $pt['base'],
                    'Impuesto' => $pt['c_impuesto'],
                    'TipoFactor' => $pt['tipo_factor'],
                    'TasaOCuota' => number_format($pt['tasa_o_cuota'], 6, '.', ''),
                    'Importe' => $pt['importe'],
                ]);
            }
        }
    }
    private function calculateTaxesByType($taxes, $baseProducto, $cantidad, $tipo = 'traslado')
    {
        $productTaxes = [];
        $cantidades = [];
        $iepsImporte = 0.0;

        // Primero: IEPS
        foreach ($taxes as $tax) {
            if ($tax->c_impuesto !== '003') continue;

            $baseImponible = $baseProducto;
            [$importe, $tasaOCuota] = $this->calculateImporteNCuota($tax, $baseImponible, $cantidad);
            $iepsImporte += $importe;

            $productTaxes[] = $this->formatTaxes($tax, $importe, $baseImponible, $tasaOCuota);
            $cantidades[] = $importe;
        }

        // Luego: IVA, ISR, otros
        foreach ($taxes as $tax) {
            if ($tax->c_impuesto === '003') continue;

            $baseImponible = $baseProducto + $iepsImporte;
            [$importe, $tasaOCuota] = $this->calculateImporteNCuota($tax, $baseImponible, $cantidad);

            $productTaxes[] = $this->formatTaxes($tax, $importe, $baseImponible, $tasaOCuota);
            $cantidades[] = $importe;
        }

        return [$productTaxes, array_sum($cantidades)];
    }
    private function calculateImporteNCuota($tax, $base, $cantidad)
    {
        if ($tax->tipo_factor === 'Tasa') {
            $tasaOCuota = number_format($tax->tasa_cuota / 100, 6, '.', '');
            $importe = round($base * ($tax->tasa_cuota / 100), 2);
        } elseif ($tax->tipo_factor === 'Cuota') {
            $importe = round($cantidad * $tax->tasa_cuota, 2);
            $tasaOCuota = '0.000000';
        } else {
            $importe = 0.00;
            $tasaOCuota = '0.000000';
        }
        return [$importe, $tasaOCuota];
    }
    private function formatTaxes($tax, $importe, $base, $tasaOCuota)
    {
        return [
            'tax_id' => $tax->id,
            'c_impuesto' => $tax->c_impuesto,
            'tipo_factor' => $tax->tipo_factor,
            'tasa_o_cuota' => $tasaOCuota,
            'tipo' => $tax->tipo,
            'importe' => $importe,
            'base' => $base,
        ];
    }
}
