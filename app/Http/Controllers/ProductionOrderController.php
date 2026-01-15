<?php

namespace App\Http\Controllers;

use App\Models\InventHistorial;
use App\Models\Product;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class ProductionOrderController extends Controller
{
    function index(Request $request)
    {
        $request->validate([
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'statuses' => 'nullable',
            'statuses.*' => 'string|in:pending,design,production,finishing,finished,delivered'
        ]);
        $user = auth()->user();
        $cliente_id = request()->get('cliente_id', null);
        $statuses = request()->get('statuses', []);
        $organizatinoId = $user->active_organization_id;
        $organization = $user->organization;
        $productionOrders = ProductionOrder::with(
            'ventaticket.almacen',
            'ventaticket.cliente',
            'ventaticket.user',
            'ventaticket_articulo.files',
            'ventaticket_articulo.product.product_components.product_hijo.product_consumibles'
        )
            ->where('organization_id', $organizatinoId)
            ->whereIn('status', $statuses)
            ->when($cliente_id, function ($query) use ($cliente_id) {
                $query->whereHas('ventaticket.cliente', function ($q) use ($cliente_id) {
                    $q->where('id', $cliente_id);
                });
            })
            ->orderByDesc('id')
            ->paginate(10);
        return [
            'productionOrders' => $productionOrders,
            'clientes' => $organization->clientes,
        ];
    }
    function update(Request $request, ProductionOrder $productionOrder)
    {
        $productionOrder->status = $request->status;
        $productionOrder->save();
        return $productionOrder->status;
    }
    function storeConsumibleGenerico(Request $request, ProductionOrder $productionOrder)
    {
        $user = auth()->user();
        $consumiblesEnviados = $request->consumibles;
        $articulo = $productionOrder->ventaticket_articulo;
        $product = $articulo->product;
        foreach ($product->product_components as $component) {
            if ($component->product_hijo->consumible == 'generico') {
                $productoEspecificoId = $consumiblesEnviados[$component->product_hijo->id];
                $productEspecifico = Product::find($productoEspecificoId);
                if ($productEspecifico) {
                    if ($product->usa_medidas) {
                        $cantidad = $component->cantidad * $articulo->area_total;
                    } else {
                        $cantidad = $component->cantidad * $articulo->cantidad;
                    }
                    $productEspecifico->incrementInventarioConsumibleGenerico(-$cantidad, $productionOrder->almacen_id);
                    $articulo->createInventarioHistorial('decrement', 'Etapa de produccion', $user);
                }
            }
        }
        $productionOrder->consumable_deducted = true;
        $productionOrder->save();
        return $productionOrder->status;
    }
}
