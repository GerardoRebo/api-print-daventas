<?php

namespace Tests\Feature\PuntoVenta;

use App\Models\VentaticketArticulo;
use App\MyClasses\PuntoVenta\ProductArticuloVenta;
use App\MyClasses\PuntoVenta\TicketVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use Tests\Feature\PuntoVenta\Data\RegisterKitDBData as DBData;
use Tests\TestCase;

class RegisterKitTest extends TestCase
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
        $product= $dbData->productAzucarKit;

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        // $response->dump();
        $response->assertStatus(200);
        $this->assertDatabaseCount('ventaticket_articulos', 1);
    }
    //cantidad null
    public function test_register_cantidad_null()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucarKit;

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => null,
                ]
            ])
        );
        // $response->dump();
        $response->assertSee("Cantidad Nulo");
    }
    //cantidad not enuff enough inventory
    public function test_register_no_enough_inventory()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product = $dbData->productAzucarKit;

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1000,
                ]
            ])
        );
        // $response->dump();
        $response->assertStatus(500) // Check for a server error (status code 500)
         ->assertSeeText("No hay suficiente inventario"); // Check for the specific error message
    }
    //articulo already_exists_in_ticket()
    public function test_register_it_already_exists_in_ticket()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $ventaTicket=$dbData->ventaTicket;
        $product= $dbData->productAzucarKit;
        $product = new ProductArticuloVenta($product->id, 10, 1);
        $ticketVenta = new TicketVenta($ventaTicket->id);
        $ticketVenta->registerArticulo($product);

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        // $response->dump();
        $this->assertDatabaseCount('ventaticket_articulos', 1);
    }
    //articulo already_exists_in_ticket()
    public function test_register_check_basic_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucarKit;

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        $articulo = VentaticketArticulo::first();
        $this->assertEquals($articulo->cantidad, 1);
        $this->assertEquals($articulo->ganancia, -490);
        $this->assertEquals($articulo->importe_descuento, 0);
        $this->assertEquals($articulo->impuesto_traslado, null);
        $this->assertEquals($articulo->precio_usado, 10);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 10);
        $this->assertEquals($articulo->descuento, 0);
    }
    public function test_register_descuento_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        /** @var Product $product */
        $product= $dbData->productAzucarKit;
        $product->createDescuento(2, 5, 10);

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 2,
                ]
            ])
        );
        $articulo = VentaticketArticulo::first();
        $this->assertEquals($articulo->cantidad, 2);
        $this->assertEquals($articulo->ganancia, -982);
        $this->assertEquals($articulo->importe_descuento, 2);
        $this->assertEquals($articulo->impuesto_traslado, null);
        $this->assertEquals($articulo->precio_usado, 10);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 20);
        $this->assertEquals($articulo->descuento, 1);
    }
    public function test_register_taxes_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $product= $dbData->productAzucarKit;
        $product->taxes()->attach([$tax->id]);

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 2,
                ]
            ])
        );
        $articulo = VentaticketArticulo::first();
        // dd($articulo);
        $this->assertEquals($articulo->cantidad, 2);
        $this->assertEquals($articulo->ganancia, -982.75862068966);
        $this->assertEquals($articulo->importe_descuento, 0);
        $this->assertEquals($articulo->impuesto_traslado, 2.7586206896552);
        $this->assertEquals($articulo->impuesto_retenido, 0);
        $this->assertEquals($articulo->precio_usado, 8.6206896551724);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 17.241379310345);
        $this->assertEquals($articulo->descuento, 0);
    }
    public function test_register_taxes_descuento_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $product = $dbData->productAzucarKit;
        $product->taxes()->attach([$tax->id]);
        $product->createDescuento(1, 5, 10);

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        $articulo = VentaticketArticulo::first();
        // dd($articulo);
        $this->assertEquals($articulo->cantidad, 1);
        $this->assertEquals($articulo->ganancia, -492.24137931034);
        $this->assertEquals($articulo->importe_descuento, 0.86206896551724);
        $this->assertEquals($articulo->impuesto_traslado, 1.2413793103448);
        $this->assertEquals($articulo->impuesto_retenido, 0);
        $this->assertEquals($articulo->precio_usado, 8.6206896551724);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 8.6206896551724);
        $this->assertEquals($articulo->descuento, 0.862068965517240);
    }
    public function test_register_it_substracts_inventario()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $product= $dbData->productAzucarKit;
        $almacen = $dbData->almacen;

        $response = $this->postJson(
            route('puntoventa.register', [
                'params' => [
                    'ticketActual' => 1,
                    'productActualId' => $product->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        $cantidad = $product->getCantidadActual($almacen->id);
        $this->assertEquals(9, $cantidad);
    }
}
