<?php

namespace Tests\Feature\PuntoVenta;

use App\Models\Abono;
use App\Models\InventarioBalance;
use App\Models\InventHistorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\Feature\PuntoVenta\Data\GuardarDBData as DBData;
use Tests\TestCase;

class CancelarVentaTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    public function test_200()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();

        $response = $this->postJson(
            route('puntoventa.cancelarventa', [
                'params' => [
                    'ticket' => 1,
                ]
            ])
        );
        $response->assertStatus(200);
    }
    //test 
    public function test_no_turno()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $dbData->turno->delete();

        $response = $this->postJson(
            route('puntoventa.cancelarventa', [
                'params' => [
                    'ticket' => 1,
                ]
            ])
        );
        $response->assertStatus(500)->assertSee("No has habilitado la caja");
    }
    //test ya facturado
    public function test_ya_facturado()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $ventaticket = $dbData->ventaTicket;
        $ventaticket->facturado_en = now();
        $ventaticket->save();

        $response = $this->postJson(
            route('puntoventa.cancelarventa', [
                'params' => [
                    'ticket' => 1,
                ]
            ])
        );
        $response->assertStatus(500)->assertSee("ha sido facturado");
    }

    //test historial
    public function test_articulos_historial()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $formaPago = [
            "efectivo" => 20,
            "efectivo_ref" => "",
            "tarjeta_debito" => 0,
            "tarjeta_debito_ref" => "",
            "tarjeta_credito" => 0,
            "tarjeta_credito_ref" => "",
            "transferencia" => 0,
            "transferencia_ref" => "",
            "cheque" => 0,
            "cheque_ref" => "",
            "vales_de_despensa" => 0,
            "vales_de_despensa_ref" => "",
            "pago_con" => "20.00"
        ];
        $response = $this->postJson(
            route('puntoventa.guardarventa', [
                'ventaticket' => 1,
                'forma_pago' => $formaPago,
                'credito' => false,
            ])
        );
        $historialOne = InventHistorial::find(1);
        $historialTwo = InventHistorial::find(2);
        $this->assertEquals(-1, $historialOne->cantidad);
        $this->assertEquals(449, $historialOne->cantidad_anterior);
        $this->assertEquals(448, $historialOne->cantidad_despues);
        $this->assertEquals('Venta', $historialOne->descripcion);

        $this->assertEquals(-1, $historialTwo->cantidad);
        $this->assertEquals(8.98, $historialTwo->cantidad_anterior);
        $this->assertEquals(7.98, $historialTwo->cantidad_despues);
        $this->assertEquals('Venta', $historialTwo->descripcion);


        $this->assertDatabaseCount('invent_historials', 2);
        $response->assertOk();
    }
    //:todo notify users
    //test ya facturado
    public function test_inventario_after_cancel()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();

        $response = $this->postJson(
            route('puntoventa.cancelarventa', [
                'params' => [
                    'ticket' => 1,
                ]
            ])
        );
        $inventarioCantidad = InventarioBalance::where('product_id', 1)->first()->cantidad_actual;
        $this->assertEquals(500, $inventarioCantidad);
    }
    public function test_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $dbData->guardarVenta();
        $ticket = $dbData->ventaTicket->refresh();

        $response = $this->postJson(
            route('puntoventa.cancelarventa', [
                'params' => [
                    'ticket' => 1,
                ]
            ])
        );
        $ticket->refresh();
        // dd($ticket->toArray());
        $turno = $dbData->turno;
        $turno->refresh();

        $this->assertEquals(1, $ticket->esta_cancelado);
        $this->assertEquals(20, $turno->devoluciones_ventas_efectivo);
        $this->assertEquals(0, $turno->efectivo_al_cierre);
        $this->assertEquals(0, $turno->acumulado_ganancias);
    }
    public function test_values_credito()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $dbData->crearCliente();
        $dbData->guardarVentaCredito();

        $ticket = $dbData->ventaTicket->refresh();

        $response = $this->postJson(
            route('puntoventa.cancelarventa', [
                'params' => [
                    'ticket' => 1,
                ]
            ])
        );
        $ticket->refresh();
        $turno = $dbData->turno;
        $turno->refresh();

        $this->assertEquals(1, $ticket->esta_cancelado);

        $this->assertEquals(20, $turno->devoluciones_ventas_credito);
        $this->assertEquals(0, $turno->acumulado_ganancias);
    }
    public function test_credito_abono_exists()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $dbData->crearCliente();
        $dbData->guardarVentaCredito();


        $response = $this->postJson(
            route('puntoventa.cancelarventa', [
                'params' => [
                    'ticket' => 1,
                ]
            ])
        );
        // dd(Abono::all()->toArray());
        $abono = Abono::first();
        $this->assertDatabaseCount('abonos', 1);
        $this->assertEquals(20, $abono->abono);
        $this->assertEquals(0, $abono->saldo);
        $this->assertEquals('Cancelacion venta', $abono->comentarios);
        $this->assertEquals('E', $abono->forma_de_pago);
    }
}
