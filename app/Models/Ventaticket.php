<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\MyClasses\Factura\ComprobanteImpuestos;
use App\MyClasses\Factura\FacturaService;
use App\MyClasses\PuntoVenta\ProductArticuloVenta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Ventaticket extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['almacen', 'cliente', 'user'];


    //uno a uno
    public function devolucione()
    {
        return $this->hasOne('App\Models\Devolucione');
    }
    public function deuda()
    {
        return $this->hasOne('App\Models\Deuda');
    }
    public function cotizacion()
    {
        return $this->hasOne(Cotizacion::class);
    }
    public function pre_factura()
    {
        return $this->hasOne('App\Models\PreFactura');
    }
    //RELACIÓN UNO A MUCHOS
    public function ventaticket_articulos()
    {
        return $this->hasMany('App\Models\VentaticketArticulo');
    }
    public function retention_taxes()
    {
        return $this->hasMany(VentaticketRetentionTaxes::class);
    }

    public function devoluciones()
    {
        return $this->hasMany('App\Models\Devolucione');
    }

    public function devoluciones_articulos()
    {
        return $this->hasMany('App\Models\DevolucionesArticulo');
    }
    public function latestPreFactura()
    {
        return $this->hasOne(PreFactura::class)->latestOfMany();
    }

    public function comisiones_tarjetas()
    {
        return $this->hasMany('App\Models\ComisionesTarjetas');
    }
    public function pagos_mixtos()
    {
        return $this->hasMany('App\Models\PagosMixto');
    }
    public function abono_ventas()
    {
        return $this->hasMany('App\Models\AbonoVenta');
    }
    public function abono_tickets()
    {
        return $this->hasMany('App\Models\AbonoTicket');
    }
    public function inventario_historials()
    {
        return $this->hasMany('App\Models\InventarioHistorial');
    }

    //relacion uno a muchos inversa

    public function user()
    {

        return $this->belongsTo('App\Models\User');
    }
    public function organization()
    {

        return $this->belongsTo('App\Models\Organization');
    }

    public function turno()
    {

        return $this->belongsTo('App\Models\Turno');
    }

    public function corte_operacion()
    {

        return $this->belongsTo('App\Models\CorteOperacion');
    }

    public function cliente()
    {

        return $this->belongsTo('App\Models\Cliente');
    }
    public function almacen()
    {

        return $this->belongsTo('App\Models\Almacen');
    }
    //relacion uno a muchos polimorfica
    public function histories()
    {

        return $this->morphMany('App\Models\History', 'historiable');
    }
    function registerArticulo(ProductArticuloVenta $product)
    {
        $yaExisteArticulo = $this->yaExisteArticuloEnTicket($product->id);

        if (!$product->enuffInventario($this->getAlmacen())) {
            throw new OperationalException("No hay suficiente inventario", 422);
        }
        $por_descuento = null;

        if ($yaExisteArticulo) {
            $articulo = $this->getArticuloByProductId($product->id);

            $articulo->precio_usado = $product->precio;
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
        $articulo->setGanancia($product);
        //add taxes
        $articulo->setTaxes();

        $articulo->save();
        //todo:quitar
        // $articulo->incrementInventario(-$product->cantidad);
    }
    public function yaExisteArticuloEnTicket($product)
    {
        return $this->ventaticket_articulos->pluck('product_id')->contains($product);
    }
    public function getArticuloByProductId($product): VentaticketArticulo
    {
        return $this->ventaticket_articulos->where('product_id', $product)->first();
    }
    public function createArticulo($product): VentaticketArticulo
    {
        $ganancia = ($product->precio - $product->product->pcosto) * $product->cantidad;
        $articulo = new VentaticketArticulo();
        $articulo->ventaticket_id = $this->id;
        $articulo->product_id = $product->id;
        $articulo->product_name = $product->product->name;
        $articulo->departamento_id = null;
        $articulo->cantidad = $product->cantidad;
        $articulo->ganancia = $ganancia;
        $articulo->pagado_en = null;
        $articulo->importe_descuento = 0;
        $articulo->precio_usado = $product->precio;
        $articulo->cantidad_devuelta = 0;
        $articulo->fue_devuelto = 0;
        $articulo->porcentaje_pagado = null;
        $articulo->precio_final = $product->precio * $product->cantidad;
        $articulo->agregado_en = null;
        $articulo->save();
        return $articulo;
    }
    public function getArticulosExtended()
    {
        $almacen = $this->getAlmacen();
        $ventaticketArticulos = VentaticketArticulo::with('product.product_components.product_hijo', 'product.images')
            ->leftJoin('inventario_balances', function ($join,) use ($almacen) {
                $join->on('ventaticket_articulos.product_id', '=', 'inventario_balances.product_id')
                    ->where('inventario_balances.almacen_id', '=', $almacen);
            })
            ->where('ventaticket_id', $this->id)
            ->leftJoin('products', 'ventaticket_articulos.product_id', '=', 'products.id')
            ->select(
                'products.*',
                'inventario_balances.cantidad_actual',
                'ventaticket_articulos.cantidad',
                'ventaticket_articulos.id',
                'ventaticket_articulos.cantidad_devuelta',
                'ventaticket_articulos.impuesto_traslado',
                'ventaticket_articulos.impuesto_retenido',
                'ventaticket_articulos.importe_descuento',
                'ventaticket_articulos.descuento',
                'ventaticket_articulos.fue_devuelto',
                'ventaticket_articulos.product_id',
                'ventaticket_articulos.product_name',
                'ventaticket_articulos.precio_usado',
                'ventaticket_articulos.precio_final'
            )
            ->orderByDesc('ventaticket_articulos.id')
            ->get();

        $ventaticketArticulos = $ventaticketArticulos->map(function ($item, $key) use ($almacen) {
            if ($item->es_kit) {
                $item->cantidad_actual = $item->getCantidadActual($almacen);
            }
            return $item;
        });
        return $ventaticketArticulos;
    }
    public function getAlmacen()
    {
        return $this->almacen_id;
    }
    function checkArticulosEnoughInventory()
    {
        $notEnoughInventory = [];
        foreach ($this->ventaticket_articulos as $articulo) {
            if (!$articulo->enuffInventario()) {
                $notEnoughInventory[] = $articulo->product->name;
            }
        }
        return $notEnoughInventory;
    }
    function decrementArticulos()
    {
        foreach ($this->ventaticket_articulos as $articulo) {
            if ($articulo->necesitaProduction()) {
                $articulo->incrementInventario(-$articulo->cantidad);
            }
        }
    }
    private function facturaValidations($clavePrivadaLocal)
    {
        if ($this->facturado_en) {
            throw new OperationalException("El ticket ya ha sido facturado", 1);
        }
        if (!$this->cliente) {
            throw new OperationalException("El ticket que quieres facturar no tiene especificado un cliente", 1);
        }
        foreach ($this->ventaticket_articulos as $articulo) {
            if (!$articulo->product->taxes->count()) {
                throw new OperationalException("Impuestos no configurados en el producto" . $articulo->product->name, 1);
            }
            if (!$articulo->product->c_ClaveUnidad) {
                throw new OperationalException("Clave unidad no configurada en el producto" . $articulo->product->name, 1);
            }
            if (!$articulo->product->c_claveProdServ) {
                throw new OperationalException("Clave Producto Servicio no configurada en el producto" . $articulo->product->name, 1);
            }
        };
        $facturaHelper = new FacturaService;
        $facturaData = $facturaHelper->getData($this);
        foreach ($facturaData as $key => $value) {
            if (!$value) {
                throw new OperationalException("No has configurado el siguiente dato necesario para facturar: " . $key, 1);
            }
        }
        if ($facturaData['clave_privada_local'] != $clavePrivadaLocal) {
            throw new OperationalException("La clave privada local no es correcta",  1);
        }
    }

    function createPreFactura(): PreFactura
    {
        $preFactura = new PreFactura();
        $preFactura->ventaticket_id = $this->id;
        $preFactura->organization_id = $this->organization_id;
        $preFactura->save();
        foreach ($this->ventaticket_articulos as $articulo) {
            $preArticulo = new PreFacturaArticulo();
            $preArticulo->pre_factura_id = $preFactura->id;
            $preArticulo->product_id =  $articulo->product_id;
            $preArticulo->cantidad = $articulo->cantidad;
            $preArticulo->precio = $articulo->precio_usado;
            $preArticulo->setImporte();
            if ($articulo->taxes->count()) {
                $preArticulo->descuento = $articulo->importe_descuento;
            } else {
                $preArticulo->setBaseImpositiva($articulo->product->taxes);
            }
            $preArticulo->save();
            $preArticulo->load('product');
            if ($articulo->taxes->count()) {
                $preArticulo->setTaxes($articulo->taxes);
            } else {
                $preArticulo->setNewTaxesTraslado();
                $preArticulo->setNewTaxesRetenido();
            }
            $preArticulo->save();
        }
        $preFactura->load('articulos.product', 'articulos.taxes');

        $preFactura->subtotal = $preFactura->articulos->sum('importe');
        $preFactura->descuento = $preFactura->articulos->sum('descuento');
        $preFactura->impuesto_traslado = $preFactura->articulos->sum('impuesto_traslado');
        $preFactura->impuesto_retenido = $preFactura->articulos->sum('impuesto_retenido');
        $preFactura->setTotal();
        $preFactura->save();
        return $preFactura;
    }

    function facturarVenta($formaPago, $metodoPago, $usoCfdi, $serie, $clavePrivadaLocal)
    {
        $user = $this->user;
        $this->facturaValidations($clavePrivadaLocal);

        /** @var PreFactura $preFactura */
        $preFactura = $this->createPreFactura();
        $oComprobante = $preFactura->initializeComprobante($serie, $formaPago, $metodoPago);

        //emisor
        $oEmisor = $preFactura->createEmisor();
        //receptor
        $oReceptor = $preFactura->createReceptor($usoCfdi);

        //conceptos
        $lstConceptos = $preFactura->createConceptos();

        //NODO IMPUESTO

        $oIMPUESTOS = new ComprobanteImpuestos();

        $facturaImpuestos = $preFactura->getImpuestos();
        if ($facturaImpuestos['retenidos']) {
            $oIMPUESTOS->TotalImpuestosRetenidos = round($preFactura->getSumatoriaImpuestos('retenido'), 2);
            $oIMPUESTOS->Retenciones = $facturaImpuestos['retenidos'];
        }
        if ($facturaImpuestos['trasladados']) {
            $oIMPUESTOS->TotalImpuestosTrasladados = round($preFactura->getSumatoriaImpuestos('traslado'), 2);
            $oIMPUESTOS->Traslados = $facturaImpuestos['trasladados'];
        }

        //agregamos impuesto a comprobante
        $oComprobante->Impuestos = $oIMPUESTOS;
        $oComprobante->Emisor = $oEmisor;
        $oComprobante->Receptor = $oReceptor;
        $oComprobante->Conceptos = $lstConceptos;

        $MiJson = "";
        $MiJson = json_encode($oComprobante);
        $jsonPath = 'factura_json/' . $this->id;
        Storage::disk('local')->put($jsonPath, $MiJson);

        $preFactura->callServie($jsonPath);

        $pdfFacturaPath = "pdf_factura/$user->organization_id/" . $preFactura->ventaticket_id;
        /* create pdf */
        $fService = new FacturaService;
        $fService->storePdf('facturaTmp/XmlDesdePhpTimbrado.xml', $user, $pdfFacturaPath);
        $this->pdf_factura_path = $pdfFacturaPath . ".pdf";
        $this->save();
    }
    public function generateTicketText()
    {
        $ventaticket = $this;
        $link = "daventas.com\n";
        $ticketText = "\n";
        $ticketText .= "" . ($ventaticket->organization->name ?? '') . "\n";
        $ticketText .= "Cajero: " . ($ventaticket->user->name ?? '') . "\n";
        $ticketText .= "Sucursal: " . ($ventaticket->almacen->name ?? '') . "\n";
        if ($ventaticket->almacen->direccion) {
            $ticketText .= "Dirección: " . $ventaticket->almacen->direccion . "\n";
        }
        if ($ventaticket->almacen->rfc) {
            $ticketText .= "RFC: " . $ventaticket->almacen->rfc . "\n";
        }
        if ($ventaticket->almacen->telefono) {
            $ticketText .= "Teléfono: " . $ventaticket->almacen->telefono . "\n";
        }
        $ticketText .= "Ticket #: " . ($ventaticket->consecutivo ?? '') . "\n";

        $ticketText .= "Fecha: " . ($ventaticket->pagado_en ? \Carbon\Carbon::parse($ventaticket->pagado_en)->format('d-m-Y h:i:s a') : \Carbon\Carbon::now()->format('d-m-Y h:i:s a')) . "\n";

        if ($ventaticket->esta_cancelado) {
            $ticketText .= "Ticket Cancelado\n";
        }
        $ticketText .= "===================\n";

        foreach ($ventaticket->ventaticket_articulos as $item) {
            $ticketText .= "- " . ($item->product->name ?? $item->product_name) . "\n";
            $ticketText .= $item->cantidad . " x $" . $item->precio_usado . " = $" . $item->precio_final . "\n";
            if ($item->fue_devuelto) {
                $ticketText .= "Devolución -" . $item->cantidad_devuelta . "\n";
            }
        }
        $ticketText .= "===================\n";
        $ticketText .= "Subtotal: $" . $ventaticket->subtotal . "\n";
        if ($ventaticket->descuento) {
            $ticketText .= "Descuento: $" . $ventaticket->descuento . "\n";
        }
        $ticketText .= "Total: $" . ($ventaticket->total - $ventaticket->total_devuelto) . "\n";
        $ticketText .= "Pagó con: $" . $ventaticket->pago_con . "\n";
        $ticketText .= "Su cambio: $" . ($ventaticket->pago_con - ($ventaticket->total - $ventaticket->total_devuelto)) . "\n";
        $ticketText .= "Gracias por tu compra  😃\n";
        $ticketText .= "\n";
        $ticketText .= "Gracias por usar nuestro punto de venta.\n";
        $ticketText .= "Visítanos en $link";

        return $ticketText;
    }
}
