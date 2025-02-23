<?php

namespace Tests\Feature\Factura;

use App\Models\Organization;
use App\Models\PreFactura;
use App\Models\PreFacturaGlobal;
use App\Models\Ventaticket;
use App\MyClasses\Factura\ComprobanteImpuestosTraslado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Factura\Data\GlobalDBData as DBData;
use Tests\TestCase;

class FacturaGlobalTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test ya facturado
    public function test_createFacturaGlobal()
    {
        return;
        $dbData = new DBData;
        $dbData->cargarDatos();
        $ventaticket = $dbData->ventaTicket;
        $response = $this->postJson(
            route('organizacions.timbrarFacturaGlobal', [
                "serie" => "asdf",
                "forma_pago" => "01",
                "uso_cfdi" => "S01",
                "clave_privada_local" => "asdf",
                "ticketIds" => [1, 2],
                "c_periodicidad" => null,
                "year" => 2024,
                "mes" => "08"
            ])
        );
        // $response->assertStatus(500)->assertSee("ha sido facturado");
    }
    public function test_pre_procesar()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $dbData->cargarUpdateDatos();
        // dd(Ventaticket::with('ventaticket_articulos')->get()->toArray());
        $ventaticket = $dbData->ventaTicket;

        $response = $this->postJson(
            route('organizacions.preProcesar', [
                "ticketIds" => [2, 3],
            ])
        );
        // $response->dump();
        $pFacturas = PreFactura::pluck('impuesto_traslado_array');
        $decodedItems = [];
        foreach ($pFacturas as $item) {
            // Decode the JSON string into an associative array
            $decodedArray = json_decode($item, true);
            // Merge the decoded array into the final array
            $decodedItems = array_merge($decodedItems, $decodedArray);
        }
        $organization = Organization::first();
        $impuestos = $organization->getImpuestosObject($decodedItems);
        // dd(PreFactura::all()->toArray());
        // $response->assertStatus(500)->assertSee("ha sido facturado");
    }
}
