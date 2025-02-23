<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\OperationalException;
use App\MyClasses\Factura\Comprobante;
use App\MyClasses\Factura\ComprobanteConcepto;
use App\MyClasses\Factura\ComprobanteEmisor;
use App\MyClasses\Factura\ComprobanteReceptor;
use App\MyClasses\Factura\FacturaService;
use App\MyClasses\Factura\Pagos;
use App\MyClasses\Factura\PagosPago;
use App\MyClasses\Factura\PagosPagoDoctoRelacionado;
use App\MyClasses\Factura\PagosPagoDoctoRelacionadoImpuestosDR;
use App\MyClasses\Factura\PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR;
use App\MyClasses\Factura\PagosPagoImpuestosP;
use App\MyClasses\Factura\PagosPagoImpuestosPTrasladoP;
use App\MyClasses\Factura\PagosTotales;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Abono extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['deuda'];

    public function cliente()
    {

        return $this->belongsTo('App\Models\Cliente');
    }
    public function deuda()
    {
        return $this->belongsTo(Deuda::class);
    }

    public function user()
    {

        return $this->belongsTo('App\Models\User');
    }

    public function turno()
    {

        return $this->belongsTo('App\Models\Turno');
    }

    //relacion uno a muchos

    public function comisiones_tarjetas()
    {
        return $this->hasMany('App\Models\ComisionesTarjetas');
    }

    public function pagos_mixtos()
    {
        return $this->hasMany('App\Models\PagosMixto');
    }

    public function abono_ventas()
    {
        return $this->hasMany('App\Models\AbonoVenta');
    }

    public function abono_tickets()
    {
        return $this->hasMany('App\Models\AbonoTicket');
    }
    public function abono_articulos()
    {
        return $this->hasMany('App\Models\AbonoArticulo');
    }
    public function folios_utilizados(): MorphMany
    {
        return $this->morphMany(FoliosUtilizado::class, 'facturable');
    }
    function facturar($formaPago, $user, $cantidad)
    {
        // return $this->latestAbono;
        $serie = 'P';
        $oComprobante = $this->initializeComprobante($serie);

        //emisor
        $oEmisor = $this->createEmisor();
        //receptor
        $oReceptor = $this->createReceptor();
        //conceptos
        $lstConceptos = $this->createConceptos();

        $oComprobante->Emisor = $oEmisor;
        $oComprobante->Receptor = $oReceptor;
        $oComprobante->Conceptos = $lstConceptos;

        $MiJson = "";
        $MiJson = json_encode($oComprobante);
        $jsonPath = 'factura_json/' . $this->id;
        Storage::disk('local')->put($jsonPath, $MiJson);

        $oPagos = $this->createPagos($formaPago, $cantidad);

        $pagoJson = "";
        $pagoJson = json_encode($oPagos);
        $jsonPathPago = 'factura_json_pago/' . $this->id;
        Storage::disk('local')->put($jsonPathPago, $pagoJson);

        $this->callServie($jsonPath, $jsonPathPago);
        return;

        $pdfFacturaPath = "pdf_factura_pagos/$user->organization_id/" . $this->deuda->ventaticket_id;
        /* create pdf */
        $fService = new FacturaService;
        $fService->storePdf('facturaTmp/XmlDesdePhpTimbrado.xml', $user, $pdfFacturaPath);
        $this->pdf_factura_path = $pdfFacturaPath . ".pdf";
        $this->save();
    }
    function createPagos($formaPago, $cantidad)
    {
        $baseIva16 = round($cantidad / (1 + .16));
        $impuestoIva16 = round($cantidad - $baseIva16);
        $oPagos = new Pagos();
        $lstPagos = [];

        $oPagosTotales = new PagosTotales;
        $oPagosTotales->TotalTrasladosBaseIVA16 = $baseIva16;
        $oPagosTotales->TotalTrasladosImpuestoIVA16 = $impuestoIva16;
        $oPagosTotales->MontoTotalPagos = $cantidad;

        $oPagos->Totales = $oPagosTotales;

        $oPago = new PagosPago();
        $oPago->MonedaP = 'MXN';
        $pagado_en = Carbon::parse(getMysqlTimestamp());
        $oPago->FechaPago = $pagado_en->format("Y-m-d\TH:i:s");
        $oPago->FormaDePagoP = $formaPago;
        $oPago->Monto = $cantidad;
        $oPago->TipoCambioP = '1';

        $lstDoctoRelacionado = [];
        $oDoctoRelacionado = new PagosPagoDoctoRelacionado;
        $oDoctoRelacionado->IdDocumento = "bfc36522-4b8e-45c4-8f14-d11b289f9eb7";
        $oDoctoRelacionado->MonedaDR = "MXN";
        $oDoctoRelacionado->NumParcialidad = "2";
        $oDoctoRelacionado->ImpSaldoAnt = $cantidad;
        $oDoctoRelacionado->ImpPagado = $cantidad;
        $oDoctoRelacionado->ImpSaldoInsoluto = "0.00";
        $oDoctoRelacionado->ObjetoImpDR = "02";
        $oDoctoRelacionado->EquivalenciaDR = "1";
        $oDoctoRelacionado->ObjetoImpDR = "02";

        // Create the "TrasladosDR" object and set its properties
        $oTrasladosDR = new PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR();
        $oTrasladosDR->BaseDR = $baseIva16;
        $oTrasladosDR->ImpuestoDR = "002";
        $oTrasladosDR->TipoFactorDR = "Tasa";
        $oTrasladosDR->TasaOCuotaDR = "0.160000";
        $oTrasladosDR->ImporteDR = $impuestoIva16;

        // Create the "ImpuestosDR" object and add the "TrasladosDR" object to it
        $oImpuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();
        $oImpuestosDR->TrasladosDR = [$oTrasladosDR]; // Assign as an array

        // Add the "ImpuestosDR" object to "ImpuestosDR" array in "DoctoRelacionado"
        $oDoctoRelacionado->ImpuestosDR = $oImpuestosDR; // Assign as an array of objects


        $lstDoctoRelacionado[] = $oDoctoRelacionado;
        $oPago->DoctoRelacionado = $lstDoctoRelacionado;

        $oImpuestoP = new PagosPagoImpuestosP;
        $oTrasladoP = new PagosPagoImpuestosPTrasladoP;

        $oTrasladoP->BaseP = $baseIva16;
        $oTrasladoP->ImpuestoP = "002";
        $oTrasladoP->TipoFactorP = "Tasa";
        $oTrasladoP->TasaOCuotaP = "0.160000";
        $oTrasladoP->ImporteP = $impuestoIva16;
        $oImpuestoP->TrasladosP = [$oTrasladoP];

        $oPago->ImpuestosP = $oImpuestoP;
        $lstPagos[] = $oPago;
        $oPagos->Pago = $lstPagos;
        return $oPagos;
    }
    function callServie($jsonPath, $jsonPathPago)
    {
        $facturaData = $this->getFacturaData();
        $clavePrivadaSat = Crypt::decryptString($facturaData['clave_privada_sat']);
        $user = $this->deuda->ventaticket->user;
        $storagePath = Storage::disk('local')->path('');
        if (app()->isProduction()) {
            $fileContent = Storage::disk('s3')->get($facturaData['cer_path']);
            Storage::disk('local')->put($facturaData['cer_path'], $fileContent);
            $fileContent = Storage::disk('s3')->get($facturaData['key_path']);
            Storage::disk('local')->put($facturaData['key_path'], $fileContent);
        }
        //path
        //json path
        //clavePrivada
        //cerPath
        //keyPath
        $command = [
            'dotnet',
            'facturacion.dll',
            $storagePath,
            $jsonPath,
            $clavePrivadaSat,
            $facturaData['cer_path'],
            $facturaData['key_path'],
            app()->isLocal() ? 'true' : 'false',
            $jsonPathPago
        ];
        $command = implode(' ', $command);
        $result = Process::path(base_path() . '/net7.0')
            ->run($command);

        logger($result->output());
        if ($result->failed()) {
            logger($result->errorOutput());
            logger($result->output());
            throw new OperationalException($result->output(), 1);
        }
        $idForPath = $this->id;
        $xmlFacturaPath = "xml_factura_pagos/$user->organization_id/$idForPath";
        if (app()->isProduction()) {
            try {
                Storage::disk('local')->delete($facturaData['cer_path']);
                Storage::disk('local')->delete($facturaData['key_path']);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        try {
            if (Storage::disk('local')->exists('facturaTmp/XmlDesdePhpTimbrado.xml')) {
                Storage::put($xmlFacturaPath . ".xml", Storage::disk('local')->get('facturaTmp/XmlDesdePhpTimbrado.xml'));
                $this->xml_factura_path = $xmlFacturaPath . ".xml";
                $this->facturado_en = getMysqlTimestamp($user->configuration?->time_zone);
                logger('hjere');
                logger($this->xml_factura_path);
                $this->save();
                $this->consumeTimbre(1);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    function consumeTimbre($cantidad)
    {
        $user = $this->deuda->ventaticket->user;
        $saldo = $user->organization->latestFoliosUtilizado;
        $saldoScalar = $saldo?->saldo ?? 0;
        if (!$saldo) {
            throw new OperationalException("No cuentas con suficientes timbres fiscales, , contacta con la administración para solicitar timbres fiscales", 1);
        }
        $this->folios_utilizados()->create([
            'organization_id' => $this->organization_id,
            'cantidad' => $cantidad,
            'antes' => $saldoScalar,
            'despues' => $saldoScalar - 1,
            'saldo' => $saldoScalar - 1,
        ]);
    }

    function createConceptos()
    {
        $lstConceptos = [];
        $oConcepto = new ComprobanteConcepto();
        // $oConcepto->Importe = $articulo->importe;
        $oConcepto->ClaveProdServ = '84111506';
        $oConcepto->ClaveUnidad =  'ACT';
        $oConcepto->Cantidad = 1;
        $oConcepto->Descripcion = 'Pago';
        $oConcepto->ObjetoImp = "01";
        $lstConceptos[] = $oConcepto;
        return $lstConceptos;
    }
    function initializeComprobante($serie)
    {
        $facturaData = $this->getFacturaData();
        $comprobante = new Comprobante();
        $comprobante->Version = "4.0";
        $comprobante->Moneda = "XXX";
        $comprobante->TipoDeComprobante = "P";
        // $comprobante->MetodoPago = $metodoPago;

        if ($serie) {
            $comprobante->Serie = $serie;
        }
        $comprobante->Folio = $this->deuda->ventaticket->consecutivo ?? 1;

        $pagado_en = Carbon::parse(getMysqlTimestamp());
        $comprobante->Fecha = $pagado_en->format("Y-m-d\TH:i:s");

        // $comprobante->FormaPago = $formaPago;
        $comprobante->SubTotal = 0;
        // $comprobante->Descuento = $this->descuento;
        $comprobante->Total = 0;
        $comprobante->LugarExpedicion = $facturaData['codigo_postal'];
        return $comprobante;
    }
    function getFacturaData()
    {
        $facturaHelper = new FacturaService;
        $facturaData = $facturaHelper->getData($this->deuda->ventaticket);
        return $facturaData;
    }
    function createEmisor(): ComprobanteEmisor
    {
        $facturaData = $this->getFacturaData();
        $oEmisor = new ComprobanteEmisor();
        $oEmisor->Rfc = strtoupper($facturaData['rfc']);
        $oEmisor->Nombre = strtoupper($facturaData['razon_social']);
        $oEmisor->RegimenFiscal =  $facturaData['regimen_fiscal'];
        return $oEmisor;
    }
    function createReceptor(): ComprobanteReceptor
    {
        $oReceptor = new ComprobanteReceptor();
        $oReceptor->Nombre = strtoupper($this->deuda->ventaticket->cliente->razon_social);
        $oReceptor->Rfc = strtoupper($this->deuda->ventaticket->cliente->rfc);
        $oReceptor->DomicilioFiscalReceptor =  $this->deuda->ventaticket->cliente->codigo_postal;
        $oReceptor->RegimenFiscalReceptor =  $this->deuda->ventaticket->cliente->regimen_fiscal;
        $oReceptor->UsoCFDI = 'CP01';
        return $oReceptor;
    }
}
