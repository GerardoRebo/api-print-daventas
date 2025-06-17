<?php

namespace App\Models;

use App\MyClasses\PuntoVenta\ProductArticuloVenta;
use App\MyClasses\PuntoVenta\PuntoVentaCliente;
use App\MyClasses\PuntoVenta\TicketVenta;
use App\Notifications\VentaRealizada;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class Turno extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['user'];

    //RELACIÓN UNO A MUCHOS
    public function corteventapordeptos()
    {
        return $this->hasMany('App\Models\CorteVentaPorDepto');
    }

    public function ventatickets()
    {
        return $this->hasMany('App\Models\Ventaticket');
    }

    public function devoluciones()
    {
        return $this->hasMany('App\Models\Devolucione');
    }

    //relacion uno a muchos inversa

    public function user()
    {

        return $this->belongsTo('App\Models\User');
    }

    public function abonos()
    {
        return $this->hasMany('App\Models\Abono');
    }
    function guardarVenta(TicketVenta $ticketVenta, $forma_pago, $credito)
    {
        if ($credito) {
            $forma_pago['efectivo'] = 0;
        }
        $ticketVenta->checkAllExistingProducts();
        $ticketVenta->setPagadoEn(getMysqlTimestamp($this->user->configuration?->time_zone));
        $ticketVenta->setEstaAbierto(0);
        $ticketVenta->setTurno($this->id);
        $ticketVenta->setPagoCon($forma_pago);
        $ticketVenta->setSubTotal($ticketVenta->getSubTotal());
        $ticketVenta->setTotal($ticketVenta->getTotal());

        $ticketVenta->setDescuento($ticketVenta->getDescuento());
        $ticketVenta->setGanancia($ticketVenta->getGanancia());
        $ticketVenta->setImpuestosTraslado($ticketVenta->getImpuestos('traslado'));
        $ticketVenta->setImpuestosRetenido($ticketVenta->getImpuestos('retenido'));
        $ticketVenta->save($ticketVenta->id);

        $this->increment('acumulado_ventas', $ticketVenta->getTotal());
        $this->increment('acumulado_ganancias', $ticketVenta->getGanancia());
        $this->increment('numero_ventas');

        $ticketVenta->notifyPreciosAjustados($this->user);
        $ticketVenta->createInventarioHistorial("decrement", "Venta");
        $ticketVenta->ticket->decrementArticulos();

        if ($credito) {
            $users = $this->user->getUsersInMyOrg();
            Notification::send($users, new VentaRealizada($this->user->name, $ticketVenta->getConsecutivo(),  'Venta a Credito'));
            $ticketVenta->setFormaPago("C");
            $ticketVenta->ticket->total_credito = $ticketVenta->getTotal();
            $ticketVenta->save();
            $cliente = new PuntoVentaCliente($ticketVenta->getCliente());
            $cliente->incrementSaldo($ticketVenta->getTotal());
            $cliente->createDeuda($this->user->organization_id, $ticketVenta);
            $this->incrementVentaCredito($ticketVenta->getTotal());
        } else {
            $this->incrementVentaEfectivo($ticketVenta->getTotal());
            $this->incrementEfectivoAlCierre($ticketVenta->getTotal());
        }
        $ticketVenta->sendToProduction();
        $ticketVenta->notifyTienda();
    }
    function updateAcumulados()
    {
        $tickets = $this->ventatickets()->where('esta_cancelado', 0)->get();
        $this->update([
            'ventas_efectivo' => $tickets->sum('fp_efectivo'),
            'ventas_cheque' => $tickets->sum('fp_cheque'),
            'ventas_tarjeta_debito' => $tickets->sum('fp_tarjeta_debito'),
            'ventas_tarjeta_credito' => $tickets->sum('fp_tarjeta_credito'),
            'ventas_transferencia' => $tickets->sum('fp_transferencia'),
            'ventas_vales_de_despensa' => $tickets->sum('fp_vales_de_despensa'),
        ]);
    }
    function finalizarCotizacion(Cotizacion $cotizacion)
    {
        $cotizacion->enviada_en = now();
        $cotizacion->esta_abierto = false;
        $cotizacion->turno_id = $this->id;
        $cotizacion->user_id = $this->user->id;
        $cotizacion->subtotal = $cotizacion->getSubTotal();
        $cotizacion->total = $cotizacion->getTotal();

        $cotizacion->descuento = $cotizacion->getDescuento();
        $cotizacion->impuesto_traslado = $cotizacion->getImpuestos('traslado');
        $cotizacion->impuesto_retenido = $cotizacion->getImpuestos('retenido');
        $cotizacion->save();
        return $this->createVentaFromCotizacion($cotizacion);
    }
    function createVentaFromCotizacion($cotizacion)
    {
        $venta = new Ventaticket;

        $venta->organization_id = $cotizacion->organization_id;
        $venta->user_id = $cotizacion->user_id;
        $venta->esta_abierto = true;
        $venta->pendiente = true;
        $venta->turno_id = $this->id;
        $venta->cliente_id = $cotizacion->cliente_id;
        $venta->almacen_id = $cotizacion->almacen_id;
        $venta->consecutivo = $cotizacion->consecutivo;
        $venta->nombre = $cotizacion->nombre;
        $venta->save();
        $cotizacion->ventaticket_id = $venta->id;
        $cotizacion->save();
        foreach ($cotizacion->articulos as $co_articulo) {
            $product = new ProductArticuloVenta($co_articulo->product_id, $co_articulo->precio, $co_articulo->cantidad, $co_articulo->ancho, $co_articulo->alto);
            $venta->registerArticulo($product);
        }
        return $venta;
    }
    function createDevolucion(Ventaticket $venta): Devolucione
    {
        return Devolucione::create([
            'tipo_devolucion' => "P",
            'organization_id' => $this->user->organization_id,
            'user_id' => $this->user->id,
            'turno_id' => $this->id,
            'pagado_en' => getMysqlTimestamp($this->user->configuration?->time_zone),
            'ventaticket_id' => $venta->id,
            'devuelto_en' => getMysqlTimestamp($this->user->configuration?->time_zone),
            'total_devuelto' => 0.0,

        ]);
    }
    function incrementVentaCredito($amount)
    {
        $this->increment('ventas_credito', $amount);
    }
    function incrementAbonoEfectivo($amount)
    {
        $this->increment('abonos_efectivo', $amount);
    }
    function incrementVentaEfectivo($amount)
    {
        $this->increment('ventas_efectivo', $amount);
    }
    function incrementEfectivoAlCierre($amount)
    {
        $this->increment('efectivo_al_cierre', $amount);
    }
}
