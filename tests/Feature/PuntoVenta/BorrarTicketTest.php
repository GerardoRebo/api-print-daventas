<?php

namespace Tests\Feature\PuntoVenta;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PuntoVenta\Data\BorrarDBData as DBData;
use Tests\TestCase;

class BorrarTicketTest extends TestCase
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
        $product=$dbData->productAzucar;

        $response = $this->postJson(
            route('puntoventa.borrarticket', [
                'params' => [
                    'ventaticket' => 1,
                ]
            ])
        );
        $cantidad = $product->getCantidadActual(1);
        $this->assertEquals(500, $cantidad);
    }
}