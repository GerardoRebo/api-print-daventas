<?php

namespace App\MyClasses\Movimientos;

use App\Exceptions\OperationalException;
use App\Models\ArticuloOcTax;
use App\Models\ArticulosOc;
use App\Models\OrdenCompra;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TicketCompra
{
    public $ticket;
    public User $user;

    public function __construct(public $id = null)
    {
        $this->ticket = OrdenCompra::with([
            'articulos_ocs.product.proveedors',
            'articulos_ocs.orden_compra',
            'articulos_ocs.product.product_components'
        ])->find($id);
    }
    public function registerArticulo(ProductArticuloCompra $product)
    {
        $this->user = auth()->user();
        $this->user->load('configuration');
        if ($this->ticket->tipo == "C") {
            return $this->registerArticuloCompra($product);
        } else {
            return $this->registerArticuloTransferencia($product);
        }
    }
    public function registerArticuloCompra(ProductArticuloCompra $product)
    {
        $this->isTicketReceived();

        $cambiaCosto = false;
        // Check if the product cost has changed
        if ($product->costChanged()) {
            $cambiaCosto = true;
            $precioAn = $product->getPrecio($this->getAlmacenOrigen());
        }

        // Process the adjustment of cost
        $product->product->procesaAjusteCosto($this->user, $product->costo, 'Compra Consecutivo#' . $this->ticket->consecutivo);

        // Check if the article already exists in the ticket
        $yaExisteArticulo = $this->yaExisteArticuloEnTicket($product->product->id);

        // If the article already exists in the ticket
        if ($yaExisteArticulo) {
            // Get the existing article
            $articulo = $this->getArticuloByProductId($product->product->id);

            // Set the quantity to the existing article
            $articulo->cantidad_ordenada += $product->cantidad;

            // Set the cost of the article
            $articulo->costo_al_ordenar = $product->costo;

            // return "Ya existe articulo";
        } else {
            // Create a new article in the ticket
            $articulo = $this->createArticulo($product);
        }

        $suma = $this->getSumaTaxes($product->getTaxes(), $articulo);
        $articulo->impuestos_al_enviar = $suma;

        // Update the values of the article
        $articulo->updateImporte();
        $articulo->save();

        // If the cost has changed, return the previous cost, current cost, and previous price
        if ($cambiaCosto) {
            return [$product->product->ucosto, $product->costo, $precioAn];
        }

        return;
    }
    public function registerArticuloTransferencia(ProductArticuloCompra $product)
    {
        $this->isTicketReceived();

        if (!$product->enuffInventario($this->ticket->almacen_origen_id)) {
            return "No Inventario";
        }

        // Check if the article already exists in the ticket
        $yaExisteArticulo = $this->yaExisteArticuloEnTicket($product->product->id);

        // If the article already exists in the ticket
        if ($yaExisteArticulo) {
            // Get the existing article
            $articulo = $this->getArticuloByProductId($product->product->id);

            // Set the quantity to the existing article
            $articulo->cantidad_ordenada += $product->cantidad;

            // Set the cost of the article
            $articulo->costo_al_ordenar = $product->product->pcosto;

            // return "Ya existe articulo";
        } else {
            // Create a new article in the ticket
            $articulo = $this->createArticulo($product);
        }
        $articulo->incrementInventario(-$product->cantidad, $this->ticket->almacen_origen_id);

        // Update the values of the article
        $articulo->updateImporte();
        $articulo->save();

        return;
    }
    public function registerArticuloSolicitudTransferencia($product)
    {
        // Your code here
    }
    function updateArticulo(ProductArticuloCompra $product)
    {
        $this->user = auth()->user();
        $this->user->load('configuration');

        if ($this->ticket->tipo == "C") {
            return $this->updateArticuloCompra($product);
        } else {
            return $this->updateArticuloTransferencia($product);
        }
    }
    function updateArticuloCompra(ProductArticuloCompra $product)
    {
        $this->isTicketReceived();

        $cambiaCosto = false;
        // Check if the product cost has changed
        if ($product->costChanged()) {
            $cambiaCosto = true;
            $precioAn = $product->getPrecio($this->getAlmacenOrigen());
        }

        // Process the adjustment of cost
        $product->product->procesaAjusteCosto($this->user, $product->costo, 'Compra Consecutivo#' . $this->ticket->consecutivo);

        // Get the existing article
        $articulo = $this->getArticuloByProductId($product->product->id);

        // Set the quantity to the existing article
        $articulo->cantidad_ordenada = $product->cantidad;

        // Set the cost of the article
        $articulo->costo_al_ordenar = $product->costo;

        // return "Ya existe articulo";

        $suma = $this->getSumaTaxes($product->getTaxes(), $articulo);
        $articulo->impuestos_al_recibir = $suma;

        // Update the values of the article
        $articulo->updateImporte();
        $articulo->save();

        // If the cost has changed, return the previous cost, current cost, and previous price
        if ($cambiaCosto) {
            return [$product->product->ucosto, $product->costo, $precioAn];
        }

        return;
    }
    function updateArticuloTransferencia(ProductArticuloCompra $product)
    {
        $this->isTicketReceived();

        if (!$product->enuffInventario($this->ticket->almacen_origen_id)) {
            return "No Inventario";
        }

        // Get the existing article
        $articulo = $this->getArticuloByProductId($product->product->id);

        $articulo->incrementInventario(-$articulo->getDelta($product->cantidad), $this->ticket->almacen_origen_id);

        // Set the quantity to the existing article
        $articulo->cantidad_ordenada = $product->cantidad;

        // Set the cost of the article
        $articulo->costo_al_ordenar = $product->product->pcosto;


        // Update the values of the article
        $articulo->updateImporte();
        $articulo->save();

        return;
    }
    public function yaExisteArticuloEnTicket($product)
    {
        return $this->ticket->articulos_ocs()->pluck('product_id')->contains($product);
    }
    public function getTotal()
    {
        return $this->getSubtotal() + $this->getTotalImpuestos();
    }
    public function getSubtotal()
    {
        return $this->getArticulos()->sum('total_al_ordenar');
    }
    public function getTotalImpuestos()
    {
        return $this->getArticulos()->sum('impuestos_al_enviar');
    }
    public function decrementImporte($importe)
    {
        $this->ticket->decrement('total_enviado', $importe);
    }
    public function ticketUpdate($cambiaUsuario, $user, $turno)
    {
        $this->ticket->estado = 'R';
        $this->ticket->subtotal_enviado = $this->getSubtotal();
        $this->ticket->impuestos_enviado = $this->getTotalImpuestos();
        $this->ticket->total_enviado = $this->getTotal();
        $this->ticket->turno_id = $turno;
        $this->ticket->enviada_en = getMysqlTimestamp($user->configuration?->time_zone);
        $this->ticket->recibida_en = getMysqlTimestamp($user->configuration?->time_zone);
        if ($cambiaUsuario) {
            $this->ticket->user_id = $user->id;
        }
        $this->ticket->save();
    }
    public function getArticuloByProductId($product)
    {
        return $this->ticket->articulos_ocs()->where('product_id', $product)->first();
    }
    public function getArticuloById($articulo)
    {
        return $this->ticket->articulos_ocs->where('id', $articulo)->first();
    }
    public function createArticulo($product)
    {
        return ArticulosOc::create([
            'orden_compra_id' => $this->ticket->id,
            'product_id' => $product->id,
            'cantidad_ordenada' => $product->cantidad,
            'cantidad_recibida' => null,
            'costo_al_ordenar' => $product->costo,
            'costo_al_recibir' => null,
            'dias_en_recibir' => null,
            'utilidad_estimada_al_ordenar' => null,
            'utilidad_estimada_al_recibir' => null,
            'impuestos_al_recibir' => null,
            'subtotal_al_recibir' => null,
            'total_al_ordenar' => $product->cantidad * $product->costo,
            'total_al_recibir' => null,
            'precio_sin_impuestos' => null,
            'precio_con_impuestos' => null,
        ]);
    }
    /** @var Collection<ArticulosOc>*/
    public function getArticulos(): Collection
    {
        return $this->ticket->articulos_ocs;
    }
    public function getCliente()
    {
        return $this->ticket->cliente_id;
    }
    public function getTipo()
    {
        return $this->ticket->tipo;
    }
    public function getEstado()
    {
        return $this->ticket->estado;
    }
    public function getAlmacenOrigen()
    {
        return $this->ticket->almacen_origen_id;
    }
    public function getConsecutivo()
    {
        return $this->ticket->consecutivo;
    }
    public function getAlmacenDestino()
    {
        return $this->ticket->almacen_destino_id;
    }
    public function setFormaPago($forma)
    {
        $this->ticket->forma_de_pago = $forma;
    }
    public function getSpecificAlmacenCliente($id)
    {
        return $this->ticket = OrdenCompra::find($id);
    }
    public function createHistory($user, $compraArticulo, $almacen, $tipo, $descripcion)
    {
        $inventarioActual = $compraArticulo->getCantidad($almacen);
        $cantidadEnTicket = $compraArticulo->articulo->cantidad_ordenada;
        if ($tipo == "increment") {
            $cantidadAnterior = $inventarioActual - $cantidadEnTicket;
            $cantidadPosterior = $inventarioActual;
            $cantidad = $cantidadEnTicket;
        } else {

            $cantidadAnterior = $inventarioActual + $cantidadEnTicket;
            $cantidadPosterior = $inventarioActual;
            $cantidad = -$cantidadEnTicket;
        }
        $this->ticket->histories()->create([
            'organization_id' => $user->active_organization_id,
            'product_id' => $compraArticulo->getProductId(),
            'user_id' => $user->id,
            'almacen_id' => $almacen,
            'cuando_fue' => getMysqlTimestamp($user->configuration?->time_zone),
            'cantidad_anterior' => $cantidadAnterior,
            'cantidad_posterior' => $cantidadPosterior,
            'cantidad' => $cantidad,
            'descripcion' => $descripcion,
            'costo_anterior' => null,
            'costo_despues' => null,
            'venta_por_kit' => 0,
            'verificado' => false,
        ]);
    }
    public function createInventarioHistorial($almacen, $tipo, $descripcion)
    {
        $user = $this->ticket->user;
        $articulosHistory = [];
        foreach ($this->getArticulos() as $articulo) {
            $inventarioActual = $articulo->product->getCantidadActual($almacen);
            $cantidadEnTicket = $articulo->cantidad_ordenada;
            if ($tipo == "increment") {
                $cantidadAnterior = $inventarioActual - $cantidadEnTicket;
                $cantidadPosterior = $inventarioActual;
                $cantidad = $cantidadEnTicket;
            } else {
                $cantidadAnterior = $inventarioActual + $cantidadEnTicket;
                $cantidadPosterior = $inventarioActual;
                $cantidad = -$cantidadEnTicket;
            }
            array_push($articulosHistory, [
                'user_id' => $user->i$user->active_organization_id
                'organization_id' => $user->organization_id,
                'product_id' => $articulo->product_id,
                'almacen_id' => $almacen,
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
    public function getSumaTaxes($taxes, $articulo)
    {
        $cantidades = [];
        $productTaxes = [];
        foreach ($taxes as $tax) {
            if (!$tax->pivot->compra) continue;
            $costo = $articulo->costo_al_ordenar;
            $cantidad = $articulo->cantidad_ordenada;
            $impuesto = $cantidad * ($costo  * ($tax->tasa_cuota / 100));
            array_push($productTaxes, [
                'orden_compra_id' => $this->ticket->id,
                'articulos_oc_id' => $articulo->id,
                'tax_id' => $tax->id,
                'importe' => $impuesto,
                'base' => $costo * $cantidad,
                'c_impuesto' => $tax->c_impuesto,
                'tipo_factor' => $tax->tipo_factor,
                'tasa_o_cuota' => $tax->tasa_cuota_str,
                'tipo' => $tax->tipo,
                'descripcion' => $tax->descripcion,
            ]);
            array_push($cantidades, $impuesto);
        };
        foreach ($productTaxes as $pt) {
            ArticuloOcTax::updateOrCreate(
                [
                    'orden_compra_id' => $pt['orden_compra_id'],
                    'articulos_oc_id' => $pt['articulos_oc_id'],
                    'tax_id' => $pt['tax_id']
                ],
                [
                    'importe' => $pt['importe'],
                    'base' => $pt['base'],
                    'c_impuesto' => $pt['c_impuesto'],
                    'tipo_factor' => $pt['tipo_factor'],
                    'tasa_o_cuota' => $pt['tasa_o_cuota'],
                    'tipo' => $pt['tipo'],
                    'descripcion' => $pt['descripcion'],
                ]
            );
        }
        return array_sum($cantidades);
    }
    private function isTicketReceived()
    {
        // Check if the ticket is already received
        if ($this->ticket->estado == "R") {
            throw new OperationalException("Ticket ya recibido.");
        }
    }
    function attachProductsToProveedor()
    {
        $productsToAttach = [];
        foreach ($this->getArticulos() as $product) {
            $product = new ProductArticuloCompra($product->product_id, null, null);
            if ($this->ticket->proveedor_id && !$product->product->proveedors->where('id', $this->ticket->proveedor_id)->count()) {
                $productsToAttach[] = $product->id;
            }
        }
        if ($this->ticket->proveedor_id) {
            $this->ticket->proveedor->products()->attach($productsToAttach);
        }
    }
    function incrementInventario($almacen)
    {
        foreach ($this->getArticulos() as $articulo) {
            /** @var Product $product */
            $product = $articulo->product;
            $product->incrementInventario($articulo->cantidad_ordenada, $almacen);
        }
    }
    function decrementInventario($almacen)
    {
        foreach ($this->getArticulos() as $articulo) {
            /** @var Product $product */
            $product = $articulo->product;
            $product->incrementInventario(-$articulo->cantidad_ordenada, $almacen);
        }
    }
    function cancelarCompra()
    {
        $almacenO = $this->getAlmacenOrigen();
        $almacenD = $this->getAlmacenDestino();

        if ($this->getEstado() == 'P') {
        } elseif ($this->getEstado() == "R") {
            $this->incrementInventario($almacenO);
            $this->createInventarioHistorial($almacenO, 'decrement', "Cancelacion de Compra");
        }
        $this->ticket->update([
            'estado' => 'C',
            'cancelada_en' => today(),
        ]);
    }
    function cancelarTransferencia()
    {
        $almacenO = $this->getAlmacenOrigen();
        $almacenD = $this->getAlmacenDestino();

        if ($this->getEstado() == 'P') {
            $this->incrementInventario($almacenO);
            $this->createInventarioHistorial($almacenO, 'increment', "Cancelacion de Transferencia Pendiente");
        } elseif ($this->getEstado() == "R") {
            $this->incrementInventario($almacenO);
            $this->decrementInventario($almacenD);
            $this->createInventarioHistorial($almacenO, 'increment', "Cancelacion de Transferencia Enviada");
            $this->createInventarioHistorial($almacenD, 'decrement', "Cancelacion de Transferencia Recibida");
        }
        $this->ticket->update([
            'estado' => 'C',
            'cancelada_en' => today(),
        ]);
    }
}
