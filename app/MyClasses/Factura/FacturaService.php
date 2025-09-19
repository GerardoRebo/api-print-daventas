<?php

namespace App\MyClasses\Factura;

use App\Pdf\Translators\PlatesHtmlTranslator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Pfx\PfxExporter;
use Illuminate\Support\Str;

class FacturaService
{
    public $uuid = null;
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
            "pfx_path" => $facturacionInfo->pfx_path,
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
            "pfx_path" => $facturacionInfo->pfx_path,
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

        $this->uuid = $cfdiData->timbreFiscalDigital()['UUID'];
        $htmlTranslator = new PlatesHtmlTranslator(
            base_path('app/Pdf/Templates'),
            'generic',
            $user
        );
        $converter = new \PhpCfdi\CfdiToPdf\Converter(
            new \PhpCfdi\CfdiToPdf\Builders\Html2PdfBuilder($htmlTranslator)
        );

        // create the invoice as output.pdf
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
    function getPfxPath($ticket)
    {
        $facturaData = $this->getData($ticket);

        // si ya existe el pfx devolvemos el path
        if ($facturaData['pfx_path']) return $facturaData['pfx_path'];

        // descargar .cer y .key de S3 si no existen en local
        foreach (['cer_path', 'key_path'] as $fileKey) {
            if (!Storage::disk('local')->exists($facturaData[$fileKey])) {
                $fileContent = Storage::disk('s3')->get($facturaData[$fileKey]);
                Storage::disk('local')->put($facturaData[$fileKey], $fileContent);
            }
        }

        $storagePath = Storage::disk('local')->path('');
        $privateKey  = Crypt::decryptString($facturaData['clave_privada_sat']);

        // abrir credenciales
        $credential = Credential::openFiles(
            $storagePath . $facturaData['cer_path'],
            $storagePath . $facturaData['key_path'],
            $privateKey
        );

        // exportar PFX
        $pfxExporter = new PfxExporter($credential);
        $pfxContents = $pfxExporter->export($privateKey);

        // crear ruta única
        $pfxPath = 'pfxs/' . Str::uuid() . '.pfx';
        Storage::put($pfxPath, $pfxContents);

        $ticket->organization->facturacion_info->update(['pfx_path' => $pfxPath]);

        return $pfxPath;
    }
}
