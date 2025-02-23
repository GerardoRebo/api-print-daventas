<?php

namespace Tests\Feature\PuntoVenta;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PuntoVenta\Data\RegisterDBData as DBData;
use Tests\TestCase;

class SetPendienteTest extends TestCase
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
            route('puntoventa.setpendiente', [
                    'ventaticket' => 1,
            ])
        );
        
        $ticket= $dbData->ventaTicket->refresh();
        $this->assertEquals(1, $ticket->pendiente);
        $response->assertStatus(200);
    }
}