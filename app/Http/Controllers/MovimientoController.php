<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Exceptions\OperationalRefreshException;
use App\Models\Almacen;
use App\Models\ArticulosOc;
use App\Models\InventarioBalance;
use App\Models\OrdenCompra;
use App\Models\Product;
use App\Models\User;
use App\MyClasses\Movimientos\ProductArticuloCompra;
use App\MyClasses\Movimientos\TicketCompra;
use App\Notifications\CompraRealizada;
use App\Notifications\TransferenciaRealizada;
use App\Notifications\VentaRealizada;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class MovimientoController extends Controller
{
    public function register(Request $request)
    {
        // Get the input parameters
        $ordencompra = $request->input('params.movimiento');
        $product = $request->input('params.productActualId');
        $costo = $request->input('params.precio');
        $cantidad = $request->input('params.cantidad');

        // Create a new ProductArticuloCompra object
        $product = new ProductArticuloCompra($product, $costo, $cantidad);

        // Create a new TicketCompra object
        $ticketCompra = new TicketCompra($ordencompra);

        return $ticketCompra->registerArticulo($product);
    }
    public function update(Request $request)
    {
        $articulo = request()->input('params.articulo');
        $costo = request()->input('params.costo');
        $cantidad = request()->input('params.cantidad');
        $ordencompra = request()->input('params.movimiento');

        $product = new ProductArticuloCompra($articulo, $costo, $cantidad, 'article');
        $ticketCompra = new TicketCompra($ordencompra);
        return $ticketCompra->updateArticulo($product);
    }
    public function guardar(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('configuration');
        $ordencompra = request()->input('movimiento');
        $cambiaUsuario = false;

        $turno = $user->getLatestTurno();

        if (!$turno) throw new  OperationalException("No has habilitado la caja", 1);

        $ticketCompra = new TicketCompra($ordencompra);

        if ($ticketCompra->ticket->estado == "R") {
            throw new  OperationalRefreshException("Movimiento ya esta procesado", 1);
        }
        if ($ticketCompra->ticket->tipo == "S") {
            $cambiaUsuario = true;
        }
        $almacenO = $ticketCompra->getAlmacenOrigen();
        $almacenD = $ticketCompra->getAlmacenDestino();

        if ($ticketCompra->getTipo() == "C") {
            $users = $user->getUsersInMyOrg();
            $prov = "";
            if ($ticketCompra->ticket->proveedor) {
                $prov = $ticketCompra->ticket->proveedor->name;
            }
            Notification::send($users, new CompraRealizada(
                $user->name,
                $ticketCompra->ticket->almacen_origen->name,
                $prov,
                'Compra'
            ));

            $ticketCompra->attachProductsToProveedor();
            $ticketCompra->incrementInventario($almacenO);
            $ticketCompra->createInventarioHistorial($almacenO, "increment", "Compra");
            $turno->increment('compras', $ticketCompra->getSubtotal());
        } else {
            $usersO = $user->getUsersAccordingAlmacen($almacenO);
            $usersD = $user->getUsersAccordingAlmacen($almacenD);
            $usersNotif = $usersO->union($usersD);
            $usersNotif = $usersNotif->unique('id');
            Notification::send($usersNotif, new TransferenciaRealizada(
                $user->name,
                $ticketCompra->ticket->almacen_origen->name,
                $ticketCompra->ticket->almacen_destino->name,
                'Tranferencia'
            ));
            $ticketCompra->incrementInventario($almacenD);
            $ticketCompra->createInventarioHistorial($almacenD, "increment", "Transferencia Recibida");
            $ticketCompra->createInventarioHistorial($almacenO, "decrement", "Transferencia Enviada");
        }
        $ticketCompra->ticketUpdate($cambiaUsuario, $user, $turno->id);
        return;
    }
    public function cancelarmovimiento(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('configuration');
        $ordencompra = request()->input('movimiento');
        $turno = $user->getLatestTurno();
        if (!$turno) throw new  OperationalException("No has habilitado la caja", 1);
        $ticketCompra = new TicketCompra($ordencompra);

        if ($ticketCompra->getTipo() == 'C') {
            $ticketCompra->cancelarCompra();
        } else {
            $ticketCompra->cancelarTransferencia();
        }

        $users = $user->getUsersInMyOrg();
        Notification::send($users, new VentaRealizada($user->name, $ticketCompra->getConsecutivo(),  'Movimiento Cancelado'));
    }
    public function getSpecificTicketForPrinting(Request $request)
    {
        $user = $request->user();
        $id = $request->ticket;
        return OrdenCompra::with('articulos_ocs')->findOrFail($id);
    }
    public function getVT(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $compraTicket = $user->getCompraticketAlmacenCliente($user);
        return [$compraTicket, $compraTicket->getArticulosExtended($user)];
    }
    public function specific(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $id = request()->input('movimiento');
        $ordenCompra = OrdenCompra::findOrFail($id);
        return [$ordenCompra, $ordenCompra->getArticulosExtended($user)];
    }
    public function destroyArticulo(Request $request)
    {
        $user = $request->user();
        $ordencompra = request()->input('movimiento');
        $articulo = request()->input('articulo');
        $articulo = ArticulosOc::findOrFail($articulo);
        $ticketCompra = new TicketCompra($ordencompra);

        if ($ticketCompra->ticket->tipo == "T") {
            $articulo->incrementInventario($articulo->cantidad_ordenada, $ticketCompra->ticket->almacen_origen_id);
        }
        $articulo->delete();
    }
    public function asignarAlmacen()
    {
        /** @var User $user */
        $user = auth()->user();
        $almacenO = request()->input('almacen');
        $almacenD = request()->input('almacenDestino');
        $almacens = Almacen::whereIn('id', [$almacenO, $almacenD])->get();
        $almacenO = $almacens->firstWhere('id', $almacenO);
        $almacenD = $almacens->firstWhere('id', $almacenD);
        $movimiento = OrdenCompra::find(request()->input('movimiento'));


        if ($movimiento->tipo == "S") {
            $usersO = $user->getUsersAccordingAlmacen($almacenO->id);
            $usersD = $user->getUsersAccordingAlmacen($almacenD->id);
            $usersNotif = $usersO->union($usersD);
            $usersNotif = $usersNotif->unique('id');

            Notification::send($usersNotif, new TransferenciaRealizada(
                $user->name,
                $almacenO->name,
                $almacenD->name,
                'Creando S.Transferencia'
            ));
        }
        if ($movimiento->tipo == "C") {

            return $movimiento->update([
                'almacen_origen_id' => $almacenO->id,
            ]);
        }
        $movimiento->update([
            'almacen_origen_id' => $almacenO->id,
            'almacen_destino_id' => $almacenD->id
        ]);
    }
    public function setnombreticket()
    {
        $nombreTicket = request()->input('params.nombre');
        $ventaticket = OrdenCompra::find(request()->input('params.ticket'));
        $ventaticket->nombre = $nombreTicket;
        $ventaticket->save();
    }
    public function borrarticket(Request $request)
    {
        $ordencompra = request()->input('movimiento');
        /** @var User $user */
        $user = $request->user()->load('configuration');

        $ticketCompra = new TicketCompra($ordencompra);
        if ($ticketCompra->getEstado() == "R") {
            throw new  OperationalException("Este ticket ya esta procesado", 1);
        }

        if ($ticketCompra->getTipo() != 'C') {
            $ticketCompra->incrementInventario($ticketCompra->ticket->almacen_origen_id);
        }

        $ticketCompra->ticket->delete();
    }
    public function getexistencias()
    {
        $product = Product::find(request()->input('productId'));

        if ($product->es_kit) {

            foreach ($product->product_components as $componente) {

                $inventario = InventarioBalance::where('product_id', $componente->product_hijo_id)->get();
                $inventario->concat($inventario);
            }

            $existencias = $inventario;
        } else {
            $existencias = InventarioBalance::where('inventario_balances.product_id', $product->id)->get();
        }
        return $existencias;
    }
    public function pendientes(Request $request)
    {
        $user = $request->user();
        $almacens = $user->almacens->pluck('id');
        $pendientes = OrdenCompra::where('organization_id', $user->organization_id)
            ->where('estado', 'B')->where('user_id', $user->id)->get();
        $masPendientes = OrdenCompra::whereIn('almacen_origen_id', $almacens)
            ->where('tipo', 'S')
            ->where('estado', 'B')
            ->get();

        $pendientes = $pendientes->concat($masPendientes);
        $pendientes = $pendientes->map(function ($item, $key) {
            $item->load('articulos_ocs');
            $total = $item->articulos_ocs->sum('total_al_ordenar');
            $item->total_enviado = $total;
            return $item;
        });
        return $pendientes;
    }
    public function setpendiente()
    {
        $movimiento = OrdenCompra::find(request()->input('movimiento'));

        if ($movimiento->estado == "R") {
            return "Ya esta recibido";
        }
        $movimiento->pendiente = 1;
        $movimiento->save();
    }
    public function getOcById(Request $request)
    {
        $user = $request->user();
        $folio = request()->input('folio');
        return  OrdenCompra::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->where('consecutivo', $folio)
            ->paginate();
    }
    public function mismovimientos(Request $request)
    {
        $user = $request->user();
        $dfecha = request()->input('dfecha', getMysqlDate($user->configuration?->time_zone));
        $hfecha = request()->input('hfecha', getMysqlDate($user->configuration?->time_zone));
        $estado = request()->input('estadomovimiento');
        $items_per_page = request()->input('items_per_page', 10);
        $consecutivo = request()->input('consecutivo');
        $tipoMovimiento = request()->input('tipoMovimiento');
        $proveedor = request()->input('proveedor');
        $almacen = request()->input('almacen');
        if ($estado == '') {
            $estado = null;
        }
        return  OrdenCompra::with('user')->when($estado, function ($query, $estado) {
            return $query->where('estado', $estado);
        }, function ($query, $estado) {
            return $query->where('estado', '!=', 'B');
        })->when($proveedor, function ($query) use ($proveedor) {
            return $query->where('proveedor_id', $proveedor);
        })
            ->when($tipoMovimiento != null, function ($query) use ($tipoMovimiento) {
                return $query->where('tipo', $tipoMovimiento);
            })
            ->when($consecutivo, function ($query) use ($consecutivo) {
                return $query->where('consecutivo', $consecutivo);
            })
            ->when($almacen, function ($query) use ($almacen) {
                return $query->where('almacen_origen_id', $almacen);
            })
            ->where('organization_id', $user->organization_id)

            // ->whereBetween('enviada_en', [$dfecha . ' 00:00:00', $hfecha . ' 23:59:59'])
            ->whereDate('enviada_en', '>=', $dfecha)
            ->whereDate('enviada_en', '<=', $hfecha)
            ->orderBy('enviada_en', 'desc')
            ->paginate($items_per_page);
    }
    public function setmovimiento()
    {
        $name = auth()->user()->name;
        $tipo = request()->input('params.tipomovimiento');
        $movimiento = OrdenCompra::findOrFail(request()->input('params.movimiento'));
        $movimiento->tipo = $tipo;
        $movimiento->save();
    }
    public function cambiaprecio(Request $request)
    {
        $request->validate([
            'precio' => 'required|numeric',
            'producto' => 'required|integer',
            'almacen' => 'required|integer',
        ]);

        $user = auth()->user();
        $precio = request()->input('precio');
        $product = request()->input('producto');
        $almacen = request()->input('almacen');
        $almacen = Almacen::find($almacen,);
        $product = Product::find($product);

        $almacen->processCambioPrecio($user, $product, $precio);
    }
    public function cambiaPrecioGeneral(Request $request)
    {
        $request->validate([
            'precio' => 'required|numeric',
            'producto' => 'required|integer',
        ]);
        /** @var User $user */
        $user = auth()->user();
        $precio = request()->input('precio');
        $product = request()->input('producto');
        $product = Product::find($product);

        foreach ($user->getMyOrgAlmacens() as $almacen) {
            $almacen->processCambioPrecio($user, $product, $precio, true);
        }
    }
    public function setproveedor()
    {
        $movimiento = OrdenCompra::find(request()->input('params.movimiento'));
        $proveedor = request()->input('params.proveedor');
        $movimiento->update([
            'proveedor_id' => $proveedor
        ]);
    }
    public function updateFolioFactura()
    {
        $movimiento = OrdenCompra::find(request()->input('movimiento'));
        $folioFactura = request()->input('folioFactura');
        $movimiento->update([
            'factura_uuid' => $folioFactura
        ]);
    }
}
