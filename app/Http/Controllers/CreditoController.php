<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Deuda;
use App\MyClasses\Creditos\RealizarAbono;
use App\Models\User; // Import the User model
use Illuminate\Http\Request;

class CreditoController extends Controller
{
    public function getcreditos(Request $request)
    {
        $user = $request->user();
        $todos = $request->todos;
        $todos = filter_var($todos, FILTER_VALIDATE_BOOLEAN);
        $name = $request->keyword;

        $creditos = Cliente::when(!$todos, function ($query, $todos) {
            $query->where('saldo_actual', '<>',  0);
        })->when($name, function ($query, $name) {
            $query->where('name', 'like', '%' . $name . '%')
                ->orWhere('email', 'like', '%' . $name . '%');
        })
            ->where('organization_id', $user->organization_id)->get();
        return $creditos;
    }
    public function getdeudas(Request $request, $credito)
    {
        $user = $request->user();
        $show_settled_loan = $request->input('show_settled_loan', 0);
        $deudas = Deuda::with('ventaticket')->where('organization_id', $user->organization_id)
            ->where('cliente_id', $credito)
            ->when(!$show_settled_loan, function ($query) {
                $query->where('saldo', '<>', 0);
            })
            // ->orderByDesc('liquidado')
            ->orderByDesc('id')
            ->paginate(10);
        return $deudas;
    }
    // public function getalldeudas(Request $request)
    // {
    //     $user = $request->user();
    //     $credito = request()->input('credito');
    //     $deudas = Deuda::with('ventaticket:id,consecutivo')->where('organization_id', $user->organization_id)
    //         ->where('cliente_id', $credito)
    //         ->orderByDesc('id')
    //         ->paginate(5);
    //     return $deudas;
    // }
    public function getabonos(Request $request)
    {
        // $suma = Abono::where('organization_id', 1)->whereDate('created_at', now()->toDateString())->sum('abono');
        $user = $request->user();
        $deuda = request()->input('deuda');
        $abonos = Abono::where('organization_id', $user->organization_id)
            ->where('deuda_id', $deuda)
            ->latest()
            ->get();
        return $abonos;
    }
    public function getClienteInfo()
    {
        $cliente = request()->input('credito');
        return Cliente::findOrFail($cliente);

        // return $cliente = Deuda::with('cliente')->find($deuda);
    }
    public function getsaldo()
    {
        $deuda = request()->input('deuda');
        $abonos = Deuda::find($deuda);
        return $abonos;
    }
    public function realizarabono(Request $request, Deuda $deuda)
    {
        $validated = $request->validate([
            'serie' => 'nullable',
            'cantidad' => 'required|numeric',
            'comments' => 'nullable|string',
            'facturar' => 'required|boolean',
            'forma_pago' => 'required_if:facturar,true',
        ]);

        /** @var User $user */
        $user = $request->user()->load('configuration');
        $turno = $user->getLatestTurno();

        if (!$turno) {
            throw new OperationalException("No has habilitado turno", 1);
        }

        $serie = isset($validated['serie']) ? $validated['serie'] : null;
        $cantidad = $validated['cantidad'];
        $comments = $validated['comments'];
        $formaPago = $validated['forma_pago'];
        $facturar = $validated['facturar'];

        $rA = new RealizarAbono;
        $turno->incrementAbonoEfectivo($cantidad);
        $abono = $rA->realizarabono($deuda->id, $user, $cantidad, $comments, $turno);
        if (!$facturar) return;
        if (!$deuda->ventaticket->facturado_en) {
            throw new OperationalException("No se puede facturar un abono a una venta no facturada", 1);
        }
        //check folios fiscales
        $saldo = $user->organization->getOverallTimbresCount();
        $saldoScalar = $saldo;
        if (!$saldoScalar) {
            throw new OperationalException("No cuentas con suficientes timbres fiscales, , contacta con la administración para solicitar timbres fiscales", 1);
        }
        $abono->facturar($formaPago, $user, $cantidad, $serie);
    }
    public function facturarabono(Request $request, Abono $abono)
    {
        $validated = $request->validate([
            'cantidad' => 'required|numeric',
            'forma_pago' => 'required_if:facturar,true',
        ]);

        /** @var User $user */
        $user = $request->user()->load('configuration');
        $turno = $user->getLatestTurno();

        if (!$turno) {
            throw new OperationalException("No has habilitado turno", 1);
        }

        $cantidad = $validated['cantidad'];
        $formaPago = $validated['forma_pago'];

        $abono->facturar($formaPago, $user, $cantidad);
    }
}
