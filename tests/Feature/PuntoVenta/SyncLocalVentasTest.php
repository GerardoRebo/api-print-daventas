<?php

namespace Tests\Feature\PuntoVenta;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PuntoVenta\Data\SyncLocalVentasDBData as DBData;
use Tests\TestCase;

class SyncLocalVentasTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
   
    //test 200 status only
    // public function test_()
    // {
    //     $dbData = new DBData;
    //     $dbData->cargarDatos();

    //     $response = $this->postJson(
    //         route('puntoventa.syncLocalVentas', [
    //                 'tickets' => [1],
    //         ])
    //     );
    //     $response->assertStatus(200);
    // }
}