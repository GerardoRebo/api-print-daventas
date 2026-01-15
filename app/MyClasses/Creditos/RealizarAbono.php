<?php

namespace App\MyClasses\Creditos;

use App\Models\Abono;
use App\Models\Deuda;

class RealizarAbono
{
    public function realizarAbono($deuda, $user, $cantidad, $comments, $turno)
    {
        $deuda = Deuda::with('cliente')->find($deuda);
        $cliente = $deuda->cliente;
        if ($cantidad == null) {
            $cantidad = $deuda->saldo;
        }

        $saldo = $deuda->saldo - $cantidad;

        $abono = Abono::create([
            'organization_id' => $user->active_organization_id,
            'deuda_id' =>  $deuda->id,
            'turno_id' =>  $turno->id,
            'user_id' =>  $user->id,
            'fecha' => getMysqlTimestamp($user->configuration?->time_zone),
            'comentarios' =>  $comments,
            'abono' =>  $cantidad,
            'saldo' =>  $saldo,
            'forma_de_pago' =>  'E'
        ]);

        $deuda->decrement('saldo', $cantidad);
        if ($deuda->saldo <= 0) {
            $deuda->liquidado = 1;
            $deuda->save();
        }
        $cliente->decrement('saldo_actual', $cantidad);
        return $abono;
    }
}
