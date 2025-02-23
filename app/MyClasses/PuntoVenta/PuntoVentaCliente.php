<?php
namespace App\MyClasses\PuntoVenta;

use App\Models\Cliente;
use App\Models\Deuda;

class PuntoVentaCliente  
{
    public $cliente;
    public function __construct($id)
    {
     $this->cliente=Cliente::find($id);   
    }
    public function incrementSaldo($saldo)
    {
        $this->cliente->increment('saldo_actual', $saldo);
    }
    public function createDeuda($organization,$ticket)
    {
        Deuda::create([
            'organization_id' => $organization,
            'ventaticket_id' => $ticket->ticket->id,
            'cliente_id' => $this->cliente->id,
            'deuda' => $ticket->getTotal(),
            'saldo' => $ticket->getTotal(),
        ]);
    }
}
