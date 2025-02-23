<?php

namespace Tests\Feature\Devolucion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Devolucion\Data\RegisterDBData as DBData;
use Tests\TestCase;

class CreateDevolucionTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    public function test_()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->guardarVenta();

        $response = $this->postJson(
            route('devolucion.createDevolucion', [
                'venta' => $dbData->ventaTicket->id,
            ])
        );
        // $response->dump();
        $response->assertStatus(201);
        $this->assertDatabaseCount('devoluciones', 1);
    }
    //test 200 status only
    public function test_no_turno()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->guardarVenta();
        $dbData->turno->delete();

        $response = $this->postJson(
            route('devolucion.createDevolucion', [
                'venta' => $dbData->ventaTicket->id,
            ])
        );
        $response->assertStatus(500)->assertSee('No has habilitado la caj');
    }
    //test 200 status only
    public function test_se_ha_devuelto_todo()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->guardarVenta();
        $dbData->ventaTicket->refresh()->total_devuelto = $dbData->ventaTicket->total;
        $dbData->ventaTicket->save();

        $response = $this->postJson(
            route('devolucion.createDevolucion', [
                'venta' => $dbData->ventaTicket->id,
            ])
        );
        $response->assertStatus(500)->assertSee('Venta se ha devuelto por completo');
    }
    public function test_eliminar_devolucion()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->createDevolucionTicket();
        $dbData->registerArticuloDevolucion();

        $response = $this->postJson(
            route('devolucion.eliminarDevolucion', [
                'id' => 1,
            ])
        );
        $this->assertDatabaseCount('devoluciones', 1);
        $response->assertStatus(200);
    }
}
