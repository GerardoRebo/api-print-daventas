<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCaja;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoCaja::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->where('es_gasto', true)
            ->with('user');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('concepto', 'like', "%{$request->search}%")
                    ->orWhere('comentarios', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('itemsPerPage')) {
            $perPage = (int) $request->itemsPerPage;
        } else {
            $perPage = 30;
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                $request->fecha_inicio,
                $request->fecha_fin
            ]);
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate($perPage)
        ]);
    }
}
