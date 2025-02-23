<?php

namespace Tests\Feature\Movimiento;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Movimiento\Data\RegisterDBData as DBData;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class destroyArticuloTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    public function test_compra()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();

        $response = $this->postJson(
            route('movimientos.destroyarticulo', [
                'movimiento' => 1,
                'articulo' => 1,
            ])
        );
        $cantidad = $dbData->productAzucar->getCantidadActual(1);
        $this->assertEquals(500, $cantidad);

        $this->assertDatabaseCount('articulos_ocs', 1);
        $response->assertStatus(200);
    }
    //test 200 status only
    public function test_transferencia()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setTransferencia();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();

        $response = $this->postJson(
            route('movimientos.destroyarticulo', [
                'movimiento' => 1,
                'articulo' => 1,
            ])
        );
        $cantidad = $dbData->productAzucar->getCantidadActual(1);

        $this->assertEquals(450, $cantidad);

        $this->assertDatabaseCount('articulos_ocs', 1);
        $response->assertStatus(200);
    }
}
