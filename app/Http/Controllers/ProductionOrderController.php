<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class ProductionOrderController extends Controller
{
    function index()
    {
        $user = auth()->user();
        $organizatinoId = $user->organization_id;
        return ProductionOrder::with('ventaticket', 'ventaticket_articulo.product')->where('organization_id', $organizatinoId)->get();
    }
}
