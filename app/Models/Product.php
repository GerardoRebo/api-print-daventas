<?php

namespace App\Models;

use App\Notifications\AjusteMPrecio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $guarded = [];
    protected $with = ['taxes', 'descuentos'];

    //RELACIÓN UNO A MUCHOS
    public function codes()
    {
        return $this->hasMany('App\Models\Code');
    }
    public function product_components()
    {
        return $this->hasMany('App\Models\ProductComponent');
    }
    public function product_consumibles()
    {
        return $this->hasMany('App\Models\ProductConsumible');
    }
    public function inventario_balances()
    {
        return $this->hasMany('App\Models\InventarioBalance');
    }
    public function precios()
    {
        return $this->hasMany('App\Models\Precio');
    }
    public function descuentos()
    {
        return $this->hasMany('App\Models\Descuento');
    }

    public function inventario_ajustes()
    {
        return $this->hasMany('App\Models\InventarioAjuste');
    }

    public function inventario_recibos_detalles()
    {
        return $this->hasMany('App\Models\InventarioRecibosDetalle');
    }

    public function lista_de_compras()
    {
        return $this->hasMany('App\Models\ListaDeCompras');
    }

    public function ventaticket_articulos()
    {
        return $this->hasMany('App\Models\VentaticketArticulo');
    }

    public function devoluciones_articulos()
    {
        return $this->hasMany('App\Models\DevolucionesArticulo');
    }

    public function inventario_historials()
    {
        return $this->hasMany('App\Models\InventarioHistorial');
    }
    public function articulos_ocs()
    {

        return $this->hasMany('App\Models\ArticulosOc');
    }
    public function histories()
    {
        return $this->hasMany('App\Models\History');
    }

    //relacion uno a muchos inversa



    //Relación muchos a muchos

    public function departamentos()
    {
        return $this->belongsToMany('App\Models\Departamento');
    }

    public function taxes()
    {
        return $this->belongsToMany('App\Models\Tax')->withPivot('id', 'venta', 'compra');;
    }

    public function proveedors()
    {
        return $this->belongsToMany('App\Models\Proveedor');
    }
    function getPrecioVal($almacenId)
    {
        return $this->getPrecioModel($almacenId)?->precio;
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function featuredImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_featured', true);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($product) {
            $product->slug_name = $product->generateUniqueSlug($product->name);
        });

        static::updating(function ($product) {
            $product->slug_name = $product->generateUniqueSlug($product->name, $product->id);
        });
    }
    public function generateUniqueSlug($name, $productId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (Product::where('slug_name', $slug)->when($productId, function ($query) use ($productId) {
            return $query->where('id', '!=', $productId);
        })->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
    function getPrecioModel($almacenId)
    {
        return Precio::firstOrCreate(
            [
                'product_id' => $this->id,
                'almacen_id' => $almacenId
            ]
        );
    }

    public function createDescuento($desde, $hasta, $porcentaje, $porcentaje_type = 1)
    {
        $descuento = $this->descuentos->where('desde', $desde)->first();

        if ($descuento) {
            $descuento = Descuento::find($descuento->id);
            $descuento->update([
                'hasta' => $hasta,
                'descuento' => $porcentaje,
                'porcentaje_type' => $porcentaje_type,
            ]);
            return;
        }

        return $this->descuentos()->create([
            'desde' => $desde,
            'hasta' => $hasta,
            'descuento' => $porcentaje,
            'porcentaje_type' => $porcentaje_type,
        ]);
    }
    public function getInventario($almacen)
    {
        return  InventarioBalance::firstOrCreate([
            'product_id' => $this->id,
            'almacen_id' => $almacen,
        ]);
    }

    public function getDescuentoModel($cantidad): ?Descuento
    {
        foreach ($this->descuentos as $desc) {
            if ($cantidad >= $desc->desde && $cantidad <= $desc->hasta) {
                return $desc;
            }
        }
        return null;
    }
    function getDescuentoCantidad($cantidad, $precio)
    {
        //importe
        $descuentoModel = $this->getDescuentoModel($cantidad);
        if (!$descuentoModel) return 0;
        if ($descuentoModel->porcentaje_type) {
            $descuento = $precio * ($descuentoModel->descuento / 100);
        } else {
            $descuento = $descuentoModel->descuento;
        }
        $descuento * $cantidad;
    }
    public function procesaAjusteCosto(User $user, $pcosto, $descripcion)
    {
        $productA = $this;
        if ($productA->pcosto == $pcosto) return;

        $users = $user->getUsersInMyOrg();
        Notification::send($users, new AjusteMPrecio($user->name, $productA->name, $pcosto, 'Ajuste Manual de costo'));

        if ($productA->es_kit && count($productA->product_components) == 1) {
            $productHijo = $productA->product_components->first()->product_hijo;
            $cantidadComponente = $productA->product_components->first()->cantidad;
            $costoNuevo = $pcosto / $cantidadComponente;
            $costoAnterior = $productA->pcosto / $cantidadComponente;
            $productHijo->update(['pcosto' => $costoNuevo, 'ucosto' => $costoAnterior]);
            $this->createCostoHistorial($user, $descripcion);
        } else {
            $productHijo = $productA;
            $costoNuevo = $pcosto;
            $costoAnterior = $productHijo->pcosto;
            $productHijo->update(['pcosto' => $costoNuevo, 'ucosto' => $costoAnterior]);
            $this->createCostoHistorial($user, $descripcion);
        }

        $componentes = ProductComponent::where('product_hijo_id', $productHijo->id)->get();
        foreach ($componentes as $componente) {
            $producto = Product::find($componente->product_id);
            if (count($producto->product_components) == 1) {
                $producto->createCostoHistorial($user, $descripcion);
                $producto->update(['pcosto' => ($componente->cantidad * $costoNuevo), 'ucosto' => $producto->pcosto]);
            } else {
                $sumaCosto = 0;
                foreach ($producto->product_components as $item) {
                    $sumaCosto += ($item->product_hijo->pcosto * $item->cantidad);
                }
                $producto->update(['pcosto' => ($sumaCosto), 'ucosto' => $producto->pcosto]);
                $producto->createCostoHistorial($user, $descripcion);
            }
        }
    }
    public function createCostoHistorial($user, $descripcion)
    {
        $this->refresh();
        DB::table('costo_historials')->insert([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
            'product_id' => $this->id,
            'costo_anterior' => $this->ucosto ?? 0,
            'costo_despues' => $this->pcosto ?? 0,
            'descripcion' => $descripcion,
            'created_at' => getMysqlTimestamp($user->configuration?->time_zone)
        ]);
    }
    public function incrementInventario($cantidad, $almacenId)
    {

        $product = $this;
        if ($product->consumible == 'generico') {
            return;
        }
        if ($product->es_kit) {
            foreach ($product->product_components as $componente) {
                if ($componente->product_hijo->consumible == 'generico') {
                    continue;
                }
                $inventario = $componente->product_hijo->getInventario($almacenId);
                $inventario->increment('cantidad_actual', ($cantidad * $componente->cantidad));
            }
            return;
        }
        $inventario = $product->getInventario($almacenId);
        $inventario->increment('cantidad_actual', $cantidad);
    }
    public function incrementInventarioConsumibleGenerico($cantidad, $almacenId)
    {
        $product = $this;
        if (!$product->consumible == 'generico') {
            return;
        }
        logger('asdfasdf');
        $inventario = $product->getInventario($almacenId);
        logger($inventario);
        logger($cantidad);
        $inventario->increment('cantidad_actual', $cantidad);
    }
    public function getPrecioSugerido($almacenId)
    {
        $ganancia = $this->getGananciaAmount($almacenId);
        if ($ganancia) {
            $impRetenido = $this->getTaxesRetenidoAmount();
            $impTraslado = $this->getTaxesTrasladoAmount();
            return +$this->pcosto + $ganancia + $impTraslado - $impRetenido;
        }
        return null;
    }
    function getGananciaAmount($almacenId)
    {
        $porcentaje = $this->getPorcentajeGanancia($almacenId);
        if ($porcentaje) {
            return (+$this->pcosto * ($porcentaje / 100));
        }
        return null;
    }
    function getPorcentajeGanancia($almacenId)
    {
        $precio = $this->getPrecioModel($almacenId);
        if ($precio->porcentaje_ganancia) {
            $porcentaje = $precio->porcentaje_ganancia;
        } else {
            $porcentaje = $this->porcentaje_ganancia == "0.00" ? null : $this->porcentaje_ganancia;
        }
        return $porcentaje;
    }
    function getTaxesTrasladoAmount()
    {
        $tasaCuota = $this->getTaxesTraslado()->sum('tasa_cuota');
        return ($this->pcosto * ($tasaCuota / 100));
    }
    function getTaxesRetenidoAmount()
    {
        $tasaCuota = $this->getTaxesRetenido()->sum('tasa_cuota');
        return ($this->pcosto * ($tasaCuota / 100));
    }
    function getTaxesTraslado()
    {
        return $this->getTaxes('traslado');
    }
    function getTaxesRetenido()
    {
        return $this->getTaxes('retenido');
    }
    function getTaxes($type)
    {
        return $this->taxes->where('tipo', $type);
    }
    public function updateInventario($cantidad, $almacenId)
    {
        $product = $this;
        if ($product->es_kit) {
            foreach ($product->product_components as $componente) {
                $inventario = $componente->product_hijo->getInventario($almacenId);
                $inventario->cantidad_actual = $cantidad * $componente->cantidad;
                $inventario->save();
            }
            return;
        }
        $inventario = $product->getInventario($almacenId);
        $inventario->cantidad_actual = $cantidad;
        $inventario->save();
    }
    public function getCantidadActualConsolidada($organization)
    {
        $suma = 0;
        foreach ($organization->almacens as $almacen) {
            if ($this->es_kit) {
                $sumaTemp = $this->getCantidadActualKit($almacen->id);
                if ($sumaTemp !== null) {
                    $suma = $suma + $sumaTemp;
                }
                continue;
            }
            $sumaTemp = InventarioBalance::where('product_id', $this->id)
                ->where('almacen_id', $almacen->id)->value('cantidad_actual');
            if ($sumaTemp !== null) {
                $suma = $suma + $sumaTemp;
            }
        }
        return $suma;
    }
    public function getCantidadActual($almacenId)
    {
        if ($this->es_kit) {
            return $this->getCantidadActualKit($almacenId);
        }
        if ($this->consumible == 'generico') {
            return $this->getCantidadActualConsumibleGenerico($almacenId);
        }
        return  InventarioBalance::where('product_id', $this->id)
            ->where('almacen_id', $almacenId)->value('cantidad_actual');
        return Cache::remember(
            'CAP' . $this->id . 'Almacen' . $almacenId,
            800,
            function () use ($almacenId) {
                return  InventarioBalance::where('product_id', $this->id)
                    ->where('almacen_id', $almacenId)->value('cantidad_actual');
            }
        );
    }
    public function usesConsumable()
    {
        foreach ($this->product_components as $component) {
            if ($component->product_hijo->product_consumibles->count()) {
                return true;
            }
        }
        return false;
    }
    public function getCantidadActualKit($almacen)
    {
        $cociente1 = [];

        foreach ($this->product_components as $component) {
            if ($this->id == $component->product_hijo->id) break;
            $inventario = $component->product_hijo->getCantidadActual($almacen);
            if (isset($inventario)) {
                $cociente = $inventario / $component->cantidad;
                array_push($cociente1, $cociente);
            }
        }
        if (count($cociente1)) {
            $cantidad = min($cociente1);
        } else {
            $cantidad = null;
        }
        return $cantidad;
    }
    public function getCantidadActualConsumibleGenerico($almacen)
    {
        $cociente1 = [];

        foreach ($this->product_consumibles as $productConsumible) {
            if ($this->id == $productConsumible->consumible->id) break;
            $inventario = $productConsumible->consumible->getCantidadActual($almacen);
            logger($inventario);
            if (isset($inventario)) {
                $cociente = $inventario;
                array_push($cociente1, $cociente);
            }
        }
        if (count($cociente1)) {
            $cantidad = array_sum($cociente1);
        } else {
            $cantidad = null;
        }
        return $cantidad;
    }
}
