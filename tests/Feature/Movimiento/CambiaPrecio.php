<?php

namespace Tests\Feature\Movimiento;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Movimiento\Data\RegisterDBData as DBData;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class CambiaPrecioTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    //:todo refactor
    public function test_()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();

        $response = $this->postJson(
            route('movimientos.destroyarticulo', [
                'precio' => 1,
                'producto' => 1,
                'almacen' => 1,
            ])
        );
        // $cantidad = $dbData->productAzucar->getCantidadActual(1);
        // $this->assertEquals(500, $cantidad);

        // $this->assertDatabaseCount('articulos_ocs', 1);
        // $response->assertStatus(200);
    }
    
}
