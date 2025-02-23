<?php

namespace Tests\Feature\Movimiento;

use App\Models\ArticuloOcTax;
use App\Models\ArticulosOc;
use App\MyClasses\Movimientos\ProductArticuloCompra;
use App\MyClasses\Movimientos\TicketCompra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Movimiento\Data\RegisterDBData as DBData;
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
    public function test_register()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucar;

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        // $response->dump();
        $response->assertStatus(200);
        $this->assertDatabaseCount('orden_compras', 1);
    }
    //cantidad null
    //:todo
    // public function test_register_cantidad_null()
    // {
    //     $dbData = new DBData;
    //     $dbData->cargarDatos();
    //     $product= $dbData->productAzucar;

    //     $response = $this->postJson(
    //         route('movimientos.register', [
    //             'params' => [
    //                 'movimiento' => 1,
    //                 'productActualId' => $product->id,
    //                 'precio' => 10,
    //                 'cantidad' => null,
    //             ]
    //         ])
    //     );
    //     // $response->dump();
    //     $response->assertSee("Cantidad Nulo");
    // }
    //test 200 status only
    public function test_already_received()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucar;
        $dbData->ordenCompra->estado= 'R';
        $dbData->ordenCompra->save();


        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        // $response->dump();
        $response->assertStatus(500)->assertSee('recibido');
    }
    public function test_cambia_costo_compra()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $product= $dbData->productAzucar;

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );

        $product->refresh();
        $productKit=$dbData->productAzucarKit->refresh();
        $this->assertEquals(20, $product->pcosto);
        $this->assertEquals(1000, $productKit->pcosto);
    }
    public function test_cambia_costo_compra_kit()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $productKit= $dbData->productAzucarKit;

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $productKit->id,
                    'precio' => 1000,
                    'cantidad' => 1,
                ]
            ])
        );

        $productKit->refresh();
        $product=$dbData->productAzucar->refresh();
        $this->assertEquals(20, $product->pcosto);
        $this->assertEquals(1000, $productKit->pcosto);
    }
    public function test_no_cambia_costo_transferencia()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucar;

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );

        $product->refresh();
        $productKit=$dbData->productAzucarKit->refresh();
        $this->assertEquals(10, $product->pcosto);
        $this->assertEquals(500, $productKit->pcosto);
    }
    // articulo already_exists_in_ticket()
    public function test_articulos_count()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucar;

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        // $response->dump();
        $this->assertDatabaseCount('articulos_ocs', 1);
    }
    // articulo already_exists_in_ticket()
    public function test_register_it_already_exists_in_ticket_count()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucar;
        $ordenCompra= $dbData->ordenCompra;

        // Create a new ProductArticuloCompra object
        $product = new ProductArticuloCompra($product->id, 20, 1);
        
        // Create a new TicketCompra object
        $ordenCompra = new TicketCompra($ordenCompra->id);
        $ordenCompra->registerArticulo($product);

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $this->assertDatabaseCount('articulos_ocs', 1);
    }
    // articulo already_exists_in_ticket()
    public function test_check_basic_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucar;
        $ordenCompra= $dbData->ordenCompra;

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $articulo = ArticulosOc::first();
        $this->assertEquals($articulo->costo_al_ordenar, 20);
        $this->assertEquals($articulo->cantidad_ordenada, 1);
        $this->assertEquals($articulo->total_al_ordenar, 20);
    }
    public function test_check_basic_values_already_exists()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucar;
        $ordenCompra= $dbData->ordenCompra;
        $dbData->setCompra();

        // Create a new ProductArticuloCompra object
        $product = new ProductArticuloCompra($product->id, 20, 1);
        
        // Create a new TicketCompra object
        $ordenCompra = new TicketCompra($ordenCompra->id);
        $ordenCompra->registerArticulo($product);

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $product->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $articulo = ArticulosOc::first();
        $this->assertEquals($articulo->costo_al_ordenar, 20);
        $this->assertEquals($articulo->cantidad_ordenada, 2);
        $this->assertEquals($articulo->total_al_ordenar, 40);
    }
    //:todo refactor logic for taxes ventas & compras
    public function test_register_taxes_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $productAzucar = $dbData->productAzucar;
        //by defaul sets true for venta & compra
        $productAzucar->taxes()->attach([$tax->id]);

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $productAzucar->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $articulo = ArticulosOc::first();
        //:todo debe ser impuestos al ordenar no al recibir
        $this->assertEquals(3.2, $articulo->impuestos_al_enviar);
    }
    public function test_register_taxes_records()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $productAzucar = $dbData->productAzucar;
        //by defaul sets true for venta & compra
        $productAzucar->taxes()->attach([$tax->id]);

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $productAzucar->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $this->assertDatabaseCount('articulo_oc_taxes', 1);
    }
    public function test_register_taxes_records_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $productAzucar = $dbData->productAzucar;
        //by defaul sets true for venta & compra
        $productAzucar->taxes()->attach([$tax->id]);

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $productAzucar->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $tax=ArticuloOcTax::first();
        $this->assertEquals(3.2, $tax->importe);
    }
    //:todo test two taxes
    public function test_register_two_taxes_records()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $tax2 = $organization->createTax('003', '1', '10', 'traslado');
        $productAzucar = $dbData->productAzucar;
        //by defaul sets true for venta & compra
        $productAzucar->taxes()->attach([$tax->id]);
        $productAzucar->taxes()->attach([$tax2->id]);

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $productAzucar->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $this->assertDatabaseCount('articulo_oc_taxes', 2);
    }
    public function test_register_two_taxes_records_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $tax2 = $organization->createTax('003', '1', '10', 'traslado');
        $productAzucar = $dbData->productAzucar;
        //by defaul sets true for venta & compra
        $productAzucar->taxes()->attach([$tax->id]);
        $productAzucar->taxes()->attach([$tax2->id]);

        $response = $this->postJson(
            route('movimientos.register', [
                'params' => [
                    'movimiento' => 1,
                    'productActualId' => $productAzucar->id,
                    'precio' => 20,
                    'cantidad' => 1,
                ]
            ])
        );
        $tax=ArticuloOcTax::find(1);
        $taxTwo=ArticuloOcTax::find(2);

        $this->assertEquals(3.2, $tax->importe);
        $this->assertEquals(2, $taxTwo->importe);
    }
}
