<?php

namespace Tests\Feature\Movimiento;

use App\Models\InventarioBalance;
use App\Models\InventHistorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Movimiento\Data\RegisterDBData as DBData;
use Tests\TestCase;

class GuardarTest extends TestCase
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
        $dbData->registerArticuloAzucar();
        $dbData->setCompra();

        $response = $this->postJson(
            route('movimientos.guardar', [
                'movimiento' => 1,
            ])
        );
        $response->assertStatus(200);
    }
    public function test_no_turno()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->registerArticuloAzucar();
        $dbData->setCompra();
        $dbData->turno->delete();

        $response = $this->postJson(
            route('movimientos.guardar', [
                'movimiento' => 1,
            ])
        );
        $response->assertStatus(500) // Check for a server error (status code 500)
            ->assertSeeText("No has habilitado la caja"); // Check for the specific error message
    }
    public function test_ya_procesado()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->registerArticuloAzucar();
        $dbData->setCompra();
        $dbData->ordenCompra->estado = 'R';
        $dbData->ordenCompra->save();

        $response = $this->postJson(
            route('movimientos.guardar', [
                'movimiento' => 1,
            ])
        );
        $response->assertStatus(500) // Check for a server error (status code 500)
            ->assertSeeText("Movimiento ya esta procesado"); // Check for the specific error message
    }
    public function test_attached_to_proveedor()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->createProveedor();
        $provedor = $dbData->proveedor;
        $dbData->ordenCompra->proveedor_id = $provedor->id;
        $dbData->ordenCompra->save();

        $response = $this->postJson(
            route('movimientos.guardar', [
                'movimiento' => 1,
            ])
        );
        $products = $provedor->refresh()->products;
        $this->assertEquals(2, $products->count());
    }
    public function test_inventario_historials_compra()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();
        $dbData->createProveedor();

        $response = $this->postJson(
            route('movimientos.guardar', [
                'movimiento' => 1,
            ])
        );
        $historialOne= InventHistorial::find(1);
        $historialTwo= InventHistorial::find(2);
        $this->assertEquals(1, $historialOne->cantidad);
        $this->assertEquals(550, $historialOne->cantidad_anterior);
        $this->assertEquals(551, $historialOne->cantidad_despues);
        $this->assertEquals('Compra', $historialOne->descripcion);

        $this->assertEquals(1, $historialTwo->cantidad);
        $this->assertEquals(10.02, $historialTwo->cantidad_anterior);
        $this->assertEquals(11.02, $historialTwo->cantidad_despues);
        $this->assertEquals('Compra', $historialTwo->descripcion);


        $this->assertDatabaseCount('invent_historials', 2);
        $response->assertOk();
    }
    public function test_check_values_compra()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setCompra();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();

        $response = $this->postJson(
            route('movimientos.guardar', [
                    'movimiento' => 1,
            ])
        );
        $ordenCompra =$dbData->ordenCompra->refresh();
        $turno =$dbData->turno->refresh();
        $this->assertEquals($ordenCompra->tipo, 'C');
        $this->assertEquals($ordenCompra->estado, 'R');
        $this->assertEquals($ordenCompra->total_enviado, 20);
        $this->assertEquals($ordenCompra->turno_id, 1);
        
        //turno
        $this->assertEquals($turno->compras, 20);
        $inventario= InventarioBalance::first();
        $this->assertEquals(551, $inventario->cantidad_actual);
    }
    public function test_check_values_transferencia()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->setTransferencia();
        $dbData->setAlmacenDestino();
        $dbData->registerArticuloAzucar();
        $dbData->registerArticuloKit();

        $response = $this->postJson(
            route('movimientos.guardar', [
                    'movimiento' => 1,
            ])
        );
        $ordenCompra =$dbData->ordenCompra->refresh();
        $turno =$dbData->turno->refresh();
        $this->assertEquals($ordenCompra->tipo, 'T');
        $this->assertEquals($ordenCompra->estado, 'R');
        $this->assertEquals($ordenCompra->almacen_origen_id, 1);
        $this->assertEquals($ordenCompra->almacen_destino_id, 2);
        $this->assertEquals($ordenCompra->total_enviado, 20);
        $this->assertEquals($ordenCompra->turno_id, 1);
        
        //turno
        $this->assertEquals($turno->compras, 0);
        $inventario= InventarioBalance::where('almacen_id', 1)->first();
        $inventarioTwo= InventarioBalance::where('almacen_id', 2)->first();
        $this->assertEquals(449, $inventario->cantidad_actual);
        $this->assertEquals(551, $inventarioTwo->cantidad_actual);
    }
}
