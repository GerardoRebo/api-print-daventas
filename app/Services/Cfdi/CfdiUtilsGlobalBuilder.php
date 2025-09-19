<?php

namespace App\Services\Cfdi;

use App\Models\PreFacturaGlobal;
use CfdiUtils\Certificado\Certificado;
use App\Models\VentaTicket;
use Carbon\Carbon;
use CfdiUtils\CfdiCreator40;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Credential;

class CfdiUtilsGlobalBuilder
{
    protected string $certPath;
    protected string $keyPemPath;
    protected string $keyPass;
    protected $facturaData;
    protected $comprobante;

    public function __construct(protected PreFacturaGlobal $preFactura)
    {

        $this->facturaData = $this->preFactura->getFacturaData();
        $storagePath = Storage::disk('local')->path('');
        $this->certPath = $storagePath . $this->facturaData['cer_path'];
        $this->keyPemPath = $storagePath . $this->facturaData['key_path'];
        $this->keyPass = Crypt::decryptString($this->facturaData['clave_privada_sat']);
    }

    public function createFromVenta($serie, $formaPago, $year, $month): string
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

        $comprobanteAtributos = $this->getComprobanteAtributos($serie, $formaPago);

        $creator = new CfdiCreator40($comprobanteAtributos, $cert);

        $creator->putCertificado(
            new Certificado($csd->certificate()->pem()),
            true // auto-asigna RFC y nombre del emisor
        );
        $this->comprobante = $creator->comprobante();

        $this->addEmisor();
        //informacion global
        $this->addInformacionGlobal($year, $month);
        // Receptor
        $this->addReceptor();
        // Conceptos e impuestos
        $this->addConceptos();

        // Cálculo automático de totales
        // Paso 1: Generar el objeto SumasConceptos
        $sumas = $creator->buildSumasConceptos(2);

        // Paso 2: Aplicarlo al comprobante
        $creator->addSumasConceptos($sumas);

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
        // Paso 3: Actualizar tu modelo con los totales
        $this->preFactura->subtotal = $sumas->getSubTotal();
        $this->preFactura->descuento = $sumas->getDescuento();
        $this->preFactura->impuesto_traslado = $sumas->getImpuestosTrasladados();
        $this->preFactura->total = $sumas->getTotal();

        // // Si manejas impuestos locales:
        // $this->preFactura->total_local_traslados = $sumas->trasladosLocales();
        // $this->preFactura->total_local_retenciones = $sumas->retencionesLocales();

        // Luego puedes guardar el modelo si así lo deseas
        $this->preFactura->save();

        return $creator->asXml();
    }
    function getComprobanteAtributos($serie, $formaPago)
    {
        $pagado_en = Carbon::parse(getMysqlTimestamp());
        if ($serie) {
            $serie = 'G' . $serie;
        }
        return [
            'Serie' => $serie,
            'Folio' => $this->preFactura->id ?? 1,
            'Fecha' => $pagado_en->format("Y-m-d\TH:i:s"),
            'FormaPago' => $formaPago ?? '01',
            'MetodoPago' => 'PUE',
            'Moneda' => 'MXN',
            'TipoDeComprobante' => 'I',
            'Exportacion' => '01',
            'LugarExpedicion' => $this->facturaData['codigo_postal'],
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
        // $cliente = $this->preFactura->ventaticket->cliente;
        $this->comprobante->addReceptor([
            'Rfc' => 'XAXX010101000',
            'Nombre' => 'PUBLICO EN GENERAL',
            'DomicilioFiscalReceptor' => $this->facturaData['codigo_postal'],
            'RegimenFiscalReceptor' => "616",
            'UsoCFDI' => "S01"
        ]);
    }
    function addConceptos()
    {
        foreach ($this->preFactura->articulos as $articulo) {
            $concepto = $this->comprobante->addConcepto([
                'ClaveProdServ' => '01010101',
                'NoIdentificacion' => $articulo->ventaticket->consecutivo ?? $articulo->ventaticket_id,
                'Cantidad' => 1,
                'ClaveUnidad' => 'ACT',
                // 'Unidad' => $producto->c_ClaveUnidad_descripcion ?? 'Pieza',
                'Descripcion' => 'Venta',
                'ValorUnitario' => $articulo->subtotal,
                'Importe' => $articulo->subtotal,
                'Descuento' => $articulo->descuento,
                'ObjetoImp' => $articulo->impuesto_traslado > 0 ? '02' : '01',
            ]);
            if ($articulo->impuesto_traslado <= 0) {
                continue;
            }

            // Impuestos por artículo
            foreach ($articulo->impuesto_traslado_array as $tax) {
                $concepto->addTraslado([
                    'Base' => $tax['Base'],
                    'Impuesto' => $tax['Impuesto'],
                    'TipoFactor' => $tax['TipoFactor'],
                    'TasaOCuota' => $tax['TasaOCuota'],
                    'Importe' => $tax['Importe'],
                ]);
            }
        }
    }
    function addInformacionGlobal($year, $month)
    {
        // Emisor (ya saca RFC y Nombre del certificado)
        $this->comprobante->addInformacionGlobal([
            'Año' => $year,
            'Meses' => $month,
            'Periodicidad' => $this->preFactura->c_periodicidad,
        ]);
    }
}
