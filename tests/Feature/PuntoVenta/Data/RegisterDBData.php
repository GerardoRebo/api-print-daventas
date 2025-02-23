<?php

namespace Tests\Feature\PuntoVenta\Data;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\InventarioBalance;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\MyClasses\PuntoVenta\TicketVenta;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class RegisterDBData
{
    public $ventaTicket, $ordenCompra, $turno;
    public Organization $organization;
    public Product $productAzucar;
    public Product $productAzucarKit;
    public User $user;
    public Almacen $almacen;
    public Cliente $cliente;
    public function cargarDatos()
    {
        $this->organization = Organization::create([
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
        // $this->ordenCompra = $this->user->createOrdenCompra();
        $this->turno = $this->user->createTurno();
        $this->ventaTicket = $this->user->getVentaticketAlmacenCliente();
        $this->ventaTicket->almacen_id = 1;
        $this->ventaTicket->save();
    }
    public function createProducts()
    {
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
            [
                'product_id' => $this->productAzucarKit->id,
                'product_hijo_id' => $this->productAzucar->id,
                'cantidad' => 50
            ],
            [
                'product_id' => $productHarinaKit->id,
                'product_hijo_id' => $productHarina->id,
                'cantidad' => 50
            ]
        ]);
        DB::table('precios')->insert([
            [
                'product_id' => $this->productAzucar->id,
                'almacen_id' => $this->almacen->id,
                'precio' => 16.5
            ],
            [
                'product_id' => $this->productAzucarKit->id,
                'almacen_id' => $this->almacen->id,
                'precio' => 800
            ],
            [
                'product_id' => $productHarina->id,
                'almacen_id' => $this->almacen->id,
                'precio' => 12
            ],
            [
                'product_id' => $productHarinaKit->id,
                'almacen_id' => $this->almacen->id,
                'precio' => 600
            ],
        ]);
    }
    function guardarVenta()
    {
        $formaPago = [
            "efectivo" => 20,
            "efectivo_ref" => "",
            "tarjeta_debito" => 0,
            "tarjeta_debito_ref" => "",
            "tarjeta_credito" => 0,
            "tarjeta_credito_ref" => "",
            "transferencia" => 0,
            "transferencia_ref" => "",
            "cheque" => 0,
            "cheque_ref" => "",
            "vales_de_despensa" => 0,
            "vales_de_despensa_ref" => "",
            "pago_con" => "20.00"
        ];
        $ticketVenta = new TicketVenta($this->ventaTicket->id);
        $this->turno->guardarVenta($ticketVenta, $formaPago, false);
    }
    function guardarVentaCredito()
    {
        $formaPago = [
            "efectivo" => 20,
            "efectivo_ref" => "",
            "tarjeta_debito" => 0,
            "tarjeta_debito_ref" => "",
            "tarjeta_credito" => 0,
            "tarjeta_credito_ref" => "",
            "transferencia" => 0,
            "transferencia_ref" => "",
            "cheque" => 0,
            "cheque_ref" => "",
            "vales_de_despensa" => 0,
            "vales_de_despensa_ref" => "",
            "pago_con" => "20.00"
        ];
        $ticketVenta = new TicketVenta($this->ventaTicket->id);
        $this->turno->guardarVenta($ticketVenta, $formaPago, true);
    }
    function crearCliente()
    {
        $this->cliente = Cliente::create(['name' => 'cliente']);
        $this->cliente->organization_id = $this->organization->id;
        $this->cliente->save();
        $this->ventaTicket->cliente_id = $this->cliente->refresh()->id;;
        $this->ventaTicket->save();
    }
}
