<?php

namespace Tests\Feature\Movimiento\Data;

use App\Models\Almacen;
use App\Models\ArticulosOc;
use App\Models\Cliente;
use App\Models\InventarioBalance;
use App\Models\OrdenCompra;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Proveedor;
use App\Models\Turno;
use App\Models\User;
use App\MyClasses\Movimientos\ProductArticuloCompra;
use App\MyClasses\Movimientos\TicketCompra;
use App\MyClasses\PuntoVenta\TicketVenta;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class RegisterDBData
{
    public OrdenCompra $ordenCompra ;
    public Turno $turno;
    public Organization $organization ;
    public Product $productAzucar; 
    public Product $productAzucarKit; 
    public User $user;
    public Almacen $almacen;
    public Cliente $cliente;
    public ArticulosOc $articulo;
    public Proveedor $proveedor;
    public function cargarDatos()
    {
        $this->organization= Organization::create([
            'name' => 'mpmujica',
            'estado' => 'Guerrero',
            'ciudad' => 'Iguala',
            'pais' => 'Mexico',
        ]);
        Sanctum::actingAs(
            /** @var User $user */
            $this->user = User::factory()->create(),
            ['*']
        );
        $this->createProducts();
        $this->turno = $this->user->createTurno();
        $this->ordenCompra = $this->user->getCompraticketAlmacenCliente();
        $this->ordenCompra->almacen_origen_id=$this->almacen->id;
        $this->ordenCompra->save();
    }
    function setAlmacenDestino() {
        $almacen = Almacen::create([
            'organization_id' => 1,
            'name' => 'destino',

        ]);
        $this->ordenCompra->almacen_destino_id=$almacen->id;
        $this->ordenCompra->save();

        $inventarioAzucar = InventarioBalance::create([
            'product_id' => $this->productAzucar->id,
            'almacen_id' => $almacen->id,
            'cantidad_actual' => 500,

        ]);
        
    }
    function setCompra() {
        $this->ordenCompra->tipo='C';
        $this->ordenCompra->save();
    }
    function setTransferencia() {
        $this->ordenCompra->tipo='T';
        $this->ordenCompra->save();
    }
    public function createProducts() {
        $this->almacen = Almacen::create([
            'organization_id' => 1,
            'name' => 'lamina',

        ]);
        $this->productAzucar = Product::create([
            'organization_id' => 1,
            'name' => 'azucar 1k',
            'codigo' => 'a1',
            'es_kit' => 0,
            'pcosto' => 10,
        ]);
        $this->productAzucarKit = Product::create([
            'organization_id' => 1,
            'name' => 'Bulto azucar 50k',
            'codigo' => 'a50',
            'es_kit' => 1,
            'pcosto' => 500,

        ]);
        $productHarina = Product::create([
            'organization_id' => 1,
            'name' => 'Harina',
            'codigo' => 'h1',
            'es_kit' => 0,
        ]);
        $productHarinaKit = Product::create([
            'organization_id' => 1,
            'name' => 'BultoHarina',
            'codigo' => 'h50',
            'es_kit' => 1,
        ]);
        $inventarioAzucar = InventarioBalance::create([
            'product_id' => $this->productAzucar->id,
            'almacen_id' => $this->almacen->id,
            'cantidad_actual' => 500,

        ]);
        $inventarioHarina = InventarioBalance::create([
            'product_id' => $productHarina->id,
            'almacen_id' => $this->almacen->id,
            'cantidad_actual' => 500,

        ]);
        DB::table('product_components')->insert([
            ['product_id' => $this->productAzucarKit->id,
            'product_hijo_id'=> $this->productAzucar->id,
            'cantidad' => 50],
            ['product_id' => $productHarinaKit->id,
            'product_hijo_id'=> $productHarina->id,
            'cantidad' => 50]
        ]);
        DB::table('precios')->insert([
            ['product_id' => $this->productAzucar->id,
            'almacen_id' => $this->almacen->id,
            'precio' => 16.5],
            ['product_id' => $this->productAzucarKit->id,
            'almacen_id' => $this->almacen->id,
            'precio' => 800],
            ['product_id' => $productHarina->id,
            'almacen_id' => $this->almacen->id,
            'precio' => 12],
            ['product_id' => $productHarinaKit->id,
            'almacen_id' => $this->almacen->id,
            'precio' => 600],
        ]);
        
    }
    function registerArticuloAzucar() {
        $ordenCompra=$this->ordenCompra;
        // Create a new ProductArticuloCompra object
        $product = new ProductArticuloCompra($this->productAzucar->id, 10, 1);
        
        // Create a new TicketCompra object
        $ticketCompra = new TicketCompra($ordenCompra->id);
        
        return $ticketCompra->registerArticulo($product);
    }
    function registerArticuloKit() {
        $ordenCompra=$this->ordenCompra;
        // Create a new ProductArticuloCompra object
        $product = new ProductArticuloCompra($this->productAzucarKit->id, 10, 1);
        // Create a new TicketCompra object
        $ticketCompra = new TicketCompra($ordenCompra->id);
        return $ticketCompra->registerArticulo($product);
    }
    function createProveedor() {
        $newProveedor = new Proveedor();
        $newProveedor->name = 'Proveedor';
        $newProveedor->organization_id = 1;
        $newProveedor->save();
        $this->proveedor=$newProveedor;
    }
    // function guardarVenta() {
    //     $ticketVenta = new TicketVenta($this->ordenCompra->id);
    //     $this->turno->guardarVenta($ticketVenta, 20, false);
    // }
    // function guardarVentaCredito() {
    //     $ticketVenta = new TicketVenta($this->ordenCompra->id);
    //     $this->turno->guardarVenta($ticketVenta, 20, true);
    // }
    // function crearCliente() {
    //     $this->cliente = Cliente::create(['name' => 'cliente']);
    //     $this->cliente->organization_id = $this->organization->id;
    //     $this->cliente->save();
    //     $this->ordenCompra->cliente_id= $this->cliente->refresh()->id;;
    //     $this->ordenCompra->save();
        
    // }
}
