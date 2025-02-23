<?php

namespace Tests\Feature\Movimiento;

use App\Models\InventHistorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Movimiento\Data\RegisterDBData as DBData;
use Tests\TestCase;

class CancelarmovimientoTest extends TestCase
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
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->setCompra();

        $response = $this->postJson(
            route('movimientos.cancelarmovimiento', [
                'movimiento' => 1,
            ])
        );
        $response->assertStatus(200);
    }
    public function test_no_turno()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->registerArticuloAzucar();
        $dbData->setCompra();
        $dbData->turno->delete();

        $response = $this->postJson(
            route('movimientos.cancelarmovimiento', [
                'movimiento' => 1,
            ])
        );
        $response->assertStatus(500) // Check for a server error (status code 500)
            ->assertSeeText("No has habilitado la caja"); // Check for the specific error message
    }
    public function test_compra_recibida_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->setCompra();
        $dbData->ordenCompra->estado = 'R';
        $dbData->ordenCompra->save();

        $response = $this->postJson(
            route('movimientos.cancelarmovimiento', [
                'movimiento' => 1,
            ])
        );
        $historialOne= InventHistorial::find(1);
        $historialTwo= InventHistorial::find(2);

        $this->assertEquals(-1, $historialOne->cantidad);
        $this->assertEquals(501, $historialOne->cantidad_anterior);
        $this->assertEquals(500, $historialOne->cantidad_despues);
        $this->assertEquals('Cancelacion de Compra', $historialOne->descripcion);

        $this->assertEquals(-1, $historialTwo->cantidad);
        $this->assertEquals(11, $historialTwo->cantidad_anterior);
        $this->assertEquals(10, $historialTwo->cantidad_despues);
        $this->assertEquals('Cancelacion de Compra', $historialTwo->descripcion);

        $cantidad= $dbData->productAzucar->getCantidadActual(1);
        $this->assertEquals(500, $cantidad);

        $this->assertDatabaseCount('invent_historials', 2);

        $response->assertOk();
    }
    public function test_compra_pendiente_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->ordenCompra->estado = 'P';
        $dbData->ordenCompra->save();

        $response = $this->postJson(
            route('movimientos.cancelarmovimiento', [
                'movimiento' => 1,
            ])
        );
        $cantidad= $dbData->productAzucar->getCantidadActual(1);
        $this->assertEquals(500, $cantidad);

        $this->assertDatabaseCount('invent_historials', 0);
        $response->assertOk();
    }
    public function test_transfe_recibida_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setTransferencia();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->ordenCompra->estado = 'R';
        $dbData->ordenCompra->save();
        $dbData->setAlmacenDestino();

        $response = $this->postJson(
            route('movimientos.cancelarmovimiento', [
                'movimiento' => 1,
            ])
        );
        $historialOne= InventHistorial::find(1);
        $historialTwo= InventHistorial::find(2);
        $historialThree= InventHistorial::find(3);
        $historialFour= InventHistorial::find(4);

        $this->assertEquals(1, $historialOne->cantidad);
        $this->assertEquals(499, $historialOne->cantidad_anterior);
        $this->assertEquals(500, $historialOne->cantidad_despues);
        $this->assertEquals(1, $historialOne->almacen_id);
        $this->assertEquals('Cancelacion de Transferencia Enviada', $historialOne->descripcion);

        $this->assertEquals(1, $historialTwo->cantidad);
        $this->assertEquals(9, $historialTwo->cantidad_anterior);
        $this->assertEquals(10, $historialTwo->cantidad_despues);
        $this->assertEquals(1, $historialTwo->almacen_id);
        $this->assertEquals('Cancelacion de Transferencia Enviada', $historialTwo->descripcion);

        $this->assertEquals(-1, $historialThree->cantidad);
        $this->assertEquals(450, $historialThree->cantidad_anterior);
        $this->assertEquals(449, $historialThree->cantidad_despues);
        $this->assertEquals(2, $historialThree->almacen_id);
        $this->assertEquals('Cancelacion de Transferencia Recibida', $historialThree->descripcion);

        $this->assertEquals(-1, $historialFour->cantidad);
        $this->assertEquals(9.98, $historialFour->cantidad_anterior);
        $this->assertEquals(8.98, $historialFour->cantidad_despues);
        $this->assertEquals(2, $historialFour->almacen_id);
        $this->assertEquals('Cancelacion de Transferencia Recibida', $historialFour->descripcion);

        $this->assertDatabaseCount('invent_historials', 4);

        $cantidadOne= $dbData->productAzucar->getCantidadActual(1);
        $cantidadTwo= $dbData->productAzucar->getCantidadActual(2);

        $this->assertEquals(500, $cantidadOne);
        $this->assertEquals(449, $cantidadTwo);

        $response->assertOk();
    }
    public function test_transferencia_pendiente_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setTransferencia();
        $dbData->setAlmacenDestino();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->ordenCompra->estado = 'P';
        $dbData->ordenCompra->save();

        $response = $this->postJson(
            route('movimientos.cancelarmovimiento', [
                'movimiento' => 1,
            ])
        );
        $historialOne= InventHistorial::find(1);
        $historialTwo= InventHistorial::find(2);

        $this->assertEquals(1, $historialOne->cantidad);
        $this->assertEquals(499, $historialOne->cantidad_anterior);
        $this->assertEquals(500, $historialOne->cantidad_despues);
        $this->assertEquals(1, $historialOne->almacen_id);
        $this->assertEquals('Cancelacion de Transferencia Pendiente', $historialOne->descripcion);

        $this->assertEquals(1, $historialTwo->cantidad);
        $this->assertEquals(9, $historialTwo->cantidad_anterior);
        $this->assertEquals(10, $historialTwo->cantidad_despues);
        $this->assertEquals(1, $historialTwo->almacen_id);
        $this->assertEquals('Cancelacion de Transferencia Pendiente', $historialTwo->descripcion);

        $this->assertDatabaseCount('invent_historials', 2);

        $cantidadOne= $dbData->productAzucar->getCantidadActual(1);
        $cantidadTwo= $dbData->productAzucar->getCantidadActual(2);

        $this->assertEquals(500, $cantidadOne);
        $this->assertEquals(500, $cantidadTwo);

        $response->assertOk();
    }
    
}
