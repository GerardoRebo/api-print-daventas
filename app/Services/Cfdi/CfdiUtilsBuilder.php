<?php

namespace App\Services\Cfdi;

use App\Models\PreFactura;
use CfdiUtils\Certificado\Certificado;
use Carbon\Carbon;
use CfdiUtils\CfdiCreator40;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Credential;

class CfdiUtilsBuilder
{
    protected string $certPath;
    protected string $keyPemPath;
    protected string $keyPass;
    protected $facturaData;
    protected $comprobante;

    public function __construct(protected PreFactura $preFactura)
    {

        $this->facturaData = $this->preFactura->getFacturaData();
        $storagePath = Storage::disk('local')->path('');
        $this->certPath = $storagePath . $this->facturaData['cer_path'];
        $this->keyPemPath = $storagePath . $this->facturaData['key_path'];
        $this->keyPass = Crypt::decryptString($this->facturaData['clave_privada_sat']);
    }

    public function createFromVenta(
        $serie,
        $formaPago,
        $metodoPago,
        $usoCfdi,
        $esPublicoEnGeneral,
        $nombre_receptor,
        $facturas_relacionadas
    ): string {
        if (app()->isProduction()) {
            $fileContent = Storage::disk('s3')->get($this->facturaData['cer_path']);
            Storage::disk('local')->put($this->facturaData['cer_path'], $fileContent);
            $fileContent = Storage::disk('s3')->get($this->facturaData['key_path']);
            Storage::disk('local')->put($this->facturaData['key_path'], $fileContent);
        }

        $cert = new Certificado($this->certPath);
        // Usar Credential para leer .cer y .key
        $csd = Credential::openFiles($this->certPath, $this->keyPemPath, $this->keyPass);

        $comprobanteAtributos = $this->getComprobanteAtributos($serie, $formaPago, $metodoPago);

        $creator = new CfdiCreator40($comprobanteAtributos, $cert);

        $creator->putCertificado(
            new Certificado($csd->certificate()->pem()),
            true // auto-asigna RFC y nombre del emisor
        );

        $this->comprobante = $creator->comprobante();

        $this->addEmisor();
        // Receptor
        $this->addReceptor($usoCfdi, $esPublicoEnGeneral, $nombre_receptor);
        // Facturas Relacionadas
        $this->addFacturasRelacionadas($facturas_relacionadas);
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
        $this->preFactura->impuesto_retenido = $sumas->getImpuestosRetenidos();
        $this->preFactura->impuesto_traslado_array = $sumas->getTraslados();
        $this->preFactura->impuesto_retenido_array = $sumas->getRetenciones();
        $this->preFactura->setTotal();

        // // Si manejas impuestos locales:
        // $this->preFactura->total_local_traslados = $sumas->trasladosLocales();
        // $this->preFactura->total_local_retenciones = $sumas->retencionesLocales();

        // Luego puedes guardar el modelo si así lo deseas
        $this->preFactura->save();

        return $creator->asXml();
    }
    function getComprobanteAtributos($serie, $formaPago, $metodoPago)
    {
        $pagado_en = Carbon::parse(getMysqlTimestamp());
        return [
            'Serie' => $serie,
            'Folio' => $this->preFactura->ventaticket->consecutivo ?? 1,
            'Fecha' => $pagado_en->format("Y-m-d\TH:i:s"),
            'FormaPago' => $formaPago ?? '01',
            'MetodoPago' => $metodoPago,
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
    function addFacturasRelacionadas($facturas_relacionadas)
    {
        if (!empty($facturas_relacionadas)) {
            // Agrupar por tipo de relación
            // Agrupar por tipo
            $agrupadas = [];
            foreach ($facturas_relacionadas as $rel) {
                $tipo = $rel['tipo'];
                if (!isset($agrupadas[$tipo])) {
                    $agrupadas[$tipo] = [];
                }
                $agrupadas[$tipo][] = ['UUID' => $rel['folio']];
            }

            // Crear los nodos
            foreach ($agrupadas as $tipoRelacion => $uuids) {
                // Agregar CfdiRelacionados con el TipoRelacion
                $cfdiRelacionados = $this->comprobante->addCfdiRelacionados([
                    'TipoRelacion' => $tipoRelacion,
                ]);

                // Agregar todos los CfdiRelacionado dentro
                $cfdiRelacionados->multiCfdiRelacionado(...$uuids);
            }
        }
    }
    function addReceptor($usoCfdi, $esPublicoEnGeneral, $nombre_receptor)
    {
        if ($esPublicoEnGeneral) {
            if ($nombre_receptor == "PUBLICO EN GENERAL" || $nombre_receptor == "") {
                $nombre_receptor = 'VENTA AL PUBLICO EN GENERAL';
            }
            $this->comprobante->addReceptor([
                'Rfc' => 'XAXX010101000',
                'Nombre' => $nombre_receptor,
                'DomicilioFiscalReceptor' => $this->facturaData['codigo_postal'],
                'RegimenFiscalReceptor' => "616",
                'UsoCFDI' => "S01"
            ]);
            return;
        }
        $cliente = $this->preFactura->ventaticket->cliente;
        $this->comprobante->addReceptor([
            'Rfc' => strtoupper($cliente->rfc),
            'Nombre' => strtoupper($cliente->razon_social),
            'DomicilioFiscalReceptor' => $cliente->codigo_postal,
            'RegimenFiscalReceptor' => $cliente->regimen_fiscal,
            'UsoCFDI' => $usoCfdi
        ]);
    }
    function addConceptos()
    {
        foreach ($this->preFactura->articulos as $articulo) {
            $producto = $articulo->product;

            $concepto = $this->comprobante->addConcepto([
                'ClaveProdServ' => $producto->c_claveProdServ ?? '01010101',
                'NoIdentificacion' => $producto->codigo,
                'Cantidad' => $articulo->cantidad,
                'ClaveUnidad' => $producto->c_ClaveUnidad ?? 'H87',
                // 'Unidad' => $producto->c_ClaveUnidad_descripcion ?? 'Pieza',
                'Descripcion' => $producto->name,
                'ValorUnitario' => $articulo->precio,
                'Importe' => $articulo->importe,
                'Descuento' => $articulo->descuento,
                'ObjetoImp' => $producto->ObjetoImp,
            ]);
            if ($producto->ObjetoImp != "02") {
                continue;
            }

            // Impuestos por artículo
            foreach ($articulo->taxes as $tax) {
                if ($tax->tipo === 'traslado') {
                    $concepto->addTraslado([
                        'Base' => $tax->base,
                        'Impuesto' => $tax->c_impuesto,
                        'TipoFactor' => $tax->tipo_factor,
                        'TasaOCuota' => number_format($tax->tasa_o_cuota, 6, '.', ''),
                        'Importe' => $tax->importe,
                    ]);
                } elseif ($tax->tipo === 'retenido') {
                    $concepto->addRetencion([
                        'Base' => $tax->base,
                        'Impuesto' => $tax->c_impuesto,
                        'TipoFactor' => $tax->tipo_factor,
                        'TasaOCuota' => number_format($tax->tasa_o_cuota, 6, '.', ''),
                        'Importe' => $tax->importe,
                    ]);
                }
            }
        }
    }
}
