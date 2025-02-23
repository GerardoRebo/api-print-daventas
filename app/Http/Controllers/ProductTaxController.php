<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTax;
use App\Models\Tax;
use Illuminate\Http\Request;

class ProductTaxController extends Controller
{
    function store(Request $request, Product $product, Tax $tax)
    {
        $request->validate([
            'compra' => 'required',
            'venta' => 'required'
        ]);
        $compra = $request->compra;
        $venta = $request->venta;
        return $product->taxes()->attach($tax->id, ['compra' => $compra, 'venta' => $venta]);
    }
    function delete(Request $request, Product $product, Tax $tax)
    {
        return $product;
    }
    function update(Request $request, ProductTax $productTax)
    {
        $validated=$request->validate([
            'compra' => 'required',
            'venta' => 'required'
        ]);
        $productTax->compra=$validated['compra'];
        $productTax->venta=$validated['venta'];
        return $productTax->save();
    }
    public function agregard($impuestoActualId, $productActualId)
    {
        $product = Product::find($productActualId);
        $product->taxes()->attach([$impuestoActualId]);
        return 'exitoso';
    }
    public function quitarD($impuestoActualId, $productActualId)
    {
        $product = Product::find($productActualId);
        $product->taxes()->detach([$impuestoActualId]);
        return;
    }
}
