<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\MyClasses\Factura\Comprobante;
use App\MyClasses\Factura\ComprobanteConcepto;
use App\MyClasses\Factura\ComprobanteConceptoImpuestos;
use App\MyClasses\Factura\ComprobanteConceptoImpuestosRetencion;
use App\MyClasses\Factura\ComprobanteConceptoImpuestosTraslado;
use App\MyClasses\Factura\ComprobanteEmisor;
use App\MyClasses\Factura\ComprobanteImpuestosRetencion;
use App\MyClasses\Factura\ComprobanteImpuestosTraslado;
use App\MyClasses\Factura\ComprobanteReceptor;
use App\MyClasses\Factura\FacturaService;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class PreFactura extends Model
{
    use HasFactory;

    public Comprobante $oComprobante;
    public $facturaData;
    protected $guarded = [];
    protected $casts = [
        'impuesto_traslado_array' => 'array',
    ];

    function ventaticket()
    {
        return $this->belongsTo('App\Models\Ventaticket');
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function articulos()
    {
        return $this->hasMany('App\Models\PrefacturaArticulo');
    }
    public function taxes()
    {
        return $this->hasMany('App\Models\PreFacturaArticuloTax');
    }
    function setTotal()
    {
        $this->total = (float)$this->subtotal -  (float)$this->descuento +  (float)$this->impuesto_traslado  -  (float)$this->impuesto_retenido;
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
    function initializeComprobante($serie, $formaPago, $metodoPago)
    {
        $facturaData = $this->getFacturaData();
        $this->oComprobante = new Comprobante();
        $this->oComprobante->Version = "4.0";
        $this->oComprobante->Moneda = "MXN";
        $this->oComprobante->TipoDeComprobante = "I";
        $this->oComprobante->MetodoPago = $metodoPago;

        if ($serie) {
            $this->oComprobante->Serie = $serie;
        }
        $this->oComprobante->Folio = $this->ventaticket->consecutivo ?? 1;

        $pagado_en = Carbon::parse(getMysqlTimestamp());
        $this->oComprobante->Fecha = $pagado_en->format("Y-m-d\TH:i:s");

        $this->oComprobante->FormaPago = $formaPago;
        $this->oComprobante->SubTotal = $this->subtotal;
        $this->oComprobante->Descuento = $this->descuento;
        $this->oComprobante->Total = $this->total;
        $this->oComprobante->LugarExpedicion = $facturaData['codigo_postal'];
        return $this->oComprobante;
    }
    function getConceptoImpuestos()
    {
        $lstImpuestosRetenidos = [];
        $lstImpuestosTrasladados = [];
        foreach ($this->impuesto_traslado_array as $taxArticulo) {
            if ($taxArticulo['tipo'] == 'retencion') {
                $oImpuesto = new ComprobanteConceptoImpuestosRetencion();
            } else {
                $oImpuesto = new ComprobanteConceptoImpuestosTraslado();
            }
            $oImpuesto->Base = $taxArticulo['base'];
            $oImpuesto->TipoFactor =  $taxArticulo['tipo_factor'];
            $oImpuesto->Impuesto =  $taxArticulo['c_impuesto'];
            $oImpuesto->Importe = $taxArticulo['importe'];
            $oImpuesto->TasaOCuota = $taxArticulo['tasa_o_cuota'];
            if ($taxArticulo['tipo'] == 'retencion') {
                $lstImpuestosRetenidos[] = $oImpuesto;
            } else {
                $lstImpuestosTrasladados[] = $oImpuesto;
            }
        }
        return [
            "traslados" => $lstImpuestosTrasladados,
        ];
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
        $oReceptor = new ComprobanteReceptor();
        $oReceptor->Nombre = strtoupper($this->ventaticket->cliente->razon_social);
        $oReceptor->Rfc = strtoupper($this->ventaticket->cliente->rfc);
        $oReceptor->DomicilioFiscalReceptor =  $this->ventaticket->cliente->codigo_postal;
        $oReceptor->RegimenFiscalReceptor =  $this->ventaticket->cliente->regimen_fiscal;
        $oReceptor->UsoCFDI = $usoCfdi;
        return $oReceptor;
    }
    function createConceptos()
    {
        $lstConceptos = [];
        foreach ($this->articulos as $articulo) {
            $oConcepto = new ComprobanteConcepto();
            $oConcepto->Importe = $articulo->importe;
            $oConcepto->ClaveProdServ = $articulo->product->c_claveProdServ;
            $oConcepto->ClaveUnidad =  $articulo->product->c_ClaveUnidad;
            $oConcepto->Cantidad = $articulo->cantidad;
            $oConcepto->Descripcion = $articulo->product->name;
            $oConcepto->ValorUnitario = $articulo->precio;
            $oConcepto->Descuento = $articulo->descuento;
            $oConcepto->ObjetoImp = "02";

            $articuloImpuestos = $articulo->getConceptoImpuestos();

            $oConcepto->Impuestos = new ComprobanteConceptoImpuestos();
            if ($articuloImpuestos["retenidos"]) {
                $oConcepto->Impuestos->Retenciones = $articuloImpuestos["retenidos"];
            }
            if ($articuloImpuestos["traslados"]) {
                $oConcepto->Impuestos->Traslados = $articuloImpuestos["traslados"];
            }
            $lstConceptos[] = $oConcepto;
        }
        return $lstConceptos;
    }
    function callServie($jsonPath)
    {
        $facturaData = $this->getFacturaData();
        $clavePrivadaSat = Crypt::decryptString($facturaData['clave_privada_sat']);
        $user = $this->ventaticket->user;
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
        $idForPath = $this->ventaticket->id;
        $xmlFacturaPath = "xml_factura/$user->organization_id/$idForPath";
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
                $this->ventaticket->xml_factura_path = $xmlFacturaPath . ".xml";
                $this->ventaticket->facturado_en = getMysqlTimestamp($user->configuration?->time_zone);
                $this->facturado_en = getMysqlTimestamp($user->configuration?->time_zone);
                $this->save();
                $this->consumeTimbre(1);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        $this->ventaticket->save();
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
    function getFacturaData()
    {
        if (!$this->facturaData) {
            $facturaHelper = new FacturaService;
            $this->facturaData = $facturaHelper->getData($this->ventaticket);
        }
        return $this->facturaData;
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
                        if ($tipoK == 'retenido') {
                            $oI = new ComprobanteImpuestosRetencion();
                            $oI->Impuesto = $c_impuestoK;
                            $oI->Importe = round($importe, 2);
                            $lstImpuestosRetenidos[] = $oI;
                        } else {
                            $oI = new ComprobanteImpuestosTraslado();
                            $oI->Impuesto = $c_impuestoK;
                            $oI->TipoFactor = $tipo_factorK;
                            $oI->TasaOCuota = $tasa_o_cuotaK;
                            $oI->Importe = round($importe, 2);
                            $oI->Base = round($base, 2);
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
        $grouped = [];

        foreach ($this->taxes as $impuesto) {
            $tipo = $impuesto->tipo;
            $tipo_factor = $impuesto->tipo_factor;
            $c_impuesto = $impuesto->c_impuesto;
            $tasa_o_cuota = $impuesto->tasa_o_cuota;

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

            $grouped[$tipo][$tipo_factor][$c_impuesto][$tasa_o_cuota][] = $impuesto->toArray();
        }

        return $grouped;
    }
    function getSumatoriaImpuestos($type)
    {
        return $this->taxes->where('tipo', $type)->sum('importe');
    }
}
