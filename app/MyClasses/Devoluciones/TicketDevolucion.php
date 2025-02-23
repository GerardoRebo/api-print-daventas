<?php

namespace App\MyClasses\Devoluciones;

use App\Models\Devolucione;
use App\Models\DevolucionesArticulo;
use Illuminate\Support\Facades\DB;

class TicketDevolucion
{
    public $ticket;
    public function __construct(public $id = null)
    {
        if ($id == null) return;

        $this->ticket = Devolucione::with('devoluciones_articulos.product')->find($id);
    }
    public function yaExisteArticuloEnTicket($product)
    {
        return $this->ticket->ventaticket_articulos()->pluck('product_id')->contains($product);
    }
    public function getTotal()
    {
        return $this->ticket->total;
    }
    public function getGanancia()
    {
        return $this->ticket->ganancia;
    }
    public function incrementImporte($importe)
    {
        $this->ticket->increment('total', $importe);
    }
    public function getArticulosExtended()
    {
        return DevolucionesArticulo::with('product.product_components.product_hijo')->where('devolucione_id', $this->ticket->id)->get();
    }
    public function getArticulos()
    {
        return $this->ticket->devoluciones_articulos;
    }
    public function decrementImporte($importe)
    {
        $this->ticket->decrement('total', $importe);
    }
    public function incrementGanancia($ganancia)
    {
        $this->ticket->increment('ganancia', $ganancia);
    }
    public function decrementGanancia($ganancia)
    {
        $this->ticket->decrement('ganancia', $ganancia);
    }
    public function getArticuloByProductId($product)
    {
        return $this->ticket->ventaticket_articulos()->where('product_id', $product)->first();
    }
    public function getArticuloById($articulo)
    {
        return $this->ticket->ventaticket_articulos()->where('id', $articulo)->first();
    }
    public function getSpecific($id)
    {
        return $this->ticket = Devolucione::find($id);
    }
    public function createInventarioHistorial( $tipo, $descripcion)
    {
        $user = $this->ticket->user;
        $almacenId = $this->ticket->ventaticket->almacen_id;
        $articulosHistory = [];
        foreach ($this->getArticulos() as $articulo) {
            $inventarioActual = $articulo->product->getCantidadActual($almacenId);
            $cantidadEnTicket = $articulo->cantidad_devuelta;

            if ($tipo == "increment") {
                $cantidadAnterior = $inventarioActual - $cantidadEnTicket;
                $cantidadPosterior = $inventarioActual;
                $cantidad = $cantidadEnTicket;
            } else {
                $cantidadAnterior = $inventarioActual;
                $cantidadPosterior = $inventarioActual - $cantidadEnTicket;
                $cantidad = -$cantidadEnTicket;
            }
            array_push($articulosHistory, [
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'product_id' => $articulo->product_id,
                'almacen_id' => $almacenId,
                'cantidad' => $cantidad,
                'cantidad_anterior' => $cantidadAnterior ?? 0,
                'cantidad_despues' => $cantidadPosterior ?? 0,
                'descripcion' => $descripcion,
                'created_at' => getMysqlTimestamp($user->configuration?->time_zone)
            ]);
        }
        DB::table('invent_historials')->insert(
            $articulosHistory
        );
    }
    public function getAlmacen()
    {
        return $this->ticket->almacen_id;
    }
    public function createArticulo($product)
    {
        $ganancia = ($product->precio - $product->product->pcosto) * $product->cantidad;
        return DevolucionesArticulo::create([
            'ventaticket_id' => $this->ticket->id,
            'product_id' => $product->id,
            'departamento_id' => null,
            'cantidad' => $product->cantidad,
            'ganancia' => $ganancia,
            'pagado_en' => null,
            'porcentaje_descuento' => null,
            'impuesto_unitario' => null,
            'precio_usado' => $product->precio,
            'cantidad_devuelta' => 0,
            'fue_devuelto' => 0,
            'porcentaje_pagado' => null,
            'precio_final' => $product->precio * $product->cantidad,
            'agregado_en' => null,
        ]);
    }
    public function getCliente()
    {
        return $this->ticket->cliente_id;
    }
    public function setFormaPago($forma)
    {
        $this->ticket->forma_de_pago = $forma;
    }
    public function getFormaPago()
    {
        return $this->ticket->forma_de_pago;
    }
    public function deleteTicket()
    {
        $this->ticket->delete();
    }
}
