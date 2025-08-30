<?php

namespace App\Services\Cfdi;

class CfdiCommon
{
    private $retenciones = [];

    public function calculateTaxesByType($taxes, $baseProducto, $cantidad, $tipo = 'traslado')
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
            'descripcion' => $tax->descripcion,
        ];
    }
    function getFormattedRetenciones($retenciones)
    {
        foreach ($retenciones as $retencion) {
            $this->addRetencion($retencion);
        }
        return $this->retenciones;
    }
    private function addRetencion($retencion)
    {
        $key = $this->impuestoKey(
            $retencion->c_impuesto,
            $retencion->tipo_factor,
            $retencion->tasa_o_cuota
        );
        if (! array_key_exists($key, $this->retenciones)) {
            $this->retenciones[$key] = [
                'Impuesto' => $retencion->c_impuesto,
                'TipoFactor' => $retencion->tipo_factor,
                'TasaOCuota' => $retencion->tasa_o_cuota,
                'Importe' => 0.0,
                'Base' => 0.0,
            ];
        }
        $this->retenciones[$key]['Importe'] += (float) $retencion->importe;
        $this->retenciones[$key]['Base'] += (float) $retencion->base;
    }
    public static function impuestoKey(string $impuesto, string $tipoFactor = '', string $tasaOCuota = ''): string
    {
        return implode(':', [$impuesto, $tipoFactor, $tasaOCuota]);
    }
}
