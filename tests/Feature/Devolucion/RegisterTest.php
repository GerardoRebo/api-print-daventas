<?php

namespace Tests\Feature\Devolucion;

use App\Models\ArticuloOcTax;
use App\Models\ArticulosOc;
use App\MyClasses\Movimientos\ProductArticuloCompra;
use App\MyClasses\Movimientos\TicketCompra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Devolucion\Data\RegisterDBData as DBData;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    //:todo devoluciones con impuestos y descuentos
    public function test_register()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->createDevolucionTicket();

        $response = $this->postJson(
            route('devolucion.register', [
                'devolucion' => 1,
                'articulo' => 1,
                'cantidad' => 1,
            ])
        );
        // $response->dump();
        $response->assertStatus(200);
        $this->assertDatabaseCount('devoluciones_articulos', 1);
    }
    public function test_destroy_articulo()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->createDevolucionTicket();
        $dbData->registerArticuloDevolucion();

        $response = $this->postJson(
            route('devolucion.destroyArticulo', [
                'id' => 1,
            ])
        );
        // $response->dump();
        $response->assertStatus(200);
        $this->assertDatabaseCount('devoluciones_articulos', 0);
    }
    
    
}
