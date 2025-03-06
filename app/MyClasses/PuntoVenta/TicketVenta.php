<?php

namespace App\MyClasses\PuntoVenta;

use App\Exceptions\OperationalException;
use App\Models\ProductionOrder;
use App\Models\Ventaticket;
use App\Models\VentaticketArticulo;
use App\MyClasses\TiendaHttp;
use App\Notifications\PrecioAjustado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TicketVenta
{
    public $ticket, $total, $ganancia, $descuento;
    public function __construct(public $id = null)
    {
        if ($id == null) return;

        $this->ticket = Ventaticket::with([
            'ventaticket_articulos.product.product_components',
            'ventaticket_articulos.product.precios',
            'ventaticket_articulos.ventaticket',
            'ventaticket_articulos.taxes'
        ])->find($id);
    }
    function registerArticulo(ProductArticuloVenta $product)
    {
        $yaExisteArticulo = $this->yaExisteArticuloEnTicket($product->id);

        if (!$product->enuffInventario($this->getAlmacen())) {
            throw new OperationalException("No hay suficiente inventario", 422);
        }
        $por_descuento = null;

        if ($yaExisteArticulo) {
            if ($product->product->usaMedidas()) {
                throw new OperationalException("El producto usa medidas", 422);
            }
            $articulo = $this->getArticuloByProductId($product->id);

            $articulo->precio_usado = $product->precio;
            //calculate base impositiva
            $articulo->cantidad += $product->cantidad;
            // weird take a look
            // $product->cantidad = $articulo->cantidad;
        } else {
            $articulo = $this->createArticulo($product);
        }

        //calculate base impositiva
        $articulo->setPrecioBase();
        $articulo->setDescuento();
        $articulo->setImporte();
        $articulo->setGanancia($product);
        //add taxes
        $articulo->setTaxes();

        $articulo->save();
        //todo:quitar
        // $articulo->incrementInventario(-$product->cantidad);
    }
    function updateArticulo($product, $articulo, $restaCantidad)
    {
        if ($restaCantidad < 0) {
            $enuffInventario = $product->enuffInventario($this->getAlmacen(), -$restaCantidad);
            if (!$enuffInventario) {
                throw new OperationalException("No hay suficiente inventario", 422);
            }
        }
        $articulo->precio_usado = $product->precio;
        $articulo->ancho = $product->ancho;
        $articulo->alto = $product->alto;
        $articulo->area = $product->ancho * $product->alto;
        $articulo->area_total  = $articulo->area * $product->cantidad;
        $articulo->addCantidad(-$restaCantidad);
        //calculate base impositiva
        $articulo->setPrecioBase();
        $articulo->setDescuento();
        $articulo->setImporte();
        $articulo->setGanancia($product);
        //add taxes
        $articulo->setTaxes();
        $articulo->save();
        $articulo->incrementInventario($restaCantidad);
    }
    public function notifyPreciosAjustados($user)
    {
        foreach ($this->getArticulos() as  $articulo) {
            if ($articulo->isCurrentPrecioDifferentThanLista()) {
                $users = $user->getUsersInMyOrg();
                try {
                    Notification::send($users, new PrecioAjustado(
                        $user->name,
                        $this->getConsecutivo(),
                        'Venta con precio ajustado',
                        $articulo->product->name,
                        $articulo->precio_usado
                    ));
                } catch (\Throwable $th) {
                    logger($th);
                }
            }
        }
    }
    public function yaExisteArticuloEnTicket($product)
    {
        return $this->ticket->ventaticket_articulos->pluck('product_id')->contains($product);
    }
    public function getTotal()
    {
        if ($this->total) {
            return $this->total;
        }
        $subTotal = $this->getArticulos()->sum('precio_final');
        $descuentos = $this->getArticulos()->sum('importe_descuento');
        $impuestoTraslado = $this->getArticulos()->sum('impuesto_traslado');
        $impuestoRetenido = $this->getArticulos()->sum('impuesto_retenido');
        return $this->total = $subTotal - $descuentos + $impuestoTraslado - $impuestoRetenido;
    }
    public function getSubTotal()
    {
        return $subTotal = $this->getArticulos()->sum('precio_final');
    }
    public function getGanancia()
    {
        if ($this->ganancia) {
            return $this->ganancia;
        }
        return $this->ganancia = $this->getArticulos()->sum('ganancia');
    }
    public function getImpuestos($type = 'traslado')
    {
        if ($type == 'traslado') {
            return $this->getArticulos()->sum('impuesto_traslado');
        }
        return $this->getArticulos()->sum('impuesto_retenido');
    }
    public function getDescuento()
    {
        if ($this->descuento) {
            return $this->descuento;
        }
        return $this->descuento = $this->getArticulos()->sum('importe_descuento');
    }
    function sendToProduction()
    {
        foreach ($this->getArticulos() as  $articulo) {
            if ($articulo->necesitaProduction()) {
                $articulo->production_order()->create([
                    'organization_id' => $this->ticket->organization_id,
                    'almacen_id' => $this->ticket->almacen_id,
                    'ventaticket_id' => $this->id,
                    'status' => 'pending',
                    'uses_consumable' => $articulo->usesConsumable(),
                    'consumable_deducted' => false
                ]);
            }
        }
    }
    public function getArticulos()
    {
        return $this->ticket->ventaticket_articulos;
    }
    public function getArticuloByProductId($product): VentaticketArticulo
    {
        return $this->ticket->ventaticket_articulos->where('product_id', $product)->first();
    }
    public function getArticuloById($articulo): VentaticketArticulo
    {
        return $this->getArticulos()->where('id', $articulo)->first();
    }
    public function getSpecificAlmacenCliente($id)
    {
        return $this->ticket = Ventaticket::with('latestPreFactura')->find($id);
    }
    public function getArticulosExtended()
    {
        return $this->ticket->getArticulosExtended();
    }
    public function getAlmacen()
    {
        return $this->ticket->almacen_id;
    }
    public function createArticulo($product): VentaticketArticulo
    {
        $ganancia = ($product->precio - $product->product->pcosto) * $product->cantidad;
        $articulo = new VentaticketArticulo();
        $articulo->ventaticket_id = $this->ticket->id;
        $articulo->product_id = $product->id;
        $articulo->product_name = $product->product->name;
        $articulo->departamento_id = null;
        $articulo->cantidad = $product->cantidad;
        $articulo->ancho = $product->ancho;
        $articulo->alto = $product->alto;
        $articulo->area = $product->ancho * $product->alto;
        $articulo->area_total = $articulo->area * $product->cantidad;
        $articulo->ganancia = $ganancia;
        $articulo->pagado_en = null;
        $articulo->importe_descuento = 0;
        $articulo->precio_usado = $product->precio;
        $articulo->cantidad_devuelta = 0;
        $articulo->fue_devuelto = 0;
        $articulo->porcentaje_pagado = null;
        if ($product->product->usa_medidas) {
            $articulo->precio_final = $product->precio * $articulo->area_total;
        } else {
            $articulo->precio_final = $product->precio * $product->cantidad;
        }
        $articulo->agregado_en = null;
        $articulo->save();
        return $articulo;
    }
    function processDevolucion($ticketDevolucion)
    {
        $ventaArticulos = $this->getArticulos();
        $devolucionArticulos = $ticketDevolucion->getArticulos();
        $ventaArticulos = $ventaArticulos->whereIn('id', $devolucionArticulos->pluck('ventaticket_articulo_id'));
        $ganancia = 0;
        foreach ($ventaArticulos as  $articulo) {
            $articuloD = $devolucionArticulos->firstWhere('ventaticket_articulo_id', $articulo->id);
            $articulo->incrementInventario($articuloD->cantidad_devuelta);
            $articulo->setDevuelto();
            $articulo->incrementCantidadDevuelta($articuloD->cantidad_devuelta);
            $ganancia += ($articulo->getGanancia() / $articulo->cantidad) * $articuloD->cantidad_devuelta;
        }
        return $ganancia;
    }
    public function createInventarioHistorial($tipo, $descripcion)
    {
        $user = $this->ticket->user;
        $almacenId = $this->ticket->almacen_id;
        $articulosHistory = [];
        foreach ($this->getArticulos() as  $articulo) {
            if ($articulo->esConsumibleGenerico()) {
                continue;
            }

            $inventarioActual = $articulo->getCantidadInventario($almacenId);
            if ($articulo->usa_medidas()) {
                $cantidadEnTicket = $articulo->area_total;
            } else {
                $cantidadEnTicket = $articulo->cantidad;
            }

            if ($tipo == "increment") {
                $cantidadAnterior = $inventarioActual;
                $cantidadPosterior = $inventarioActual + $cantidadEnTicket;
                $cantidad = $cantidadEnTicket;
            } else {
                $cantidadAnterior = $inventarioActual + $cantidadEnTicket;
                $cantidadPosterior = $inventarioActual;
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

    function checkAllExistingProducts()
    {
        foreach ($this->getArticulos() as $articulo) {
            if (!$articulo->product_id) {
                throw new OperationalException("En el ticket hay productos actualmente eliminados, limpia primero", 1);
            }
        }
    }
    public function getCliente()
    {
        return $this->ticket->cliente_id;
    }
    public function getFormaPago()
    {
        return $this->ticket->forma_de_pago;
    }
    public function setFormaPago($value)
    {

        $this->ticket->forma_de_pago = $value;
    }
    public function getConsecutivo()
    {
        return $this->ticket->consecutivo;
    }
    public function deleteTicket()
    {
        $this->ticket->delete();
    }
    public function setPagadoEn($value)
    {
        $this->ticket->pagado_en = $value;
    }
    public function setEstaAbierto($value)
    {
        $this->ticket->esta_abierto = $value;
    }
    public function setTurno($value)
    {
        $this->ticket->turno_id = $value;
    }
    public function setPagoCon($value)
    {
        $this->ticket->fp_efectivo = $value['efectivo'];
        $this->ticket->fp_efectivo_ref = $value['efectivo_ref'];
        $this->ticket->fp_tarjeta_debito = $value['tarjeta_debito'];
        $this->ticket->fp_tarjeta_debito_ref = $value['tarjeta_debito_ref'];
        $this->ticket->fp_tarjeta_credito = $value['tarjeta_credito'];
        $this->ticket->fp_tarjeta_credito_ref = $value['tarjeta_credito_ref'];
        $this->ticket->fp_transferencia = $value['transferencia'];
        $this->ticket->fp_transferencia_ref = $value['transferencia_ref'];
        $this->ticket->fp_cheque = $value['cheque'];
        $this->ticket->fp_cheque_ref = $value['cheque_ref'];
        $this->ticket->fp_vales_de_despensa = $value['vales_de_despensa'];
        $this->ticket->fp_vales_de_despensa_ref = $value['vales_de_despensa_ref'];
        $this->ticket->pago_con = $value['pago_con'];
    }
    public function setTotal($value)
    {
        $this->ticket->total = $value;
    }
    public function setDescuento($value)
    {

        $this->ticket->descuento = $value;
    }
    public function setGanancia($value)
    {

        $this->ticket->ganancia = $value;
    }
    public function setImpuestosTraslado($value)
    {
        $this->ticket->impuesto_traslado = $value;
    }
    public function setImpuestosRetenido($value)
    {
        $this->ticket->impuesto_retenido = $value;
    }
    public function save()
    {
        $this->ticket->save();
    }
    public function setSubTotal($value)
    {
        $this->ticket->subtotal = $value;
    }
    function refresh()
    {
        $this->ticket->refresh();
    }
    function hasOrder()
    {
        return !!$this->getCartId();
    }
    function getCartId()
    {
        return $this->ticket?->cotizacion?->cart_id;
    }
    function notifyTienda()
    {
        if (!$this->hasOrder()) return;
        $http = new TiendaHttp;
        $cartId = $this->getCartId();
        $path = "/api/cart/$cartId/notify";
        $data = [
            'venta' => $this->ticket->toArray(),
        ];
        return $http->apiRequest('post', $path, $data);
    }
}
