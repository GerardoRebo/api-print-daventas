<?php

namespace Tests\Feature\Movimiento;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Movimiento\Data\RegisterDBData as DBData;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class BorrarticketTest extends TestCase
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
            route('movimientos.borrarticket', [
                'movimiento' => 1,
            ])
        );
        $response->assertStatus(200);
    }
    // public function test_no_turno()
    // {
    //     $dbData = new DBData;
    //     $dbData->cargarDatos();
    //     $dbData->registerArticuloAzucar();
    //     $dbData->registerArticuloKit();
    //     $dbData->setCompra();
    //     $dbData->turno->delete();

    //     $response = $this->postJson(
    //         route('movimientos.borrarticket', [
    //             'movimiento' => 1,
    //         ])
    //     );
    //     $response->assertStatus(500) // Check for a server error (status code 500)
    //         ->assertSeeText("No has habilitado la caja"); // Check for the specific error message
    // }
    public function test_ya_recibido()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->setCompra();
        $dbData->ordenCompra->estado = 'R';
        $dbData->ordenCompra->save();

        $response = $this->postJson(
            route('movimientos.borrarticket', [
                'movimiento' => 1,
            ])
        );
        $response->assertStatus(500) // Check for a server error (status code 500)
            ->assertSeeText("Este ticket ya esta procesado"); // Check for the specific error message
    }
    public function test_compra()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();

        $response = $this->postJson(
            route('movimientos.borrarticket', [
                'movimiento' => 1,
            ])
        );
        $cantidad = $dbData->productAzucar->getCantidadActual(1);
        assertEquals(500, $cantidad);
    }
    public function test_transferencia()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setTransferencia();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();

        $response = $this->postJson(
            route('movimientos.borrarticket', [
                'movimiento' => 1,
            ])
        );
        $cantidad = $dbData->productAzucar->getCantidadActual(1);
        assertEquals(500, $cantidad);
    }
}
