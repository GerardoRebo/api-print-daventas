<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Product\Data\RegisterDBData as DBData;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    //:todo devoluciones con impuestos y descuentos
    public function test_store()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();

        $response = $this->postJson(
            route('products.store', [
                'codigo' => '001',
                'name' => 'test',
                'descripcion' => 'Descrip',
                'tventa' => 'U',
                'pcosto' => 10,
                'porcentaje_ganancia' => 10,
                'prioridad' => false,
                'es_kit' => false,
                'perecedero' => null,
            ])
        );
        $this->assertDatabaseCount('products', 5);
        $response->assertStatus(201);
    }
    public function test_ajustar()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();

        $response = $this->putJson(
            route('products.ajustar', [
                'product' => 1,
                'almacenActualId' => 1,
                'name' => 'Test',
                'pcosto' => 10,
                'pventa' => 10,
                'precio_mayoreo' => 10,
                'cantidad' => 10,
            ])
        );
        $response->assertStatus(200);
    }
    
}
