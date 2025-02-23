<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TiendaController extends Controller
{
    //
    function ping() {
       return response()->json(['success' => true]);
    }
}
