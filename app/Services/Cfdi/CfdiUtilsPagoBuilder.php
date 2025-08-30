<?php

namespace App\Services\Cfdi;

use App\Models\Abono;
use CfdiUtils\Certificado\Certificado;
use Carbon\Carbon;
use CfdiUtils\CfdiCreator40;
use CfdiUtils\Elements\Pagos20\Pagos;
use CfdiUtils\SumasPagos20\PagosWriter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Credential;

class CfdiUtilsPagoBuilder
{
    protected string $certPath;
    protected string $keyPemPath;
    protected string $keyPass;
    protected $facturaData;
    protected $comprobante;
    protected $ventaticket;

    public function __construct(protected Abono $abonoModel)
    {
        $this->facturaData = $this->abonoModel->getFacturaData();
        $this->ventaticket = $abonoModel->deuda->ventaticket;
        $storagePath = Storage::disk('local')->path('');
        $this->certPath = $storagePath . $this->facturaData['cer_path'];
        $this->keyPemPath = $storagePath . $this->facturaData['key_path'];
        $this->keyPass = Crypt::decryptString($this->facturaData['clave_privada_sat']);
    }

    public function createFromAbono($serie, $formaPago, $cantidad): string
    {
        if (app()->isProduction()) {
            $fileContent = Storage::disk('s3')->get($this->facturaData['cer_path']);
            Storage::disk('local')->put($this->facturaData['cer_path'], $fileContent);
            $fileContent = Storage::disk('s3')->get($this->facturaData['key_path']);
            Storage::disk('local')->put($this->facturaData['key_path'], $fileContent);
        }

        $cert = new Certificado($this->certPath);
        // Usar Credential para leer .cer y .key
        $csd = Credential::openFiles($this->certPath, $this->keyPemPath, $this->keyPass);

        $comprobanteAtributos = $this->getComprobanteAtributos($serie);

        $creator = new CfdiCreator40($comprobanteAtributos, $cert);

        $creator->putCertificado(
            new Certificado($csd->certificate()->pem()),
            true // auto-asigna RFC y nombre del emisor
        );
        $this->comprobante = $creator->comprobante();

        $this->addEmisor();
        // Receptor
        $this->addReceptor();

        $this->addConcepto();

        // Crear nodo Pagos
        $pagos = new Pagos();
        $pagos = $this->addPagos($pagos, $cantidad, $formaPago);

        PagosWriter::calculateAndPut($pagos);

        $this->comprobante->addComplemento($pagos);

        // Sello digital usando la llave privada del CSD
        $creator->addSello($csd->privateKey()->pem(), $csd->privateKey()->passPhrase());


        // Limpieza de namespaces
        $creator->moveSatDefinitionsToComprobante();

        // Validación
        $asserts = $creator->validate();
        if (app()->isLocal()) {
            // if ($asserts->hasErrors()) {
            //     throw new \Exception('Errores en validación CFDI: ' . json_encode($asserts->errors()));
            // }
        } else {
            if ($asserts->hasErrors()) {
                throw new \Exception('Errores en validación CFDI: ' . json_encode($asserts->errors()));
            }
        }
        // $this->abonoModel->save();

        return $creator->asXml();
    }
    function getComprobanteAtributos($serie)
    {
        $pagado_en = Carbon::parse(getMysqlTimestamp());
        return [
            'Serie' => 'P' . $serie,
            'Folio' => $this->abonoModel->deuda->ventaticket->consecutivo ?? 1,
            'Fecha' => $pagado_en->format("Y-m-d\TH:i:s"),
            'Moneda' => 'XXX',
            'TipoDeComprobante' => 'P',
            'Exportacion' => '01',
            'LugarExpedicion' => $this->facturaData['codigo_postal'],
            'SubTotal' => '0',
            'Total' => '0',
        ];
    }
    function addEmisor()
    {
        // Emisor (ya saca RFC y Nombre del certificado)
        $emisor = [
            'RegimenFiscal' => $this->facturaData['regimen_fiscal'],
        ];

        if (app()->isLocal()) {
            $emisor += [
                'Rfc' => strtoupper($this->facturaData['rfc']),
                'Nombre' => strtoupper($this->facturaData['razon_social']),
            ];
        }

        $this->comprobante->addEmisor($emisor);
    }
    function addReceptor()
    {
        $cliente = $this->abonoModel->deuda->ventaticket->cliente;
        $this->comprobante->addReceptor([
            'Rfc' => strtoupper($cliente->rfc),
            'Nombre' => strtoupper($cliente->razon_social),
            'DomicilioFiscalReceptor' => $cliente->codigo_postal,
            'RegimenFiscalReceptor' => $cliente->regimen_fiscal,
            'UsoCFDI' => 'CP01'
        ]);
    }
    function addConcepto()
    {
        $this->comprobante->addConcepto([
            'ClaveProdServ' => '84111506',
            'Cantidad' => '1',
            'ClaveUnidad' => 'ACT',
            'Descripcion' => 'Pago',
            'ValorUnitario' => '0',
            'Importe' => '0',
            'ObjetoImp' => '01',
        ]);
    }
    function addPagos($pagos, $cantidad, $formaPago)
    {

        // 5.1 Crear nodo pago
        $pago = $pagos->addPago([
            'Monto' => $cantidad,
            'MonedaP' => 'MXN',
            'TipoCambioP' => '1',
            'FechaPago' => now()->format('Y-m-d\TH:i:s'),
            'FormaDePagoP' => $formaPago, // Transferencia
        ]);
        $impuestoTraslado = $this->ventaticket->pre_factura->impuesto_traslado_array ?? [];
        $impuestoRetenido = $this->ventaticket->pre_factura->impuesto_retenido_array ?? [];
        $objetoImpuesto = '01';
        if (count($impuestoTraslado) || count($impuestoRetenido)) {
            $objetoImpuesto = '02';
        }

        // 5.2 Relacionar el pago con una factura
        $doctoRelacionado = $pago->addDoctoRelacionado([
            'ImpPagado' => $cantidad,
            'EquivalenciaDR' => "1",
            'IdDocumento' => $this->ventaticket->cfdi_uuid,
            'MonedaDR' => 'MXN',
            'NumParcialidad' => $this->abonoModel->deuda->abonos->count() + 1,
            //I want to get the last abono minus 1
            'ImpSaldoAnt' => $this->abonoModel->deuda->abonos()->orderBy('id', 'desc')->skip(1)->first()?->saldo ?? $this->abonoModel->deuda->deuda,
            'ImpSaldoInsoluto' =>  $this->abonoModel->deuda->saldo,
            'ObjetoImpDR' => $objetoImpuesto
        ]);
        if ($objetoImpuesto == '01') {
            return $pagos;
        }
        $proporcionPagada = $cantidad / $this->ventaticket->total;

        // new DoctoRelacionado
        $impuestosDR = $doctoRelacionado->getImpuestosDR();
        if (count($impuestoTraslado) > 0) {
            $trasladosDR = $impuestosDR->getTrasladosDR();
            foreach ($impuestoTraslado as $key => $value) {
                $baseDR = round($value['Base'] * $proporcionPagada, 2);
                $importeDR = round($value['Importe'] * $proporcionPagada, 2);
                $trasladosDR->addTrasladoDR([
                    'BaseDR' => $baseDR,
                    'ImpuestoDR' => $value['Impuesto'],
                    'TipoFactorDR' => $value['TipoFactor'],
                    'TasaOCuotaDR' => $value['TasaOCuota'],
                    'ImporteDR' => $importeDR,
                ]);
            }
        }
        if (count($impuestoRetenido) > 0) {
            //I need to get retentions again because I need the detailed ones
            $retenciones = $this->ventaticket->articulo_taxes()->where('tipo', 'retenido')->get();
            $cfdiCommon = new CfdiCommon();
            $retenciones = $cfdiCommon->getFormattedRetenciones($retenciones);
            $retencionesDR = $impuestosDR->getRetencionesDR();
            foreach ($retenciones as $key => $value) {
                $baseDR = round($value['Base'] * $proporcionPagada, 2);
                $importeDR = round($value['Importe'] * $proporcionPagada, 2);
                $retencionesDR->addRetencionDR([
                    'BaseDR' => $baseDR,
                    'ImpuestoDR' => $value['Impuesto'],
                    'TipoFactorDR' => $value['TipoFactor'],
                    'TasaOCuotaDR' => $value['TasaOCuota'],
                    'ImporteDR' => $importeDR,
                ]);
            }
        }
        return $pagos;
    }
}
