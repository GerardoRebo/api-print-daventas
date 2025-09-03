<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\InventarioBalance;
use App\Models\Product;
use App\Models\User;
use App\Models\Ventaticket;
use App\Models\VentaticketArticulo;
use App\MyClasses\Creditos\RealizarAbono;
use App\MyClasses\PuntoVenta\CreateVentaTicket;
use App\MyClasses\PuntoVenta\ProductArticuloVenta;
use App\MyClasses\PuntoVenta\TicketVenta;
use App\MyClasses\Wha;
use App\Notifications\VentaRealizada;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;


class PuntoVentaController extends Controller
{
    //tested
    public function register(Request $request)
    {
        $user = $request->user();
        $ventaticket = $request->input('ticketActual');
        $product = $request->input('productActualId');
        $precio = $request->input('precio');
        $cantidad = $request->input('cantidad');
        $ancho = $request->input('ancho');
        $alto = $request->input('alto');
        if ($cantidad == null) return "Cantidad Nulo";

        $product = new ProductArticuloVenta($product, $precio, $cantidad, $ancho, $alto);
        $ticketVenta = new TicketVenta($ventaticket);
        $ticketVenta->registerArticulo($product);
    }
    //tested
    public function update()
    {
        $cantidad = request()->input('cantidad');
        $ancho = request()->input('ancho');
        $alto = request()->input('alto');
        $precio = request()->input('precio');
        $articulo = request()->input('articulo');
        $ventaticket = request()->input('ventaticket');
        if ($cantidad == null) return "Cantidad Nulo";

        $ticketVenta = new TicketVenta($ventaticket);
        $articulo = $ticketVenta->getArticuloById($articulo);
        $restaCantidad = $articulo->cantidad - $cantidad;
        $product = new ProductArticuloVenta($articulo->product_id, $precio, $cantidad, $ancho, $alto);
        $ticketVenta->updateArticulo($product, $articulo, $restaCantidad);
    }
    //tested
    public function destroyArticulo(Request $request)
    {
        $user = $request->user();
        $articulo = request()->input('params.articulo');
        $ventaticket = request()->input('params.ventaticket');

        $ticketVenta = new TicketVenta($ventaticket);
        $articulo = $ticketVenta->getArticuloById($articulo);
        $articulo->destroyMe();
    }
    //tested
    public function borrarticket(Request $request)
    {
        $user = $request->user();
        $ventaticket = request()->input('params.ventaticket');

        $ticketVenta = new TicketVenta($ventaticket);

        // foreach ($ticketVenta->getArticulos() as $product) {
        //     $product = new ProductArticuloVenta($product->product_id, null, null);
        //     $articulo = $ticketVenta->getArticuloByProductId($product->id);
        //     $articulo->incrementInventario($articulo->cantidad);
        // }

        $ticketVenta->deleteTicket();
    }
    //tested
    public function guardarVenta(Request $request)
    {
        $request->validate([
            'ventaticket' => 'required|integer',
            'forma_pago' => 'required|array',
            'credito' => 'required|boolean',

            // Validate that "pago_con" is numeric
            'forma_pago.pago_con' => 'required|numeric',

            // Validate that each *_ref field is a string
            'forma_pago.efectivo_ref' => 'nullable|string',
            'forma_pago.tarjeta_debito_ref' => 'nullable|string',
            'forma_pago.tarjeta_credito_ref' => 'nullable|string',
            'forma_pago.transferencia_ref' => 'nullable|string',
            'forma_pago.cheque_ref' => 'nullable|string',
            'forma_pago.vales_de_despensa_ref' => 'nullable|string',

            // Validate the other forma_pago fields as numeric
            'forma_pago.efectivo' => 'required|numeric',
            'forma_pago.tarjeta_debito' => 'required|numeric',
            'forma_pago.tarjeta_credito' => 'required|numeric',
            'forma_pago.transferencia' => 'required|numeric',
            'forma_pago.cheque' => 'required|numeric',
            'forma_pago.vales_de_despensa' => 'required|numeric',
        ]);

        /** @var User $user */
        $user = $request->user()->load('configuration');
        $ventaticket = request()->input('ventaticket');
        $forma_pago = request()->input('forma_pago');
        $credito = request()->input('credito');

        $turno = $user->getLatestTurno();

        if (!$turno) {
            throw new OperationalException("No has habilitado la caja, seras redireccionado", 1);
        }
        $ticketVenta = new TicketVenta($ventaticket);
        $notEnoughInventory = $ticketVenta->ticket->checkArticulosEnoughInventory();
        if (count($notEnoughInventory)) {
            $productsList = implode(", ", $notEnoughInventory);
            throw new OperationalException("Los siguientes productos no tienen suficiente inventario: " . $productsList, 1);
        }
        if ($ticketVenta->ticket->pagado_en) {
            return;
        }
        $turno->guardarVenta($ticketVenta, $forma_pago, $credito);

        return;
    }
    //tested
    public function cancelarventa(Request $request)
    {
        $ventaticket = request()->input('params.ticket');
        $user = $request->user()->load('configuration');
        /** @var User $user */
        $turno = $user->getLatestTurno();
        if (!$turno) {
            throw new OperationalException("No has habilitado la caja", 1);
        }
        $ticketVenta = new TicketVenta($ventaticket);

        if ($ticketVenta->ticket->facturado_en) {
            throw new Exception("No es posible cancelar, el ticket ha sido facturado", 1);
        }

        $almacen = $ticketVenta->getAlmacen();
        $ticketVenta->createInventarioHistorial("increment", "Cancelación Venta");

        $users = $user->getUsersInMyOrg();
        Notification::send($users, new VentaRealizada($user->name, $ticketVenta->getConsecutivo(),  'Venta Cancelada'));

        foreach ($ticketVenta->getArticulos() as $articulo) {
            if ($articulo->usa_medidas) {
                $articulo->incrementInventario($articulo->area_total);
            } else {
                $articulo->incrementInventario($articulo->cantidad);
            }
        }

        $ticketVenta->ticket->update([
            'esta_cancelado' => 1,
        ]);
        if ($ticketVenta->getFormaPago() == 'C') {
            $turno->increment('devoluciones_ventas_credito', $ticketVenta->getTotal());
            $rA = new RealizarAbono;
            $rA->realizarAbono($ticketVenta->ticket->deuda->id, $user, null, "Cancelacion venta", $turno);
        } else {
            $turno->increment('devoluciones_ventas_efectivo', $ticketVenta->getTotal());
            $turno->decrement('efectivo_al_cierre', $ticketVenta->getTotal());
        }
        $turno->decrement('acumulado_ganancias', $ticketVenta->ticket->ganancia);
        return;
    }
    public function getVT(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $ventaticket = $user->getVentaticketAlmacenCliente($user);
        return [$ventaticket, $ventaticket->getArticulosExtended($user)];
    }
    public function getLastVentaticket(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $ventaticket = $user->getLastVentaTicket($user);
        return $ventaticket;
    }
    public function specific(Request $request)
    {
        $user = $request->user();
        $id = request()->input('ventaticket');
        $ticketVenta = new TicketVenta();
        $venta = $ticketVenta->getSpecificAlmacenCliente($id);
        return [$venta, $ticketVenta->getArticulosExtended($user)];
    }
    public function getSpecificVTForPrinting(Request $request)
    {
        $user = $request->user();
        $id = $request->ventaticket;
        return Ventaticket::with(
            'ventaticket_articulos',
            'organization.image',
            'organization.facturacion_info:infoable_id,razon_social',
            'deuda.abonos'
        )->findOrFail($id);
    }
    //tested
    public function asignarAlmacen(Request $request)
    {
        $ventaticket = Ventaticket::find(request()->input('ventaticket'));

        $ventaticket->update([
            'almacen_id' => request()->input('almacen')
        ]);
        return $ventaticket;
    }
    public function setnombreticket()
    {
        $nombreTicket = request()->input('params.nombre');
        $ventaticket = Ventaticket::find(request()->input('params.ticket'));
        $ventaticket->nombre = $nombreTicket;
        $ventaticket->save();
    }
    public function sendVentaToWha($ticketId, Wha $wha)
    {
        request()->validate([
            'telefono' => 'string|digits:10',
        ]);
        $ventaticket = Ventaticket::find($ticketId);
        $almacenId = $ventaticket->almacen_id;
        $phone = request()->get('telefono');
        $content = $ventaticket->generateTicketText();
        return response()->json(
            [
                'success' => true,
                'text' => $content
            ]
        );
        $wha->sendMessage($almacenId, $phone, $content);
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
        $pendientes = Ventaticket::with('ventaticket_articulos')->where('organization_id', $user->organization_id)
            ->where('esta_abierto', 1)->where('pendiente', 1)
            ->where('user_id', $user->id)
            ->get();

        $pendientes = $pendientes->map(function ($item, $key) {
            $total = $item->ventaticket_articulos->sum('precio_final');
            $item->total = $total;
            return $item;
        });

        return $pendientes;
    }
    public function setpendiente()
    {
        $ventaticket = Ventaticket::find(request()->input('ventaticket'));
        $ventaticket->pendiente = 1;
        $ventaticket->save();
    }
    public function misventas(Request $request)
    {
        $user = $request->user();
        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));
        $misventas = Ventaticket::where('esta_abierto', 0)
            ->where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->whereBetween('pagado_en', [$dfecha, $fecha])
            ->orderBy('pagado_en', 'desc')
            ->paginate(20);
        return $misventas;
    }
    public function verificarVentas(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $turno = $user->getLatestTurno();
        $misventas = Ventaticket::where('esta_abierto', 0)
            ->where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('turno_id', $turno->id)
            ->sum('total');
        return $misventas;
    }
    public function syncLocalVentas(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $turno = $user->getLatestTurno();
        $tickets = $request->tickets;
        $deleteTickets = [];
        foreach ($tickets as $ticket) {
            try {
                $puntoVentaLogic = new CreateVentaTicket;
                $ventaticket = $puntoVentaLogic->creaTicket($user);
                $ventaticket->nombre = "Local #" . $ticket['ticket_id'] . " " . $ticket['nombre'];
                $ventaticket->almacen_id = $ticket['almacen_id'];
                $ventaticket->cliente_id = $ticket['cliente_id'];
                $ventaticket->save();
                $ticketVenta = new TicketVenta($ventaticket->id);
                $credito = filter_var($ticket['credito'], FILTER_VALIDATE_BOOLEAN);

                foreach ($ticket['articulos'] as $articulo) {
                    if ($articulo['cantidad'] === null || $articulo['cantidad'] === 0) {
                        continue;
                    }
                    $ticketVenta->registerArticulo(new ProductArticuloVenta($articulo['product_id'], $articulo['precio'], $articulo['cantidad']));
                }
                $ticketVenta->refresh();
                $turno->guardarVenta($ticketVenta, $ticket['pago_con'], $credito);
                $deleteTickets[] = $ticket['ticket_id'];
            } catch (\Throwable $th) {
                logger($th->getMessage() . " File:" . $th->getFile() . " Line:" . $th->getLine());
                $deleteTickets[] = $ticket['ticket_id'];
            }
        }
        return $deleteTickets;
    }
    public function facturar(Request $request, $ticket)
    {
        $request->validate([
            'forma_pago' => 'required|string',
            'metodo_pago' => 'required|string',
            'uso_cfdi' => 'required|string',
            'serie' => 'nullable|string',
            'clave_privada_local' => 'required|string',
        ]);
        $formaPago = $request->forma_pago;
        $metodoPago = $request->metodo_pago;
        $usoCfdi = $request->uso_cfdi;
        $serie = $request->serie;
        $clavePrivadaLocal = $request->clave_privada_local;

        $ventaticket = Ventaticket::with(
            'ventaticket_articulos.product',
            'ventaticket_articulos.taxes.tax',
            'organization.facturacion_info',
        )
            ->findOrFail($ticket);
        $user = $request->user()->load('configuration');
        /** @var Ventaticket $ventaticket */
        $saldo = $user->organization->latestFoliosUtilizado;
        $saldoScalar = $saldo?->saldo ?? 0;
        if (!$saldoScalar) {
            throw new OperationalException("No cuentas con suficientes timbres fiscales, , contacta con la administración para solicitar timbres fiscales", 1);
        }
        return $ventaticket->facturarVenta($formaPago, $metodoPago, $usoCfdi, $serie, $clavePrivadaLocal);

        return "Facturacion Exitosa";
    }
    function acceptRetentionRules(Ventaticket $ventaticket)
    {
        $user = auth()->user();
        $organization = $user->organization;
        // if ($ventaticket->ventaticket_articulos->count()) {
        //     throw new OperationalException("El ticket no debe tener articulos", 1);
        // }
        $ventaticket->retention = true;
        $ventaticket->save();
        $retentionRules = $organization->getClientRetentionRules($ventaticket?->cliente?->regimen_fiscal);
        foreach ($retentionRules as $rule) {
            $ventaticket->retention_rules()->create([
                'retention_rule_id' => $rule->id,
                'organization_id' => $organization->id,
                'regimen_fiscal' => $ventaticket->cliente->regimen_fiscal,
                'isr_percentage' => $rule->isr_percentage,
                'iva_percentage' => $rule->iva_percentage,
            ]);
        }
        if ($ventaticket->esta_abierto) {
            foreach ($ventaticket->ventaticket_articulos as $articulo) {
                $articulo->impuesto_retenido = $articulo->getRetencionTaxesAmount();
                $articulo->save();
            }
        }

        return $ventaticket;
    }
    function descargarXml(Request $request, Ventaticket $ticket)
    {
        $user = auth()->user();
        if (Storage::exists($ticket->xml_factura_path)) {
            // Get the file content from S3
            $fileContent = Storage::get($ticket->xml_factura_path);
            // Return the file as a response with appropriate headers
            return response($fileContent)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'attachment; filename="factura_xml_"');
            // return response()->download($xmlPath, "factura_xml_");
        }
        throw new Exception("Archivo no encontrado", 1);
    }
    function descargarPdf(Request $request, Ventaticket $ticket)
    {
        $user = auth()->user();
        if (Storage::exists($ticket->pdf_factura_path)) {
            $fileContent = Storage::get($ticket->pdf_factura_path);
            return response($fileContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="factura_pdf_"');
        }
        throw new Exception("Archivo no encontrado", 1);
    }
    function updateDescription(Request $request, VentaticketArticulo $articulo)
    {
        $user = auth()->user();
        $description = request()->input('description');
        logger($description);
        $articulo->update([
            'description' => $description
        ]);
        return $articulo;
    }
    // function global(Request $request)
    // {
    //     $request->validate([
    //         'desde' => 'required|date',
    //         'hasta' => 'required|date',
    //     ]);
    //     $user = auth()->user();
    //     $organization = $user->organization;
    //     $desde = $request->desde;
    //     $hasta = $request->hasta;

    //     // Convert to Carbon instances
    //     $desde = Carbon::parse($desde);
    //     $hasta = Carbon::parse($hasta);

    //     // Calculate the difference in months
    //     $differenceInMonths = $desde->diffInMonths($hasta);

    //     // Check if the difference is less than or equal to 6 months
    //     if ($differenceInMonths >= 6) {
    //         throw new OperationalException("El rango de fechas es excesivo", 1);
    //     }
    //     return $organization->getVentatickets($desde, $hasta);
    // }
    // function timbrarFacturaGlobal(Request $request)
    // {
    //     return $request->all();
    //     $request->validate([
    //         'desde' => 'required|date',
    //         'hasta' => 'required|date',
    //     ]);
    //     $user = auth()->user();
    //     $organization = $user->organization;

    //     // return $organization->getVentatickets($desde, $hasta);
    // }
}
