<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\Jobs\AfterGuardarVenta;
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
    /* 
    dinero_inicial, 
    acumulado_ventas,//total credito y contado 
    acumulado_entradas,
    acumulado_salidas, 
    acumulado_ganancias, 
    compras, 
    ventas_efectivo,//contado 
    ventas_credito, 
    efectivo_al_cierre,//calcular despues
    diferencia, 
    abonos_efectivo, 
    devoluciones_ventas,
    devoluciones_ventas_efectivo, 
    devoluciones_ventas_credito, 
    devoluciones_abonos_efectivo, 
    abonos_tarjeta, 
    comisiones_tarjeta, 
    */

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
    function guardarVenta(Ventaticket $ventaticket, $forma_pago, $credito)
    {
        if ($credito) {
            $forma_pago['efectivo'] = 0;
        }
        $ventaticket->checkAllExistingProducts();
        //save base data
        $ventaticket->pagado_en = getMysqlTimestamp($this->user->configuration?->time_zone);
        $ventaticket->esta_abierto = 0;
        $ventaticket->turno_id = $this->id;
        $ventaticket->setPagoCon($forma_pago);
        $ventaticket->subtotal = $ventaticket->getSubTotal();
        $ventaticket->total = $ventaticket->getTotal();

        $ventaticket->descuento = $ventaticket->getDescuento();
        $ventaticket->ganancia = $ventaticket->getGanancia();
        $ventaticket->impuesto_traslado = $ventaticket->getImpuestos('traslado');
        $ventaticket->impuesto_retenido = $ventaticket->getImpuestos('retenido');
        $ventaticket->save();

        dispatch(new AfterGuardarVenta($ventaticket->id));
        $dataToUpdate = [];
        if ($credito) {
            $users = $this->user->getUsersInMyOrg();
            Notification::send($users, new VentaRealizada($this->user->name, $ventaticket->consecutivo,  'Venta a Credito'));
            $ventaticket->forma_de_pago = "C";
            $ventaticket->total_credito = $ventaticket->getTotal();
            $ventaticket->save();
            $cliente = new PuntoVentaCliente($ventaticket->cliente_id);
            $cliente->incrementSaldo($ventaticket->getTotal());
            $cliente->createDeuda($this->user->active_organization_id, $ventaticket);
            $dataToUpdate["ventas_credito"] = $this->ventas_credito + $ventaticket->getTotal();
        } else {
            $dataToUpdate["ventas_efectivo"] = $this->ventas_credito + $ventaticket->getTotal();
        }
        $dataToUpdate = array_merge($dataToUpdate, [
            'acumulado_ventas' => $this->acumulado_ventas + $ventaticket->getTotal(),
            'acumulado_ganancias' => $this->acumulado_ganancias + $ventaticket->getGanancia(),
            'numero_ventas' => $this->numero_ventas + 1,
        ]);
        $this->update($dataToUpdate);
        $ventaticket->notifyTienda();
    }
    function updateAcumulados()
    {

        $tickets = $this->ventatickets()->get();
        $this->update([
            'ventas_efectivo' => $tickets->sum('fp_efectivo'),
            'ventas_cheque' => $tickets->sum('fp_cheque'),
            'ventas_tarjeta_debito' => $tickets->sum('fp_tarjeta_debito'),
            'ventas_tarjeta_credito' => $tickets->sum('fp_tarjeta_credito'),
            'ventas_transferencia' => $tickets->sum('fp_transferencia'),
            'ventas_vales_de_despensa' => $tickets->sum('fp_vales_de_despensa'),
        ]);
    }
    function guardarCotizacion(Cotizacion $cotizacion)
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
        return $cotizacion;
        // $this->createVentaFromCotizacion($cotizacion);
    }
    function finzalizarCotizacion(Cotizacion $cotizacion)
    {
        foreach ($cotizacion->articulos as $articulo) {
            if (!$articulo->product->enuffInventario($cotizacion->almacen_id, $articulo->cantidad)) {
                throw new OperationalException("No hay suficiente inventario", 422);
            }
        }
        $this->createVentaFromCotizacion($cotizacion);
    }
    function createVentaFromCotizacion($cotizacion)
    {
        $venta = new Ventaticket;

        $venta->organization_id = $cotizacion->organization_id;
        $venta->user_id = $cotizacion->user_id;
        $venta->esta_abierto = false;
        $venta->pendiente = false;
        $venta->turno_id = $this->id;
        $venta->cliente_id = $cotizacion->cliente_id;
        $venta->almacen_id = $cotizacion->almacen_id;
        $venta->consecutivo = $cotizacion->consecutivo;
        $venta->nombre = $cotizacion->nombre;
        $venta->save();
        $cotizacion->ventaticket_id = $venta->id;
        $cotizacion->save();
        foreach ($cotizacion->articulos as $co_articulo) {
            $product = new ProductArticuloVenta($co_articulo->product_id, $co_articulo->precio, $co_articulo->cantidad);
            $venta->registerArticulo($product);
        }
    }
    function createDevolucion(Ventaticket $venta): Devolucione
    {
        return Devolucione::create([
            'tipo_devolucion' => "P",
            'organization_id' => $this->user->active_organization_id,
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
