<?php

namespace App\MyClasses\PuntoVenta;

use App\Models\Cotizacion;
use Exception;
use Illuminate\Support\Facades\Redis;

class CreateCotizacion
{

    public function realizarConexion($user)
    {
        try {
            return  Redis::incr('cotizacion' . $user->organization_id);
        } catch (Exception $e) {
            return  0;
        }
    }
    public function creaCotizacion($user)
    {
        $cuenta = $this->realizarConexion($user);

        $cotizacion = Cotizacion::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'consecutivo' => $cuenta,
            'esta_abierto' => 1,
            'pendiente' => 0,
        ]);
        return $cotizacion;
    }
}
