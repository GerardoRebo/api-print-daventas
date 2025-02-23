<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\InventarioBalance;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\MyClasses\PuntoVenta\CreateVentaTicket;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class DBData
{
    public function cargarDatos()
    {
        $puntoVentaLogic = new CreateVentaTicket;
        $organization= Organization::create([
            'name' => 'mpmujica',
            'estado' => 'Guerrero',
            'ciudad' => 'Iguala',
            'pais' => 'Mexico',
        ]);
        Sanctum::actingAs(
            /** @var User $user */
            $user = User::factory()->create(),
            ['*']
        );
        $this->createProducts();
        $ordenCompra = $user->createOrdenCompra();
        $turno = $user->createTurno();
        $timestamp = date('Y-m-d H:i:s', strtotime("2022-04-10 19:13:42"));
        $ventaticket = $puntoVentaLogic->creaTicket($user);
        $ventaticket->almacen_id = 1;
        $ventaticket->save();
    }
    public function createProducts() {
        $almacen = Almacen::create([
            'organization_id' => 1,
            'name' => 'lamina',

        ]);
        $productAzucar = Product::create([
            'organization_id' => 1,
            'name' => 'azucar 1k',
            'codigo' => 'a1',
            'es_kit' => 0,
            'pcosto' => 10,
        ]);
        $productAzucarKit = Product::create([
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
            'product_id' => $productAzucar->id,
            'almacen_id' => $almacen->id,
            'cantidad_actual' => 50,

        ]);
        $inventarioHarina = InventarioBalance::create([
            'product_id' => $productHarina->id,
            'almacen_id' => $almacen->id,
            'cantidad_actual' => 500,

        ]);
        DB::table('product_components')->insert([
            ['product_id' => $productAzucarKit->id,
            'product_hijo_id'=> $productAzucar->id,
            'cantidad' => 50],
            ['product_id' => $productHarinaKit->id,
            'product_hijo_id'=> $productHarina->id,
            'cantidad' => 50]
        ]);
        DB::table('precios')->insert([
            ['product_id' => $productAzucar->id,
            'almacen_id' => $almacen->id,
            'precio' => 16.5],
            ['product_id' => $productAzucarKit->id,
            'almacen_id' => $almacen->id,
            'precio' => 800],
            ['product_id' => $productHarina->id,
            'almacen_id' => $almacen->id,
            'precio' => 12],
            ['product_id' => $productHarinaKit->id,
            'almacen_id' => $almacen->id,
            'precio' => 600],
        ]);
        
    }

}
