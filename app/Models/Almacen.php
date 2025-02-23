<?php

namespace App\Models;

use App\Notifications\AjusteMInventario;
use App\Notifications\AjusteMPrecio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class Almacen extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];

    //RELACIÓN UNO A MUCHOS

    public function inventariobalances()
    {
        return $this->hasMany('App\Models\InventarioBalance');
    }

    public function ordencompras()
    {
        return $this->hasMany('App\Models\OrdenCompra');
    }

    public function precios()
    {
        return $this->hasMany('App\Models\Precio');
    }

    public function inventario_ajustes()
    {
        return $this->hasMany('App\Models\InventarioAjuste');
    }

    public function inventario_recibos()
    {
        return $this->hasMany('App\Models\InventarioRecibo');
    }

    public function inventario_historials()
    {
        return $this->hasMany('App\Models\InventarioHistorial');
    }
    public function ventatickets()
    {
        return $this->hasMany('App\Models\Ventaticket');
    }
    public function histories()
    {
        return $this->hasMany('App\Models\History');
    }

    //RELACIÓN UNO A MUCHOS inversa

    public function organizacion()
    {
        return $this->belongsTo('App\Models\Organization');
    }

    //relación muchos a muchos

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    function processCambioCantidad($user, Product $product, $cantidadNueva)
    {
        $cantidadActual = $product->getCantidadActual($this->id);
        $deltaCantidad =  $cantidadActual - $cantidadNueva;

        if ($deltaCantidad != 0) {
            $users = $user->getUsersInMyOrg();
            Notification::send($users, new AjusteMInventario(
                $user->name,
                $product->name,
                $deltaCantidad,
                'Ajuste Manual de inventario',
                $this
            ));
            // $product->incrementInventario(-$deltaCantidad, $this->id);
            $product->updateInventario($cantidadNueva, $this->id);

            DB::table('invent_historials')->insert([
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'product_id' => $product->id,
                'almacen_id' => $this->id,
                'cantidad' => $deltaCantidad,
                'cantidad_anterior' => $cantidadActual ?? 0,
                'cantidad_despues' => $cantidadNueva ?? 0,
                'descripcion' => 'AjusteManualInventario',
                'created_at' => getMysqlTimestamp($user->configuration?->time_zone)
            ]);
        }
    }
    function processCambioPrecio($user, Product $product, $precioNuevo, $notify = false)
    {
        $precio = $product->getPrecioModel($this->id);
        $precioAnterior = $precio->precio;
        if ($precioAnterior == $precioNuevo) return;

        $precio->precio = $precioNuevo;
        $precio->save();

        DB::table('precio_historials')->insert(
            [
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'product_id' => $product->id,
                'almacen_id' => $this->id,
                'precio_anterior' => $precioAnterior ?? 0,
                'precio_despues' => $precioNuevo ?? 0,
                'descripcion' => 'AjusteManualPrecioGeneral',
                'created_at' => getMysqlTimestamp($user->configuration?->time_zone)
            ]
        );
        if (!$notify) return;
        $users = $user->getUsersInMyOrg($user->organization_id);
        Notification::send($users, new AjusteMPrecio(
            $user->name,
            $product->name,
            $precioNuevo,
            'Ajuste Manual Normal de precio'
        ));
    }
}
