<?php

namespace Tests\Feature\PuntoVenta;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PuntoVenta\Data\DestroyDBData as DBData;
use Tests\TestCase;

class DestroyTest extends TestCase
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
        $articulo= $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.destroyarticulo', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                ]
            ])
        );
        // $response->dump();
        $response->assertStatus(200);
        $this->assertDatabaseCount('ventaticket_articulos', 0);
    }
    public function test_increment_cantidad()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $articulo= $dbData->articulo;
        $product= $dbData->productAzucar;

        $response = $this->postJson(
            route('puntoventa.destroyarticulo', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                ]
            ])
        );
        $cantidad = $product->getCantidadActual(1);
        $this->assertEquals($cantidad, 500);
    }
    public function test_articulo_doesnt_exist()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();

        $response = $this->postJson(
            route('puntoventa.destroyarticulo', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => 100,
                ]
            ])
        );
        $response->assertServerError();
    }
}