<?php

namespace App\MyClasses\PuntoVenta;

use App\Models\Ventaticket;
use Exception;
use Illuminate\Support\Facades\Redis;

class CreateVentaTicket
{

    public function realizarConexion($user)
    {
        try {
            return  Redis::incr('venta' . $user->active_organization_id);
        } catch (Exception $e) {
            return  0;
        }
    }
    public function creaTicket($user)
    {
        $cuenta = $this->realizarConexion($user);

        $ventaticket = Ventaticket::create([
            'organization_id' => $user->active_organization_id,
            'user_id' => $user->id,
            'consecutivo' => $cuenta,
            'turno_id' => null,
            'cliente_id' => null,
            'almacen_id' => null,
            'nombre' => '',
            'subtotal' => 0,
            'impuesto_retenido' => 0,
            'impuesto_traslado' => 0,
            'total' => 0,
            'ganancia' => 0,
            'esta_abierto' => 1,
            'vendido_en' => null,
            'pago_con' => null,
            'numero_de_articulos' => 0,
            'pagado_en' => null,
            'esta_cancelado' => 0,
            'forma_de_pago' => 'E',
            'referencia' => '',
            'pendiente' => 0,
            'total_devuelto' => 0,
            'total_ahorrado' => null,
            'total_credito' => null,
            'refrescar_ticket' => null,
        ]);
        return $ventaticket;
    }
}
