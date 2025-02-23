<?php

namespace App\MyClasses\Factura;

use App\Pdf\Translators\PlatesHtmlTranslator;
use Illuminate\Support\Facades\Storage;

/**
 * Class Comprobante
 *
 * @property string $Emisor
 * @property string $Receptor
 * @property string $Conceptos
 * @property string $Version
 * @property string $Moneda
 * @property string $TipoDeComprobante
 * @property string $MetodoPago
 * @property string $Serie
 * @property string $Folio
 * @property string $Fecha
 * @property string $FormaPago
 * @property string $SubTotal
 * @property string $Descuento
 * @property string $Total
 * @property string $LugarExpedicion
 * @property string $Impuestos
 * @property string $InformacionGlobal
 */
class Comprobante
{
    // public $Emisor;
    // public $Receptor;
    // public $Conceptos;
    // public $Version;
    // public $Moneda;
    // public $TipoDeComprobante;
    // public $MetodoPago;
    // public $Serie;
    // public $Folio;
    // public $Fecha;
    // public $FormaPago;
    // public $SubTotal;
    // public $Descuento;
    // public $Total;
    // public $LugarExpedicion;
    // public $Impuestos;
}
/**
 * Class ComprobanteEmisor
 *
 * @property string $Rfc
 * @property string $Nombre
 * @property string $RegimenFiscal
 */
class ComprobanteEmisor
{
    // public $Rfc;
    // public $Nombre;
    // public $RegimenFiscal;
}
/**
 * Class ComprobanteReceptor
 *
 * @property string $Nombre
 * @property string $Rfc
 * @property string $UsoCFDI
 * @property string $DomicilioFiscalReceptor
 * @property string $RegimenFiscalReceptor
 */
class ComprobanteReceptor
{
    // public $Nombre;
    // public $Rfc;
    // public $UsoCFDI;
    // public $domicilioFiscalReceptor;
    // public $RegimenFiscalReceptor;
}
/**
 * Class ComprobanteConcepto
 *
 * @property string $Importe
 * @property string $ClaveProdServ
 * @property string $ClaveUnidad
 * @property string $Cantidad
 * @property string $Descripcion
 * @property string $ValorUnitario
 * @property string $Descuento
 * @property string $ObjetoImp
 * @property object $Impuestos
 * @property object $NoIdentificacion
 */
class ComprobanteConcepto
{
    // public $Importe;
    // public $ClaveProdServ;
    // public $ClaveUnidad;
    // public $Cantidad;
    // public $Descripcion;
    // public $ValorUnitario;
    // public $Descuento;
    // public $ObjetoImp;
    // public $Impuestos;
}
/**
 * Class ComprobanteConceptoImpuestosTraslado
 *
 * @property string $Base
 * @property string $TipoFactor
 * @property string $Impuesto
 * @property string $Importe
 * @property string $TasaOCuota
 */
class ComprobanteConceptoImpuestosTraslado
{
    // public $Base;
    // public $TipoFactor;
    // public $Impuesto;
    // public $Importe;
    // public $TasaOCuota;
}
/**
 * Class ComprobanteConceptoImpuestosRetencion
 *
 * @property string $Base
 * @property string $TipoFactor
 * @property string $Impuesto
 * @property string $Importe
 * @property string $TasaOCuota
 */
class ComprobanteConceptoImpuestosRetencion
{
    // public $Base;
    // public $TipoFactor;
    // public $Impuesto;
    // public $Importe;
    // public $TasaOCuota;
}
/**
 * Class ComprobanteImpuestos
 *
 * @property string $TotalImpuestosTrasladados
 * @property string $Traslados
 * @property string $TotalImpuestosRetenidos
 * @property string $Retenciones
 */
class ComprobanteImpuestos
{
    // public $TotalImpuestosTrasladados;
    // public $Traslados;
    // public $TotalImpuestosRetenidos;
    // public $Retenciones;
}
/**
 * Class ComprobanteImpuestosRetencion
 *
 * @property string $Base
 * @property string $TipoFactor
 * @property string $Impuesto
 * @property string $Importe
 * @property string $TasaOCuota
 */
class ComprobanteImpuestosRetencion
{
    // public $Impuesto;
    // public $Importe;
}
/**
 * Class ComprobanteConceptoImpuestos
 *
 * @property string $Traslados
 * @property string $Retenciones
 */
class ComprobanteConceptoImpuestos
{
    // public $Traslados;
    // public $Retenciones;

}
class Pagos
{
    // public $Traslados;
    // public $Retenciones;

}

/**
 * Class PagosTotales
 *
 */
class PagosTotales {}
/**
 * Class PagosPagoDoctoRelacionado
 *
 */
/**
 * Class PagosPago
 *
 * @property string $MonedaP
 * @property string $FechaPago
 * @property string $FormaDePagoP
 * @property string $Monto
 * @property string $TipoCambioP
 */
class PagosPago {}
class PagosPagoDoctoRelacionado {}
class PagosPagoDoctoRelacionadoImpuestosDR {}
class PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR {}
class PagosPagoImpuestosP {}
class PagosPagoImpuestosPTrasladoP {}
class FacturaService
{
    public function __construct() {}
    function getData($ventaticket)
    {

        $facturacionInfo = $ventaticket->almacen->facturacion_info;
        if (!$facturacionInfo) {
            $facturacionInfo = $ventaticket->organization->facturacion_info;
        }
        return [
            "clave_privada_local" => $facturacionInfo->clave_privada_local,
            "clave_privada_sat" => $facturacionInfo->clave_privada_sat,
            "codigo_postal" => $facturacionInfo->codigo_postal,
            "rfc" => $facturacionInfo->rfc,
            "razon_social" => $facturacionInfo->razon_social,
            "regimen_fiscal" => $facturacionInfo->regimen_fiscal,
            "cer_path" => $facturacionInfo->cer_path,
            "key_path" => $facturacionInfo->key_path,
        ];
    }
    function getGlobalData($organization)
    {

        $facturacionInfo = $organization->facturacion_info;
        return [
            "clave_privada_local" => $facturacionInfo->clave_privada_local,
            "clave_privada_sat" => $facturacionInfo->clave_privada_sat,
            "codigo_postal" => $facturacionInfo->codigo_postal,
            "rfc" => $facturacionInfo->rfc,
            "razon_social" => $facturacionInfo->razon_social,
            "regimen_fiscal" => $facturacionInfo->regimen_fiscal,
            "cer_path" => $facturacionInfo->cer_path,
            "key_path" => $facturacionInfo->key_path,
        ];
    }
    function generatePdf($xmlPath, $output, $user)
    {
        $xml = Storage::disk('local')->get($xmlPath);

        // clean cfdi
        $xml = \PhpCfdi\CfdiCleaner\Cleaner::staticClean($xml);

        // create the main node structure
        $comprobante = \CfdiUtils\Nodes\XmlNodeUtils::nodeFromXmlString($xml);

        // create the CfdiData object, it contains all the required information
        $cfdiData = (new \PhpCfdi\CfdiToPdf\CfdiDataBuilder())
            ->build($comprobante);

        $htmlTranslator = new PlatesHtmlTranslator(
            base_path('app/Pdf/Templates'),
            'generic',
            $user
        );
        $converter = new \PhpCfdi\CfdiToPdf\Converter(
            new \PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder($htmlTranslator)
        );

        // create the invoice as output.pdf
        // Storage::disk('local')->get('facturaTmp/XmlDesdePhpTimbrado.xml')
        $converter->createPdfAs($cfdiData, $output);
    }
    function storePdf($xmlPath, $user, $pdfFacturaPath)
    {
        if (Storage::disk('local')->exists($xmlPath)) {
            $input = $xmlPath;
            $localPdfPath = 'facturaTmp/factura.pdf';
            $output = Storage::disk('local')->path($localPdfPath);
            $this->generatePdf($input, $output, $user);
            Storage::disk('local')->delete($input);
            if (Storage::disk('local')->exists($localPdfPath)) {
                Storage::put($pdfFacturaPath . ".pdf", Storage::disk('local')->get($localPdfPath));
            }
            if (app()->isProduction()) {
                Storage::disk('local')->delete($localPdfPath);
            }
        }
    }
}
