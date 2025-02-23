<?php

namespace Tests\Feature\PuntoVenta;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PuntoVenta\Data\AsignarAlmacenDBData as DBData;
use Tests\TestCase;

class GetExistenciasTest extends TestCase
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

        $response = $this->getJson(
            route('puntoventa.getexistencias', [
                    'productId' => 1,
            ])
        );
        //:todo refactor
        // $response->dump();
        $response->assertStatus(200);
    }
}