<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\MyClasses\Factura\Comprobante;
use App\MyClasses\Factura\ComprobanteConcepto;
use App\MyClasses\Factura\ComprobanteConceptoImpuestos;
use App\MyClasses\Factura\ComprobanteEmisor;
use App\MyClasses\Factura\ComprobanteImpuestosRetencion;
use App\MyClasses\Factura\ComprobanteImpuestosTraslado;
use App\MyClasses\Factura\ComprobanteReceptor;
use App\MyClasses\Factura\FacturaService;
use App\MyClasses\Factura\InformacionGlobal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class PreFacturaGlobal extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'ticket_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function articulos()
    {
        return $this->hasMany(PreFactura::class);
    }
    function setTotal()
    {
        $this->total = $this->subtotal - $this->descuento + $this->impuesto;
    }
    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    protected function descuento(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    protected function impuesto(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn($value) => round($value, 2),
        );
    }
    public function folios_utilizados(): MorphMany
    {
        return $this->morphMany(FoliosUtilizado::class, 'facturable');
    }
    function initializeComprobante($serie, $formaPago, $year, $mes)
    {
        $facturaData = $this->getFacturaData();
        $oCombrobante = new Comprobante();
        $oCombrobante->Version = "4.0";
        $oCombrobante->Moneda = "MXN";
        $oCombrobante->TipoDeComprobante = "I";
        $oCombrobante->MetodoPago = "PUE";
        // $oCombrobante->Exportacion = "01";
        $informacionGlobal = new InformacionGlobal;
        $informacionGlobal->Año = $year;
        $informacionGlobal->Meses = $mes;
        $informacionGlobal->Periodicidad = $this->c_periodicidad;
        $oCombrobante->InformacionGlobal = $informacionGlobal;
        if ($serie) {
            $oCombrobante->Serie = 'G' . $serie;
        }
        $oCombrobante->Folio = $this->id;
        $pagado_en = Carbon::parse(getMysqlTimestamp());
        $oCombrobante->Fecha = $pagado_en->format("Y-m-d\TH:i:s");
        $oCombrobante->FormaPago = $formaPago;
        $oCombrobante->SubTotal = $this->subtotal;
        $oCombrobante->Descuento = $this->descuento;
        $oCombrobante->Total = $this->total;
        $oCombrobante->LugarExpedicion = $facturaData['codigo_postal'];
        return $oCombrobante;
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
    function createReceptor($usoCfdi): ComprobanteReceptor
    {
        $facturaData = $this->getFacturaData();
        $oReceptor = new ComprobanteReceptor();
        $oReceptor->Rfc = 'XAXX010101000';
        $oReceptor->Nombre = 'PUBLICO EN GENERAL';
        $oReceptor->DomicilioFiscalReceptor =  $facturaData['codigo_postal'];
        $oReceptor->RegimenFiscalReceptor =  "616";
        $oReceptor->UsoCFDI = $usoCfdi;
        return $oReceptor;
    }
    function createConceptos()
    {
        $lstConceptos = [];
        foreach ($this->articulos as $articulo) {
            $oConcepto = new ComprobanteConcepto();
            $oConcepto->ClaveProdServ = '01010101';
            $oConcepto->NoIdentificacion = $articulo->ventaticket_id;
            $oConcepto->Cantidad = 1;
            $oConcepto->ClaveUnidad =  'ACT';
            $oConcepto->Descripcion = 'Venta';
            $oConcepto->ValorUnitario = $articulo->subtotal;
            $oConcepto->Importe = $articulo->subtotal;
            $oConcepto->Descuento = $articulo->descuento;
            $oConcepto->ObjetoImp = "02";

            $articuloImpuestos = $articulo->getConceptoImpuestos();

            if ($articuloImpuestos["traslados"]) {
                $oConcepto->Impuestos = new ComprobanteConceptoImpuestos();
                $oConcepto->Impuestos->Traslados = $articuloImpuestos["traslados"];
            }
            $lstConceptos[] = $oConcepto;
        }
        return $lstConceptos;
    }
    function updateFacturadoEn()
    {
        $prefacturasIds = $this->articulos->pluck('id');
        DB::table('pre_facturas')
            ->whereIn('id', $prefacturasIds)  // Updating specific tickets
            ->update(['facturado_en' => getMysqlTimestamp($this->user->configuration?->time_zone),]);
    }
    function consumeTimbre($cantidad)
    {
        $saldo = $this->organization->latestFoliosUtilizado;
        $saldoScalar = $saldo?->saldo ?? 0;
        if (!$saldoScalar) {
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
    function callServie($jsonPath)
    {
        $facturaData = $this->getFacturaData();
        $clavePrivadaSat = Crypt::decryptString($facturaData['clave_privada_sat']);
        $user = $this->user;
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
            app()->isLocal() ? 'true' : 'false'
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
        $xmlFacturaPath = "xml_factura_global/$user->organization_id/$idForPath";
        if (app()->isProduction()) {
            try {
                Storage::disk('local')->delete($facturaData['cer_path']);
                Storage::disk('local')->delete($facturaData['key_path']);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        // if (app()->isProduction()) {
        try {
            if (Storage::disk('local')->exists('facturaTmp/XmlDesdePhpTimbrado.xml')) {
                Storage::put($xmlFacturaPath . ".xml", Storage::disk('local')->get('facturaTmp/XmlDesdePhpTimbrado.xml'));
                $this->xml_factura_path = $xmlFacturaPath . ".xml";
                $this->facturado_en = getMysqlTimestamp($user->configuration?->time_zone);
                $this->updateFacturadoEn();
                $this->consumeTimbre(1);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        $this->save();
    }
    function getFacturaData()
    {
        $facturaHelper = new FacturaService;
        return $facturaHelper->getGlobalData($this->organization);
    }
    function getImpuestos()
    {
        $lstImpuestosRetenidos = [];
        $lstImpuestosTrasladados = [];
        $impuestos = $this->getGroupedImpuestos();
        foreach ($impuestos as $tipoK => $tipo) {
            foreach ($tipo as $tipo_factorK => $tipo_factor) {
                foreach ($tipo_factor as $c_impuestoK => $c_impuesto) {
                    foreach ($c_impuesto as $tasa_o_cuotaK => $items) {
                        $importe = 0;
                        $base = 0;
                        foreach ($items as $item) {
                            $base += (float)$item['base'];
                            $importe += (float)$item['importe'];
                        }
                        if ($tipoK == 'retencion') {
                            $oI = new ComprobanteImpuestosRetencion();
                            $oI->Impuesto = $c_impuestoK;
                            $oI->Importe = $importe;
                            $lstImpuestosRetenidos[] = $oI;
                        } else {
                            $oI = new ComprobanteImpuestosTraslado();
                            $oI->Impuesto = $c_impuestoK;
                            $oI->TipoFactor = $tipo_factorK;
                            $oI->TasaOCuota = $tasa_o_cuotaK;
                            $oI->Importe = $importe;
                            $oI->Base = $base;
                            $lstImpuestosTrasladados[] = $oI;
                        }
                    }
                }
            }
        }
        return [
            "retenidos" => $lstImpuestosRetenidos,
            "trasladados" => $lstImpuestosTrasladados,
        ];
    }
    private function getGroupedImpuestos()
    {
        $pFacturas = $this->articulos->pluck('impuesto_traslado_array');
        $decodedItems = [];
        foreach ($pFacturas as $item) {
            // Decode the JSON string into an associative array
            // $decodedArray = json_decode($item, true);
            // Merge the decoded array into the final array
            // $decodedItems = array_merge($decodedItems, $decodedArray);
            $decodedItems = array_merge($decodedItems, $item);
        }
        $grouped = [];

        foreach ($decodedItems as $impuesto) {
            $tipo = $impuesto['tipo'];
            $tipo_factor = $impuesto['tipo_factor'];
            $c_impuesto = $impuesto['c_impuesto'];
            $tasa_o_cuota = $impuesto['tasa_o_cuota'];

            if (!isset($grouped[$tipo])) {
                $grouped[$tipo] = [];
            }

            if (!isset($grouped[$tipo][$tipo_factor])) {
                $grouped[$tipo][$tipo_factor] = [];
            }

            if (!isset($grouped[$tipo][$tipo_factor][$c_impuesto])) {
                $grouped[$tipo][$tipo_factor][$c_impuesto] = [];
            }

            if (!isset($grouped[$tipo][$tipo_factor][$c_impuesto][$tasa_o_cuota])) {
                $grouped[$tipo][$tipo_factor][$c_impuesto][$tasa_o_cuota] = [];
            }

            $grouped[$tipo][$tipo_factor][$c_impuesto][$tasa_o_cuota][] = $impuesto;
        }

        return $grouped;
    }
}
