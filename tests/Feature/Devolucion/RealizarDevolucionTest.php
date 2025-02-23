<?php

namespace Tests\Feature\Devolucion;

use App\Models\Devolucione;
use App\Models\InventHistorial;
use App\Models\VentaticketArticulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Devolucion\Data\RegisterDBData as DBData;
use Tests\TestCase;

class RealizarDevolucionTest extends TestCase
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
        $dbData->createDevolucionTicket();
        $dbData->registerArticuloDevolucion();

        $response = $this->postJson(
            route('devolucion.realizardevolucion', [
                'ticket' => 1,
            ])
        );
        $response->assertStatus(200);
        $this->assertDatabaseCount('devoluciones', 1);
    }
    public function test_no_turno()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->guardarVenta();
        $dbData->createDevolucionTicket();
        $dbData->registerArticuloDevolucion();
        $dbData->turno->delete();

        $response = $this->postJson(
            route('devolucion.realizardevolucion', [
                'ticket' => 1,
            ])
        );
        $response->assertStatus(500)->assertSee('No has habilitado la caja');
    }
    public function test_check_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->guardarVenta();
        $dbData->createDevolucionTicket();
        $dbData->registerArticuloDevolucion();

        $response = $this->postJson(
            route('devolucion.realizardevolucion', [
                'ticket' => 1,
            ])
        );
        $turno = $dbData->turno->refresh();
        $ventaTicket = $dbData->ventaTicket->refresh();
        $productAzucar = $dbData->productAzucar->refresh();
        $productAzucarKit = $dbData->productAzucarKit->refresh();
        $almacen = $dbData->almacen;
        $ticketDevolucion = Devolucione::first();
        $this->assertEquals(10, $turno->devoluciones_ventas_efectivo);
        $this->assertEquals(-490, $turno->acumulado_ganancias);

        $this->assertEquals(10, $ventaTicket->total_devuelto);

        $this->assertEquals('C', $ticketDevolucion->tipo_devolucion);
        $this->assertEquals(1, $ticketDevolucion->turno_id);
        $this->assertEquals(10, $ticketDevolucion->total_devuelto);

        $cantidadOne = $productAzucar->getCantidadActual($almacen->id);
        $this->assertEquals(450, $cantidadOne);
        $cantidadTwo = $productAzucarKit->getCantidadActual($almacen->id);
        $this->assertEquals(9.0, $cantidadTwo);
        $articulo = VentaticketArticulo::first();
        $this->assertEquals(1, $articulo->fue_devuelto);
        $this->assertEquals(1, $articulo->cantidad_devuelta);

        $historialOne = InventHistorial::where('id', 1)->first();
        $historialTwo = InventHistorial::where('id', 3)->first();

        $this->assertEquals(449, $historialOne->cantidad_anterior);
        $this->assertEquals(448, $historialOne->cantidad_despues);

        $this->assertEquals(449, $historialTwo->cantidad_anterior);
        $this->assertEquals(450, $historialTwo->cantidad_despues);
    }
}
