<?php

namespace Tests\Feature\Product\Data;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\InventarioBalance;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class RegisterDBData
{
    public $ventaTicket, $turno;
    public Organization $organization ;
    public Product $productAzucar; 
    public Product $productAzucarKit; 
    public User $user;
    public Almacen $almacen;
    public Cliente $cliente;
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
        // $this->ventaTicket = $this->user->createOrdenCompra();
        $this->turno = $this->user->createTurno();
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
    
}
