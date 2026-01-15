<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\MyClasses\PuntoVenta\ProductArticuloCotizacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $with = ['almacen', 'cliente', 'user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public function ventaticket()
    {
        return $this->belongsTo(Ventaticket::class);
    }

    public function almacen()
    {
        return $this->belongsTo('App\Models\Almacen');
    }
    public function articulos()
    {
        return $this->hasMany(CotizacionArticulo::class);
    }

    function addCotizacionArticulos($cart)
    {
        foreach ($cart->articulos as $articulo) {
            $newA = new CotizacionArticulo();
            $newA->cotizacion_id = $this->id;
            $newA->product_id = $articulo->product_id;
            $newA->product_name = $articulo->product_name;
            $newA->cantidad = $articulo->cantidad;
            $newA->precio = $articulo->precio;
            $newA->save();
            $this->articulos()->save($newA);
        }
    }
    public function getAlmacen()
    {
        return $this->almacen_id;
    }
    public function getArticulosExtended()
    {
        $almacen = $this->getAlmacen();
        $ventaticketArticulos = CotizacionArticulo::with('product.product_components.product_hijo')
            ->leftJoin('inventario_balances', function ($join,) use ($almacen) {
                $join->on('cotizacion_articulos.product_id', '=', 'inventario_balances.product_id')
                    ->where('inventario_balances.almacen_id', '=', $almacen);
            })
            ->where('cotizacion_id', $this->id)
            ->leftJoin('products', 'cotizacion_articulos.product_id', '=', 'products.id')
            ->select(
                'products.*',
                'inventario_balances.cantidad_actual',
                'cotizacion_articulos.cantidad',
                'cotizacion_articulos.id',
                'cotizacion_articulos.impuesto_traslado',
                'cotizacion_articulos.impuesto_retenido',
                'cotizacion_articulos.importe_descuento',
                'cotizacion_articulos.descuento',
                'cotizacion_articulos.product_id',
                'cotizacion_articulos.product_name',
                'cotizacion_articulos.precio',
                'cotizacion_articulos.importe'
            )
            ->orderByDesc('cotizacion_articulos.id')
            ->get();

        $ventaticketArticulos = $ventaticketArticulos->map(function ($item, $key) use ($almacen) {
            if ($item->es_kit) {
                $item->cantidad_actual = $item->getCantidadActual($almacen);
            }
            return $item;
        });
        return $ventaticketArticulos;
    }
    function setPrecios()
    {
        foreach ($this->articulos as $articulo) {
            $articulo->precio = $articulo->product->getPrecioVal($this->almacen_id);
            // $articulo->setPrecioBase();
            $articulo->setDescuento();
            $articulo->setImporte();
            $articulo->setTaxes();
            $articulo->save();
        }
    }
    function registerArticulo(ProductArticuloCotizacion $product)
    {
        $yaExisteArticulo = $this->yaExisteArticuloEnTicket($product->id);

        $por_descuento = null;

        if ($yaExisteArticulo) {
            $articulo = $this->getArticuloByProductId($product->id);

            $articulo->precio = $product->precio;
            //calculate base impositiva
            $articulo->cantidad += $product->cantidad;
            // weird take a look
            // $product->cantidad = $articulo->cantidad;
        } else {
            $articulo = $this->createArticulo($product);
        }

        //calculate base impositiva
        $articulo->setPrecioBase();
        $articulo->setDescuento();
        $articulo->setImporte();
        //add taxes
        $articulo->setTaxes();

        $articulo->save();
        // $articulo->incrementInventario(-$product->cantidad);
    }
    public function yaExisteArticuloEnTicket($product)
    {
        return $this->articulos->pluck('product_id')->contains($product);
    }
    public function getArticuloByProductId($product): CotizacionArticulo
    {
        return $this->articulos->where('product_id', $product)->first();
    }
    public function createArticulo($product): CotizacionArticulo
    {
        $articulo = new CotizacionArticulo();
        $articulo->cotizacion_id = $this->id;
        $articulo->product_id = $product->id;
        $articulo->product_name = $product->product->name;
        $articulo->cantidad = $product->cantidad;
        $articulo->importe_descuento = 0;
        $articulo->precio = $product->precio;
        $articulo->importe = $product->precio * $product->cantidad;
        $articulo->save();
        return $articulo;
    }
    function updateArticulo($product, $articulo, $restaCantidad)
    {
        if ($restaCantidad < 0) {
            $enuffInventario = $product->enuffInventario($this->getAlmacen(), -$restaCantidad);
            if (!$enuffInventario) {
                throw new OperationalException("No hay suficiente inventario", 422);
            }
        }
        $articulo->precio = $product->precio;
        $articulo->addCantidad(-$restaCantidad);
        //calculate base impositiva
        $articulo->setPrecioBase();
        $articulo->setDescuento();
        $articulo->setImporte();
        //add taxes
        $articulo->setTaxes();
        $articulo->save();
        // $articulo->incrementInventario($restaCantidad);
    }
    public function getArticuloById($articulo): CotizacionArticulo
    {
        return $this->articulos->where('id', $articulo)->first();
    }
    function checkAllExistingProducts()
    {
        foreach ($this->articulos as $articulo) {
            if (!$articulo->product_id) {
                throw new OperationalException("En el ticket hay productos actualmente eliminados, limpia primero", 1);
            }
        }
    }
    public function getTotal()
    {
        $subTotal = $this->articulos->sum('importe');
        $descuentos = $this->articulos->sum('importe_descuento');
        $impuestoTraslado = $this->articulos->sum('impuesto_traslado');
        $impuestoRetenido = $this->articulos->sum('impuesto_retenido');
        return $this->total = $subTotal - $descuentos + $impuestoTraslado - $impuestoRetenido;
    }
    public function getSubTotal()
    {
        return $this->articulos->sum('importe');
    }
    public function getImpuestos($type = 'traslado')
    {
        if ($type == 'traslado') {
            return $this->articulos->sum('impuesto_traslado');
        }
        return $this->articulos->sum('impuesto_retenido');
    }
    public function getDescuento()
    {
        if ($this->descuento) {
            return $this->descuento;
        }
        return $this->descuento = $this->articulos->sum('importe_descuento');
    }
}
