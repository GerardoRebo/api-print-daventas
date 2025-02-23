<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\Cotizacion;
use App\Models\InventarioBalance;
use App\Models\Product;
use App\Models\User;
use App\MyClasses\Creditos\RealizarAbono;
use App\MyClasses\PuntoVenta\ProductArticuloCotizacion;
use App\MyClasses\PuntoVenta\ProductArticuloVenta;
use App\MyClasses\PuntoVenta\TicketVenta;
use App\MyClasses\Wha;
use App\Notifications\VentaRealizada;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CotizacionController extends Controller
{
    //tested
    public function register(Request $request)
    {
        $user = $request->user();
        $cotizacion = $request->input('params.ticketActual');
        $product = $request->input('params.productActualId');
        $precio = $request->input('params.precio');
        $cantidad = $request->input('params.cantidad');
        if ($cantidad == null) return "Cantidad Nulo";

        $product = new ProductArticuloCotizacion($product, $precio, $cantidad);
        $cotizacion = Cotizacion::findOrFail($cotizacion);
        $cotizacion->registerArticulo($product);
    }
    //tested
    public function update()
    {
        $cantidad = request()->input('params.cantidad');
        $precio = request()->input('params.precio');
        $articulo = request()->input('params.articulo');
        $cotizacion = request()->input('params.cotizacion');
        if ($cantidad == null) return "Cantidad Nulo";

        $cotizacion = Cotizacion::findOrFail($cotizacion);
        $articulo = $cotizacion->getArticuloById($articulo);
        $restaCantidad = $articulo->cantidad - $cantidad;
        $product = new ProductArticuloVenta($articulo->product_id, $precio, $cantidad);
        $cotizacion->updateArticulo($product, $articulo, $restaCantidad);
    }
    //tested
    public function destroyArticulo(Request $request)
    {
        $user = $request->user();
        $articulo = request()->input('params.articulo');
        $cotizacion = request()->input('params.cotizacion');

        $cotizacion = Cotizacion::find($cotizacion);
        $articulo = $cotizacion->getArticuloById($articulo);
        $articulo->destroyMe();
    }
    //tested
    public function borrarticket(Request $request)
    {
        $user = $request->user();
        $cotizacion = request()->input('params.cotizacion');

        $cotizacion = Cotizacion::findOrFail($cotizacion);

        $cotizacion->delete();
    }
    //tested
    public function guardarVenta(Request $request)
    {
        $request->validate([
            'cotizacion' => 'required|integer',
        ]);
        /** @var User $user */
        $user = $request->user()->load('configuration');
        $cotizacion = request()->input('cotizacion');

        $turno = $user->getLatestTurno();

        if (!$turno) {
            throw new OperationalException("No has habilitado la caja, seras redireccionado", 1);
        }
        $cotizacion = Cotizacion::findOrFail($cotizacion);
        $cotizacion->checkAllExistingProducts();
        $turno->finalizarCotizacion($cotizacion);
        return;
    }
    //tested
    public function cancelarventa(Request $request)
    {
        $cotizacion = request()->input('params.ticket');
        $user = $request->user()->load('configuration');
        /** @var User $user */
        $turno = $user->getLatestTurno();
        if (!$turno) {
            throw new OperationalException("No has habilitado la caja", 1);
        }
        $cotizacion = new TicketVenta($cotizacion);

        if ($cotizacion->ticket->facturado_en) {
            throw new Exception("No es posible cancelar, el ticket ha sido facturado", 1);
        }

        $almacen = $cotizacion->getAlmacen();
        $cotizacion->createInventarioHistorial("increment", "Cancelación Venta");

        $users = $user->getUsersInMyOrg();
        Notification::send($users, new VentaRealizada($user->name, $cotizacion->getConsecutivo(),  'Venta Cancelada'));

        foreach ($cotizacion->getArticulos() as $articulo) {
            $articulo->incrementInventario($articulo->cantidad);
        }

        $cotizacion->ticket->update([
            'esta_cancelado' => 1,
        ]);
        if ($cotizacion->getFormaPago() == 'C') {
            $turno->increment('devoluciones_ventas_credito', $cotizacion->getTotal());
            $rA = new RealizarAbono;
            $rA->realizarAbono($cotizacion->ticket->deuda->id, $user, null, "Cancelacion venta", $turno);
        } else {
            $turno->increment('devoluciones_ventas_efectivo', $cotizacion->getTotal());
            $turno->decrement('efectivo_al_cierre', $cotizacion->getTotal());
        }
        $turno->decrement('acumulado_ganancias', $cotizacion->ticket->ganancia);
        return;
    }
    public function getVT(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $cotizacion = $user->getCotizacionAlmacenCliente($user);
        return [$cotizacion, $cotizacion->getArticulosExtended($user)];
    }
    public function specific(Request $request)
    {
        $user = $request->user();
        $id = request()->input('cotizacion');
        $cotizacion = Cotizacion::findOrFail($id);
        if ($cotizacion->user_id && $cotizacion->user_id != $user->id) {
            throw new OperationalException("Otro usuario ya tomo esta orden", 1);
        }
        if (!$cotizacion->user_id) {
            $cotizacion->user_id= $user->id;
            $cotizacion->save();
        }
        return [$cotizacion, $cotizacion->getArticulosExtended($user)];
    }
    public function getSpecificVTForPrinting(Request $request)
    {
        $user = $request->user();
        $id = $request->cotizacion;
        return Cotizacion::with('articulos')->findOrFail($id);
    }
    //tested
    public function asignarAlmacen(Request $request)
    {
        $cotizacion = Cotizacion::find(request()->input('cotizacion'));
        $cotizacion->update([
            'almacen_id' => request()->input('almacen')
        ]);

        $cotizacion->setPrecios();

        return $cotizacion;
    }
    public function setnombreticket()
    {
        $nombreTicket = request()->input('params.nombre');
        $cotizacion = Cotizacion::find(request()->input('params.ticket'));
        $cotizacion->nombre = $nombreTicket;
        $cotizacion->save();
    }
    public function sendVentaToWha($ticketId, Wha $wha)
    {
        request()->validate([
            'telefono' => 'string|digits:10',
        ]);
        $cotizacion = Cotizacion::find($ticketId);
        $almacenId = $cotizacion->almacen_id;
        $phone = request()->get('telefono');
        $content = $cotizacion->generateTicketText();
        return $wha->sendMessage($almacenId, $phone, $content);
    }
    public function archivar($cotizacionId)
    {
        request()->validate([
            'telefono' => 'string|digits:10',
        ]);
        $cotizacion = Cotizacion::findOrFail($cotizacionId);
        $cotizacion->archivado;
        return response()->json(['success' => true]);
    }
    public function getexistencias(Request $request)
    {
        $user = $request->user();
        $product = Product::find(request()->input('productId'));

        if ($product->es_kit) {
            foreach ($product->product_components as $componente) {

                $inventario = InventarioBalance::with('product')->where('product_id', $componente->product_hijo_id)->get();
                $inventario->concat($inventario);
            }
            $existencias = $inventario;
        } else {
            $existencias = InventarioBalance::with('product')->where('inventario_balances.product_id', $product->id)
                ->get();
        }
        return $existencias;
    }
    public function pendientes(Request $request)
    {
        $user = $request->user();
        $base = Cotizacion::with('articulos')
            ->where('organization_id', $user->organization_id)
            ->where('esta_abierto', 1)
            ->where('pendiente', 1)
            ->where('archivado', 0);
        $pendientes = clone $base;
        $base = $base->where('user_id', $user->id)->get() ;
        $pendientes = $pendientes->whereNull('user_id')->get() ;
        $pCollection = $pendientes->concat($base);
        $pCollection = $pCollection->map(function ($item, $key) {
            $total = $item->articulos->sum('importe');
            $item->total = $total;
            return $item;
        });

        return $pCollection;
    }
    public function setpendiente()
    {
        $cotizacion = Cotizacion::find(request()->input('cotizacion'));
        $cotizacion->pendiente = 1;
        $cotizacion->save();
    }
    public function misventas(Request $request)
    {
        $user = $request->user();
        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));
        $misventas = Cotizacion::where('esta_abierto', 0)
            ->where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->whereBetween('enviada_en', [$dfecha, $fecha])
            ->orderBy('enviada_en', 'desc')
            ->paginate(8);
        return $misventas;
    }
    public function setcliente()
    {
        $cotizacion = Cotizacion::findOrFail(request()->input('cotizacion'));
        $cliente = request()->input('cliente');
        $cotizacion->update([
            'cliente_id' => $cliente
        ]);
    }
}
