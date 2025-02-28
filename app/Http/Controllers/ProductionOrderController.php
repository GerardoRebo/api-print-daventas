<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class ProductionOrderController extends Controller
{
    function index()
    {
        $user = auth()->user();
        $organizatinoId = $user->organization_id;
        return ProductionOrder::with('ventaticket', 'ventaticket_articulo.product.product_components.product_hijo.product_consumibles')->where('organization_id', $organizatinoId)->get();
    }
    function update(Request $request, ProductionOrder $productionOrder)
    {
        $productionOrder->status = $request->status;
        $productionOrder->save();
        return $productionOrder->status;
    }
    function storeConsumibleGenerico(Request $request, ProductionOrder $productionOrder)
    {
        $consumiblesEnviados = $request->consumibles;
        $articulo = $productionOrder->ventaticket_articulo;
        $product = $articulo->product;
        foreach ($product->product_components as $component) {
            if ($component->product_hijo->es_consumible_generico) {
                $productoEspecificoId = $consumiblesEnviados[$component->product_hijo->id];
                $productEspecifico = Product::find($productoEspecificoId);
                if ($productEspecifico) {
                    $cantidad = $component->cantidad * $articulo->cantidad;
                    $productEspecifico->incrementInventario(-$cantidad, $productionOrder->almacen_id);
                }
            }
        }
        return $productionOrder->status;
    }
}
