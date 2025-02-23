<?php

namespace Tests\Feature\PuntoVenta;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PuntoVenta\Data\AsignarAlmacenDBData as DBData;
use Tests\TestCase;

class AsignarAlmacenTest extends TestCase
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

        $response = $this->getJson(
            route('puntoventa.asignaralmacen', [
                    'ventaticket' => 1,
                    'almacen' => 1,
            ])
        );
        $response->assertStatus(200);
    }
}