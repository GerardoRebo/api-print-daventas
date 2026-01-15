<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\MyClasses\Factura\FacturaService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class PreFactura extends Model
{
    use HasFactory;

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
    public function system_folios(): MorphMany
    {
        return $this->morphMany(SystemFolio::class, 'facturable');
    }

    function callServie()
    {
        $facturaData = $this->getFacturaData();
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
        $xmlFacturaPath = "xml_factura/$user->active_organization_id/$idForPath";
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
                $this->ventaticket->xml_factura_path = $xmlFacturaPath . ".xml";
                $this->ventaticket->facturado_en = getMysqlTimestamp($user->configuration?->time_zone);
                $this->facturado_en = getMysqlTimestamp($user->configuration?->time_zone);
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
        $this->ventaticket->save();
    }
    function consumeTimbre($cantidad)
    {
        $saldoSystem = $this->organization->latestSystemFolio;
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
