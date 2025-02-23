<?php

namespace Tests\Feature\Factura\Data;

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
    public $ventaTicket, $ventaTicketTwo, $ordenCompra, $turno, $tax;
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
        $this->organization->updateClavePrivadaLocal('12345678');
        $this->organization->refresh();
        $this->organization->updateClavePrivadaSat('12345678a');
        $this->organization->updateFacturacionInfo('GERARDO REBOLLEDO MONTIEL', '612', 'REMG930325975', '40068', 'mock');
        $this->organization->updateKey('certificados/FRFdbXq7OrdBNP4b9VDmqxjvaK2TmEc2YOKo1RpO.bin', 'name');
        $this->organization->updateCer('certificados/NuSf9WkskFQh3oYdvjrFTNQg3vyPgXfrk0iCuAwH.bin', 'name');
        $this->createTax();
        $this->createProducts();
        $this->crearCliente();
        $this->turno = $this->user->createTurno();

        $this->createVenta();
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
            'c_ClaveUnidad' => '3H',
            'c_claveProdServ' => '50161509',
        ]);
        $this->productAzucarKit = Product::create([
            'organization_id' => 1,
            'name' => 'Bulto azucar 50k',
            'codigo' => 'a50',
            'es_kit' => 1,
            'pcosto' => 500,
            'c_ClaveUnidad' => '3H',
            'c_claveProdServ' => '50161509',

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
    function createTax()
    {
        $this->tax = $this->organization->createTax('002', '1', 16, 'traslado');
    }
    function createVenta()
    {
        $this->ventaTicket = $this->user->getVentaticketAlmacenCliente();
        $this->ventaTicket->almacen_id = 1;
        $this->ventaTicket->cliente_id = $this->cliente->refresh()->id;;
        $this->ventaTicket->save();
        $productAzucar = $this->productAzucar;
        $productAzucarKit = $this->productAzucarKit;
        $productAzucar->taxes()->attach([$this->tax->id]);
        $productAzucarKit->taxes()->attach([$this->tax->id]);
        $this->guardarVenta();
    }
    function createVentaTwo()
    {
        $this->ventaTicketTwo = $this->user->getVentaticketAlmacenCliente();
        $this->ventaTicketTwo->almacen_id = 1;
        $this->ventaTicketTwo->cliente_id = $this->cliente->refresh()->id;;
        $this->ventaTicketTwo->save();
        $this->guardarVentaTwo();
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
    function guardarVentaTwo()
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
        $ticketVenta = new TicketVenta($this->ventaTicketTwo->id);
        $this->turno->guardarVenta($ticketVenta, $formaPago, false);
    }
    function crearCliente()
    {
        $this->cliente = Cliente::create(['name' => 'cliente']);
        $this->cliente->organization_id = $this->organization->id;
        $this->cliente->razon_social = 'GERARDO REBOLLEDO MONTIEL';
        $this->cliente->rfc = 'REMG930325975';
        $this->cliente->codigo_postal = '40068';
        $this->cliente->regimen_fiscal = '612';
        $this->cliente->save();
    }
}
