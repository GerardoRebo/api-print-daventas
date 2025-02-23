<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class TiendaOrderController extends Controller
{
    //
    function store(Request $request)
    {
        $cartArray = $request->cart;
        $cart = json_decode(json_encode($cartArray));
        logger($cart);
        $org = Organization::findOrFail($cart->tienda_id);
        $org->createCotizacionFromOrder($cart);
        //todo: create log
        return response()->json();
    }
}
