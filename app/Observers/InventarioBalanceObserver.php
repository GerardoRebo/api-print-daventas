<?php

namespace App\Observers;

use App\Models\InventarioBalance;
use Illuminate\Support\Facades\Cache;

class InventarioBalanceObserver
{
    
    public function updated(InventarioBalance $inventarioBalance)
    {
        $product= $inventarioBalance->product_id;
        $almacen= $inventarioBalance->almacen_id;
        $cantidad= $inventarioBalance->cantidad_actual;

        Cache::put('CAP' . $product . 'Almacen' . $almacen, $cantidad, 172800);
    }

}
