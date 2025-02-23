<?php

namespace App\Http\Controllers;

use App\Models\ArticuloTax;
use App\Models\Concepto;
use App\Models\MovimientoCaja;
use App\Models\Turno;
use App\Models\Ventaticket;
use App\Models\VentaticketArticulo;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use App\Models\User; // Import the User model
use Illuminate\Support\Facades\DB;

class CorteController extends Controller
{
    public function habilitarcaja(Request $request)
    {
        /** @var User $user */
        $user = $request->user()->load('configuration');
        return $user->createTurno();
    }
    public function getturnoactual(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $turno = $user->getLatestTurno();
        if ($turno) {
            $turno->updateAcumulados();
        }
        $userTz = $user->configuration?->time_zone;
        $currentMonth = Carbon::now($userTz)->month;
        $currentYear = Carbon::now($userTz)->year;
        $cortesDeCaja = Turno::where('user_id', $user->id)->whereMonth('termino_en', $currentMonth)
            ->whereYear('termino_en', $currentYear)
            ->get(['id', 'termino_en', 'acumulado_ventas']);
        return [$turno, $cortesDeCaja];
    }
    public function getMisMovimientos(Request $request)
    {
        $user = $request->user();
        $turno = Turno::where('user_id', $user->id)
            ->where('inicio_en', '!=', null)
            ->where('termino_en', null)->value('id');
        $movimientos = MovimientoCaja::where('user_id', $user->id,)
            ->where('turno_id', $turno)->get();
        return $movimientos;
    }
    public function getUserMovimientos(Request $request)
    {
        $turno = request('turno');
        $movimientos = MovimientoCaja::where('turno_id', $turno)->get();
        return $movimientos;
    }
    public function getMisCortes(Request $request)
    {
        $user = $request->user();
        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));
        $miscortesPaginados = Turno::where('organization_id', $user->organization_id)
            ->whereBetween('inicio_en', [$dfecha, $fecha])
            ->orderBy('inicio_en', 'desc')
            ->paginate(7);
        $miscortes = $miscortesPaginados->getCollection()->map(function ($item, $key) {

            $suma = $item->dinero_inicial +
                $item->ventas_efectivo +
                $item->acumulado_entradas -
                $item->devoluciones_ventas_efectivo -
                $item->acumulado_salidas +
                $item->abonos_efectivo;
            $item->total_sistema = $suma;
            return $item;
        });
        $ventaEfectivo = $miscortesPaginados->getCollection()->sum('ventas_efectivo');
        $devolucinoesVentaEfectivo = $miscortesPaginados->getCollection()->sum('devoluciones_ventas_efectivo');
        $abonos_efectivo = $miscortesPaginados->getCollection()->sum('abonos_efectivo');
        $acumulado_ganancias = $miscortesPaginados->getCollection()->sum('acumulado_ganancias');
        $compras = $miscortesPaginados->getCollection()->sum('compras');

        $data = [
            'ventas_efectivo' => $ventaEfectivo,
            'devoluciones_ventas_efectivo' => $devolucinoesVentaEfectivo,
            'acumulado_ganancias' => $acumulado_ganancias,
            'abonos_efectivo' => $abonos_efectivo,
            'compras' => $compras,
        ];
        return ['miscortes' => $miscortesPaginados->setCollection($miscortes), 'acumulados' => $data];
    }
    public function getAcumulados(Request $request)
    {
        $user = $request->user();
        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');
        $organizationId = $user->organization_id;

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));
        $dailyTotals = Turno::select(
            DB::raw('DATE(termino_en) as date'),
            DB::raw('SUM(acumulado_ventas) as acumulado_ventas'),
            DB::raw('SUM(abonos_efectivo) as abonos_efectivo'),
            DB::raw('SUM(acumulado_ganancias) as acumulado_ganancias'),
            DB::raw('SUM(compras) as compras'),
            DB::raw('SUM(devoluciones_ventas_efectivo) as devoluciones_ventas_efectivo')
        )
            ->where('organization_id', $organizationId)
            ->whereBetween('termino_en', [$dfecha, $fecha])
            ->groupBy(DB::raw('DATE(termino_en)'))
            ->orderBy('date')
            ->get();
        // Convert the dates to Carbon instances
        $startDate = Carbon::parse($dfecha);
        $endDate = Carbon::parse($fecha);

        // Create a collection with all dates within the range
        $allDates = collect();
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $allDates->push($date->format('Y-m-d'));
        }

        // Combine the collection with the query results
        $dailyTotals = $allDates->map(function ($date) use ($dailyTotals) {
            $record = $dailyTotals->firstWhere('date', $date);
            return [
                'date' => $date,
                'acumulado_ventas' => $record->acumulado_ventas ?? 0,
                'abonos_efectivo' => $record->abonos_efectivo ?? 0,
                'acumulado_ganancias' => $record->acumulado_ganancias ?? 0,
                'compras' => $record->compras ?? 0,
                'devoluciones_ventas_efectivo' => $record->devoluciones_ventas_efectivo ?? 0,
            ];
        });
        $totals = $dailyTotals->reduce(function ($carry, $item) {
            $carry['acumulado_ventas'] += $item['acumulado_ventas'];
            $carry['compras'] += $item['compras'];
            $carry['acumulado_ganancias'] += $item['acumulado_ganancias'];
            $carry['abonos_efectivo'] += $item['abonos_efectivo'];
            $carry['devoluciones_ventas_efectivo'] += $item['devoluciones_ventas_efectivo'];
            return $carry;
        }, [
            'acumulado_ventas' => 0,
            'compras' => 0,
            'acumulado_ganancias' => 0,
            'abonos_efectivo' => 0,
            'devoluciones_ventas_efectivo' => 0,
        ]);

        return ['dailyTotals' => $dailyTotals, 'totals' => $totals];
    }
    public function getCorte(Request $request, $corte)
    {
        $user = $request->user();
        $corte = Turno::with('ventatickets')->find($corte);
        $ventaTickets = $corte->ventatickets;
        $ventaTicketArticulos = VentaticketArticulo::with('product')
            ->selectRaw('product_id,precio_usado, sum(cantidad) as cantidad,
        sum(importe_descuento) as importe_descuento,
        sum(impuesto_traslado) as impuesto_traslado,
        sum(impuesto_retenido) as impuesto_retenido,
        sum(precio_final) as precio_final')
            ->groupBy('product_id')
            ->groupBy('precio_usado')
            ->whereIn('ventaticket_id', $ventaTickets->pluck('id'))
            ->get();
        $taxes = ArticuloTax::with('tax')->join('ventaticket_articulos', 'ventaticket_articulos.id', '=', 'articulo_taxes.ventaticket_articulo_id')
            ->join('products', 'products.id', '=', 'ventaticket_articulos.product_id')
            ->selectRaw('product_id,tax_id, sum(importe) as importe')
            ->groupBy('tax_id')
            ->groupBy('product_id')
            ->whereIn('articulo_taxes.ventaticket_id', $ventaTickets->pluck('id'))
            ->get();
        // Create an associative array to group taxes by product_id
        $groupedTaxes = [];
        foreach ($taxes as $tax) {
            $productId = $tax['product_id'];
            if (!isset($groupedTaxes[$productId])) {
                $groupedTaxes[$productId] = [];
            }
            $groupedTaxes[$productId][] = $tax;
        }

        // Iterate through ventas and associate taxes
        foreach ($ventaTicketArticulos as &$venta) {
            $productId = $venta['product_id'];
            if (isset($groupedTaxes[$productId])) {
                $venta['taxes'] = $groupedTaxes[$productId];
            } else {
                $venta['taxes'] = [];
            }
        }
        return ['corte' => $corte, 'articulos' => $ventaTicketArticulos];
    }
    function show(Request $request, Turno $corte)
    {
        $corte->load('ventatickets.cliente', 'ventatickets.almacen');
        return response()->json([
            'success' => true,
            'corte' => $corte
        ]);
    }

    public function realizarcorte(Request $request)
    {
        $request->validate([
            'cantidad' => 'required|numeric',
            'comments' => 'nullable|string',
            'diferencia' => 'nullable|numeric',
        ]);
        $user = $request->user()->load('configuration');
        $cantidad = request()->input('cantidad');
        $comments = request()->input('comments');
        $diferencia = request()->input('diferencia');

        $ticket = Ventaticket::where('user_id', $user->id)->where('esta_abierto', 1)->where('total', '>', 0)->get();
        if (count($ticket) == 0) {
            $turno = Turno::where('user_id', $user->id)
                ->where('inicio_en', '!=', null)
                ->where('termino_en', null)->first();

            $turno->updateAcumulados();
            $turno->update([
                'efectivo_al_cierre' => $cantidad,
                'diferencia' => $diferencia,
                'termino_en' => getMysqlTimestamp($user->configuration?->time_zone),
            ]);
        } else {
            return 'TicketsAbiertos';
        }
    }
    public function realizarmovimiento(Request $request)
    {
        $request->validate([
            'cantidad' => 'nullable|numeric',
            'comments' => 'nullable|string',
            'concepto' => 'nullable|string',
            'tipo' => 'nullable|string',
        ]);
        $user = $request->user();
        $turno = Turno::where('user_id', $user->id)
            ->where('inicio_en', '!=', null)
            ->where('termino_en', null)->first();

        $tipo = request()->input('tipo');
        $cantidad = request()->input('cantidad') ?? 0;
        $comments = request()->input('comments');
        $concepto = request()->input('concepto');
        $conceptoString = Concepto::find($concepto);
        if ($conceptoString) {
            $concepto = $conceptoString->name;
        }
        $movimientocaja = MovimientoCaja::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'turno_id' => $turno->id,
            'tipo' => $tipo,
            'concepto' => $concepto,
            'comentarios' => $comments,
            'cantidad' => $cantidad,


        ]);
        if ($tipo == 'entrada') {
            $turno->increment('acumulado_entradas', $cantidad);
        } else {

            $turno->increment('acumulado_salidas', $cantidad);
        }
        return $turno;
    }
    public function getconceptos(Request $request)
    {
        $user = $request->user();
        $tipo = request()->input('params.tipo');
        return  Concepto::where('organization_id', $user->organization_id)
            ->where('tipo', $tipo)->get();
    }
}
