<?php

namespace App\Services\FacturasFolios;

use App\Models\FoliosUtilizado;
use App\Models\Organization;
use App\Models\VentaFolio;

class FacturasFolios
{
    function ventaFolios(Organization $organization, $cantidad)
    {
        $venta = VentaFolio::create([
            'organization_id' => $organization->id,
            'cantidad' => $cantidad,
        ]);
        $saldo = $organization->latestFoliosUtilizado;
        FoliosUtilizado::create(
            [
                'organization_id' => $organization->id,
                'cantidad' => $cantidad,
                'antes' => $saldo->saldo,
                'despues' => $saldo->saldo + $cantidad,
                'saldo' => $saldo->saldo + $cantidad,
                'facturable_type' => 'App\Models\VentaFolio',
                'facturable_id' => $venta->id,
            ]
        );
    }
}
