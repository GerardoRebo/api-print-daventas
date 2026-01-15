<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Product;
use Illuminate\Http\Request;

class CodigoController extends Controller
{
    public function eliminar()
    {
        $codigo = request()->input('params.codigo');
        Code::destroy($codigo);
        return;
    }
    public function show(Request $request)
    {
        $product = Product::find($request->productActualId);
        return $product->codes;
    }
    public function attach(Request $request)
    {
        $user = auth()->user();
        Code::create([
            'product_id' => $request->productActualId,
            'code' => $request->codigo,
            'organization_id' => $user->active_organization_id,
        ]);
        return;
    }
}
