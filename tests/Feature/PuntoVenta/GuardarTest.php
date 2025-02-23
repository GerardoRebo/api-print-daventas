<?php

namespace Tests\Feature\PuntoVenta;

use App\Models\Deuda;
use App\Models\InventHistorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\PuntoVenta\Data\GuardarDBData as DBData;
use Tests\TestCase;

class GuardarTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    public function test_register()
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
        $response->assertStatus(200);
    }
    public function test_no_turno()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $dbData->turno->delete();

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
                'forma_pago' => 10,
                'credito' => false,
            ])
        );
        $response->assertStatus(500) // Check for a server error (status code 500)
            ->assertSeeText("No has habilitado la caja, seras redireccionado"); // Check for the specific error message
    }
    public function test_check_values()
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
        $ventaTicket = $dbData->ventaTicket->refresh();
        $turno = $dbData->turno->refresh();
        $this->assertEquals($ventaTicket->subtotal, 20);
        $this->assertEquals($ventaTicket->impuestos, 0);
        $this->assertEquals($ventaTicket->total, 20);
        $this->assertEquals($ventaTicket->ganancia, -490);
        $this->assertEquals($ventaTicket->esta_abierto, 0);
        $this->assertEquals($ventaTicket->pago_con, 20);
        //:todo
        // $this->assertEquals($ventaTicket->numero_de_articulos, 1);
        $this->assertEquals($ventaTicket->forma_de_pago, 'E');
        $this->assertEquals($ventaTicket->descuento, 0);

        //turno
        $this->assertEquals($turno->acumulado_ganancias, -490);
        $this->assertEquals($turno->ventas_efectivo, 20);
        // $this->assertEquals($turno->ventas_tarjeta, 0);
        $this->assertEquals($turno->ventas_credito, 0);
        $this->assertEquals($turno->efectivo_al_cierre, 20);
        $this->assertEquals($turno->numero_ventas, 1);
    }
    public function test_notify_different_precio()
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
        Notification::spy();
        $response = $this->postJson(
            route('puntoventa.guardarventa', [
                'ventaticket' => 1,
                'forma_pago' => $formaPago,
                'credito' => false,
            ])
        );
        // dd(Notification::assertSentTo());
        // Notification::assertSentTo(auth()->user(), PrecioAjustado::class);
        Notification::assertCount(0);
        $response->assertOk();
    }
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
    public function test_credito()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $dbData->crearCliente();
        $ventaticket = $dbData->ventaTicket;
        $cliente = $dbData->cliente;
        $ventaticket->cliente_id = $cliente->refresh()->id;
        $ventaticket->save();
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
                'credito' => true,
            ])
        );
        $ventaticket->refresh();
        $cliente->refresh();
        $turno = $dbData->turno->refresh();
        $deuda = Deuda::first();
        $this->assertEquals('C', $ventaticket->forma_de_pago);
        $this->assertEquals(20, $turno->ventas_credito);
        $this->assertEquals(20, $cliente->saldo_actual);
        $this->assertEquals(20, $deuda->deuda);
        $this->assertEquals(20, $deuda->saldo);

        $response->assertOk();
    }
}
