<?php

namespace Tests\Feature\Factura;

use App\Models\Ventaticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Factura\Data\GuardarDBData as DBData;
use Tests\TestCase;

class FacturaTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    // public function test_200()
    // {
    //     $dbData = new DBData;
    //     $dbData->cargarDatos();
    //     $dbData->cargarUpdateDatos();
    //     $response = $this->postJson(
    //         route('puntoventa.facturar', [
    //             'ticket' => 1,
    //             'forma_pago' => '01',
    //             'uso_cfdi' => 'G03',
    //             'serie' => null,
    //             'clave_privada_local' => '12345678',
    //         ])
    //     );
    //     $response->dump();
    //     $response->assertStatus(200);
    // }
    //test ya facturado
    public function test_ya_facturado()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();
        $ventaticket = $dbData->ventaTicket;
        $ventaticket->facturado_en = now();
        $ventaticket->save();

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

        $response = $this->postJson(
            route('puntoventa.facturar', [
                'ticket' => 1,
                'forma_pago' => $formaPago,
                'uso_cfdi' => 'G03',
                'serie' => null,
                'clave_privada_local' => '12345678',
            ])
        );
        $response->assertStatus(500)->assertSee("ha sido facturado");
    }

    // public function test_values()
    // {
    //     $dbData = new DBData;
    //     $dbData->cargarDatos();

    //     // $response = $this->postJson(
    //     //     route('puntoventa.facturar', [
    //     //         'ticket' => 1,
    //     //         'forma_pago' => '01',
    //     //         'uso_cfdi' => 'G03',
    //     //         'serie' => null,
    //     //         'clave_privada_local' => '',
    //     //     ])
    //     // );
    //     // $ticket->refresh();
    //     // // dd($ticket->toArray());
    //     // $turno= $dbData->turno;
    //     // $turno->refresh();

    //     // $this->assertEquals(1, $ticket->esta_cancelado);
    //     // $this->assertEquals(20, $turno->devoluciones_ventas_efectivo);
    //     // $this->assertEquals(0, $turno->efectivo_al_cierre);
    //     // $this->assertEquals(0, $turno->acumulado_ganancias);
    // }
}
