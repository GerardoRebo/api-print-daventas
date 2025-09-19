<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Exceptions\OperationalException;
use App\MyClasses\Factura\FacturaService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
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
    public function system_folios(): MorphMany
    {
        return $this->morphMany(SystemFolio::class, 'facturable');
    }
    function facturar($formaPago, $user, $cantidad, $serie)
    {
        $cfdiUtils = new CfdiUtilsPagoBuilder($this);
        $xml = $cfdiUtils->createFromAbono($serie, $formaPago, $cantidad);
        $jsonPath = 'facturaTmp/XmlDesdePhpSinTimbrar.xml';
        Storage::disk('local')->put($jsonPath, $xml);

        $this->callServie($jsonPath, $jsonPath);

        $pdfFacturaPath = "pdf_factura_pagos/$user->organization_id/" . $this->deuda->ventaticket_id;
        /* create pdf */
        $fService = new FacturaService;
        $fService->storePdf('facturaTmp/XmlDesdePhpTimbrado.xml', $user, $pdfFacturaPath);
        $this->pdf_factura_path = $pdfFacturaPath . ".pdf";
        $this->save();
    }
    function callServie()
    {
        $facturaData = $this->getFacturaData();
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
        $xmlFacturaPath = "xml_factura_pagos/$user->organization_id/$idForPath";
        if (app()->isProduction()) {
            try {
                Storage::disk('local')->delete($facturaData['cer_path']);
                Storage::disk('local')->delete($facturaData['key_path']);
            } catch (\Throwable $th) {
                throw $th;
            }
        }
        try {
            if (Storage::disk('local')->exists('facturaTmp/XmlDesdePhpTimbrado.xml')) {
                Storage::put($xmlFacturaPath . ".xml", Storage::disk('local')->get('facturaTmp/XmlDesdePhpTimbrado.xml'));
                $this->xml_factura_path = $xmlFacturaPath . ".xml";
                $this->facturado_en = getMysqlTimestamp($user->configuration?->time_zone);
                logger($this->xml_factura_path);
                $this->save();
                $this->consumeTimbre(1);
            } else {
                $texto = $result->output();
                $resultado = Str::after($texto, 'Error al timbrar');
                throw new OperationalException($resultado, 1);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    function consumeTimbre($cantidad)
    {
        $user = $this->deuda->ventaticket->user;
        $saldoSystem = $user->organization->latestSystemFolio;
        $saldoSystemScalar = $saldoSystem?->saldo ?? 0;
        if ($saldoSystemScalar) {
            $this->system_folios()->create([
                'organization_id' => $this->organization_id,
                'cantidad' => $cantidad,
                'antes' => $saldoSystemScalar,
                'saldo' => $saldoSystemScalar - 1,
            ]);
            return;
        }

        $saldo = $user->organization->latestFoliosUtilizado;
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
        $facturaHelper = new FacturaService;
        $facturaData = $facturaHelper->getData($this->deuda->ventaticket);
        return $facturaData;
    }
}
