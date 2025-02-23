<?php

namespace Tests\Feature\PuntoVenta;

use App\Models\VentaticketArticulo;
use App\MyClasses\PuntoVenta\ProductArticuloVenta;
use App\MyClasses\PuntoVenta\TicketVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PuntoVenta\Data\UpdateDBData as DBData;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
   
    //test 200 status only
    public function test_update()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        // $response->dump();
        $response->assertStatus(200);
        $this->assertDatabaseCount('ventaticket_articulos', 1);
    }
    //cantidad not enuff enough inventory
    public function test_update_no_enough_inventory()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();

        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
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
    public function test_update_it_already_exists_in_ticket()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();

        $dbData->cargarUpdateDatos();

        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        
        // $response->dump();
        $this->assertDatabaseCount('ventaticket_articulos', 1);
    }
    //articulo already_exists_in_ticket()
    public function test_update_check_basic_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();

        $dbData->cargarUpdateDatos();

        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
       
        $articulo = VentaticketArticulo::first();
        $this->assertEquals($articulo->cantidad, 1);
        $this->assertEquals($articulo->ganancia, 0);
        $this->assertEquals($articulo->importe_descuento, 0);
        $this->assertEquals($articulo->impuesto_traslado, null);
        $this->assertEquals($articulo->precio_usado, 10);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 10);
        $this->assertEquals($articulo->descuento, 0);
    }
    public function test_update_descuento_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        /** @var Product $product */
        $product = $dbData->productAzucar;
        $product->createDescuento(2, 5, 10);

        $dbData->cargarUpdateDatos();

        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                    'precio' => 10,
                    'cantidad' => 2,
                ]
            ])
        );
        
        $articulo = VentaticketArticulo::first();
        $this->assertEquals($articulo->cantidad, 2);
        $this->assertEquals($articulo->ganancia, -2);
        $this->assertEquals($articulo->importe_descuento, 2);
        $this->assertEquals($articulo->impuesto_traslado, null);
        $this->assertEquals($articulo->precio_usado, 10);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 20);
        $this->assertEquals($articulo->descuento, 1);
    }
    public function test_update_taxes_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $productAzucar = $dbData->productAzucar;
        $productAzucar->taxes()->attach([$tax->id]);
        $dbData->cargarUpdateDatos();

        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                    'precio' => 10,
                    'cantidad' => 2,
                ]
            ])
        );
        
        $articulo = VentaticketArticulo::first();
        $this->assertEquals($articulo->cantidad, 2);
        $this->assertEquals($articulo->ganancia, -2.7586206896552);
        $this->assertEquals($articulo->importe_descuento, 0);
        $this->assertEquals($articulo->impuesto_traslado, 2.7586206896552);
        $this->assertEquals($articulo->impuesto_retenido, 0);
        $this->assertEquals($articulo->precio_usado, 8.6206896551724);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 17.241379310345);
        $this->assertEquals($articulo->descuento, 0);
    }
    public function test_update_taxes_descuento_values()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $organization = $dbData->organization;
        $tax = $organization->createTax('002', '1', '16', 'traslado');
        $productAzucar = $dbData->productAzucar;
        $productAzucar->taxes()->attach([$tax->id]);
        $productAzucar->createDescuento(1, 5, 10);

        $dbData->cargarUpdateDatos();

        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
        
        $articulo = VentaticketArticulo::first();
        $this->assertEquals($articulo->cantidad, 1);
        $this->assertEquals($articulo->ganancia, -2.2413793103448);
        $this->assertEquals($articulo->importe_descuento, 0.86206896551724);
        $this->assertEquals($articulo->impuesto_traslado, 1.2413793103448);
        $this->assertEquals($articulo->impuesto_retenido, 0);
        $this->assertEquals($articulo->precio_usado, 8.6206896551724);
        $this->assertEquals($articulo->cantidad_devuelta, 0);
        $this->assertEquals($articulo->precio_final, 8.6206896551724);
        $this->assertEquals($articulo->descuento, 0.862068965517240);
    }
    public function test_update_it_substracts_inventario()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $productAzucar = $dbData->productAzucar;
        $almacen = $dbData->almacen;
        $dbData->cargarUpdateDatos();

        $articulo = $dbData->articulo;

        $response = $this->postJson(
            route('puntoventa.update', [
                'params' => [
                    'ventaticket' => 1,
                    'articulo' => $articulo->id,
                    'precio' => 10,
                    'cantidad' => 1,
                ]
            ])
        );
       
        $cantidad = $productAzucar->getCantidadActual($almacen->id);
        $this->assertEquals(499, $cantidad);
    }
}
