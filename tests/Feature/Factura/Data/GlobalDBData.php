<?php

namespace Tests\Feature\Factura\Data;

use App\Models\VentaticketArticulo;
use App\MyClasses\PuntoVenta\ProductArticuloVenta;
use App\MyClasses\PuntoVenta\TicketVenta;

class GlobalDBData extends RegisterDBData
{
    public VentaticketArticulo $articulo;
    function cargarUpdateDatos()
    {
        $this->createVentaTwo();
        $ventaTicket = $this->ventaTicket;
        $product = new ProductArticuloVenta(1, 10, 1);
        $ticketVenta = new TicketVenta($ventaTicket->id);
        $ticketVenta->registerArticulo($product);

        $product = new ProductArticuloVenta(2, 10, 1);
        $ticketVenta = new TicketVenta($ventaTicket->id);
        $ticketVenta->registerArticulo($product);

        $ventaTicket = $this->ventaTicketTwo;
        $product = new ProductArticuloVenta(1, 10, 1);
        $ticketVenta = new TicketVenta($ventaTicket->id);
        $ticketVenta->registerArticulo($product);

        $product = new ProductArticuloVenta(2, 10, 1);
        $ticketVenta = new TicketVenta($ventaTicket->id);
        $ticketVenta->registerArticulo($product);
    }
}
