<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\Devolucione;
use App\Models\DevolucionesArticulo;
use App\Models\User;
use App\Models\Ventaticket;
use App\Models\VentaticketArticulo;
use App\MyClasses\Devoluciones\TicketDevolucion;
use App\MyClasses\PuntoVenta\TicketVenta;
use App\Notifications\VentaRealizada;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class DevolucionController extends Controller
{
    public function register()
    {
        $devolucion = request()->input('devolucion');
        $articulo = request()->input('articulo');
        $cantidad = request()->input('cantidad');
        if ($cantidad < 1) {
            throw new OperationalException("Solo se aceptan cantidades positivas", 1);
        }
        $articulo = VentaticketArticulo::find($articulo);
        $devolucion = Devolucione::find($devolucion);

        $devolucion->registerArticulo($articulo, $cantidad);
    }
    public function specificDevolucion(Request $request)
    {
        $user = $request->user();
        $id = request()->input('ventaticket');
        $ticketDevolucion = new TicketDevolucion();
        $devolucion = $ticketDevolucion->getSpecific($id);
        return [$devolucion, $ticketDevolucion->getArticulosExtended()];
    }
    public function getSpecificTicketForPrinting(Request $request)
    {
        $user = $request->user();
        $id = $request->ticket;
        return Devolucione::with('ventaticket.almacen', 'devoluciones_articulos.product', 'user')->findOrFail($id);
    }
    public function misDevoluciones(Request $request)
    {
        $user = $request->user();
        $dfecha = request('dfecha', getMysqlDate($user->configuration?->time_zone));
        $hfecha = request('hfecha', getMysqlDate($user->configuration?->time_zone));
        $misDevoluciones = Devolucione::where('organization_id', $user->active_organization_id)
            ->where('user_id', $user->id)
            ->whereDate('devuelto_en', '>=', $dfecha)
            ->whereDate('devuelto_en', '<=', $hfecha)
            ->orderBy('devuelto_en', 'desc')
            ->paginate(12);
        return $misDevoluciones;
    }
    public function destroyArticulo(Request $request)
    {
        $id = request()->input('id');
        DevolucionesArticulo::find($id)->delete();
    }
    public function createDevolucion(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('configuration');
        $venta = $request->input('venta');
        $turno = $user->getLatestTurno();
        if (!$turno) throw new  OperationalException("No has habilitado la caja", 1);
        $venta = Ventaticket::with('ventaticket_articulos.taxes')->find($venta);
        if ($venta->total_devuelto >= $venta->total) {
            throw new  OperationalException("Venta se ha devuelto por completo", 1);
        }
        return $turno->createDevolucion($venta);
    }
    public function eliminarDevolucion(Request $request)
    {
        $devolucion = request()->input('devolucion');
        Devolucione::destroy($devolucion);
    }
    public function realizarDevolucion(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('configuration');
        $turno = $user->getLatestTurno();
        $ticketDevolucion = request()->input('ticket');

        if (!$turno) throw new  OperationalException("No has habilitado la caja", 1);

        $ticketDevolucion = new TicketDevolucion($ticketDevolucion);
        $ticketVenta = new TicketVenta($ticketDevolucion->ticket->ventaticket_id);
        $ganancia = $ticketVenta->processDevolucion($ticketDevolucion);
        $total = $ticketDevolucion->ticket->devoluciones_articulos->sum('dinero_devuelto');

        $ticketDevolucion->createInventarioHistorial("increment", "Devolución Venta");

        $ticketVenta->ticket->increment('total_devuelto', $total);
        $ticketDevolucion->ticket->increment('total_devuelto', $total);
        $ticketDevolucion->ticket->update([
            "turno_id" => $turno->id,
            "tipo_devolucion" => 'C',
            "devuelto_en" => getMysqlTimestamp($user->configuration?->time_zone),
        ]);

        $turno->increment('devoluciones_ventas_efectivo', $total);
        $turno->decrement('acumulado_ganancias', $ganancia);
        $users = $user->getUsersInMyOrg();
        Notification::send($users, new VentaRealizada($user->name, $ticketVenta->getConsecutivo(),  'Devolucion de Mercancia en el Ticket'));
    }
}
