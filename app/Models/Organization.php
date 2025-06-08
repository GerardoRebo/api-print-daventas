<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\MyClasses\Factura\ComprobanteImpuestos;
use App\MyClasses\Factura\ComprobanteImpuestosTraslado;
// use App\MyClasses\Factura\ComprobanteImpuestosTraslado;
use App\MyClasses\Factura\FacturaService;
use App\MyClasses\Services\AlmacenService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use stdClass;

class Organization extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['url'];

    //relación muchos a muchos
    /* public function users(){
        return $this->belongsToMany('App\Models\User');
    } */
    //RELACIÓN UNO A MUCHOS
    public function invitations()
    {
        return $this->hasMany('App\Models\Invitation');
    }
    public function users()
    {
        return $this->hasMany('App\Models\User');
    }
    public function almacens()
    {
        return $this->hasMany('App\Models\Almacen');
    }
    public function facturas()
    {
        return $this->hasMany(PreFactura::class);
    }
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
    public function facturasGlobales()
    {
        return $this->hasMany(PreFacturaGlobal::class);
    }
    public function folios_utilizados()
    {
        return $this->hasMany(FoliosUtilizado::class);
    }
    //RELACIÓN UNO A MUCHOS Inversa
    public function plan()
    {
        return $this->belongsTo('App\Models\Plan');
    }
    public function facturacion_info(): MorphOne
    {
        return $this->morphOne(FacturacionInfo::class, 'infoable');
    }
    public function latestFoliosUtilizado()
    {
        return $this->hasOne(FoliosUtilizado::class)->latestOfMany();
    }
    public function image(): MorphOne
    {
        return $this->morphOne(SingleImage::class, 'single_imageable');
    }
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($organization) {
            $organization->slug_name = $organization->generateUniqueSlug($organization->name);
        });

        static::updating(function ($organization) {
            $organization->slug_name = $organization->generateUniqueSlug($organization->name, $organization->id);
        });
    }
    function getClientRetentionRules($regimenFiscal)
    {
        return RetentionRule::with('tax')->where('organization_id', $this->id)
            ->where('regimen_fiscal', $regimenFiscal)->get();
    }
    function getClientRetentionRulesString($regimenFiscal)
    {
        $rules = $this->getClientRetentionRules($regimenFiscal);
        $rules = $rules->map(function ($item) {
            return $item->tax->descripcion . ' %' . $item->tax->tasa_cuota;
        });
        return $rules->implode($rules, ', ');
    }
    protected function url(): Attribute
    {
        $url = config('app.shop_tienda_base_url');
        return Attribute::make(
            get: fn() => $url . '/tiendas/' . $this->slug_name . '/products/'
        );
    }
    public function generateUniqueSlug($name, $organizationId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (Organization::where('slug_name', $slug)->when($organizationId, function ($query) use ($organizationId) {
            return $query->where('id', '!=', $organizationId);
        })->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
    public function createTax($c_impuesto, $activo, $tasaCuota, $tipo): Tax
    {
        $newTax = new Tax();
        $newTax->organization_id = $this->id;
        $newTax->tipo = $tipo;
        $newTax->tipo_factor = 'Tasa';
        $newTax->tasa_cuota = $tasaCuota;
        $newTax->tasa_cuota_str = $this->formatPercentage($tasaCuota);
        if ($c_impuesto == '002') {
            $newTax->descripcion = 'IVA';
            $newTax->c_impuesto = '002';
        } elseif ($c_impuesto == '003') {
            $newTax->descripcion = 'IEPS';
            $newTax->c_impuesto = '003';
        }
        $newTax->activo = $activo;
        $newTax->save();
        return $newTax;
    }
    function updateTax($c_impuesto, $activo, $tasaCuota, $tipo, $impuesto)
    {
        if ($c_impuesto == '002') {
            $impuesto->descripcion = 'IVA';
            $impuesto->c_impuesto = '002';
        } elseif ($c_impuesto == '003') {
            $impuesto->descripcion = 'IEPS';
            $impuesto->c_impuesto = '003';
        }
        $impuesto->tipo = $tipo;
        $impuesto->activo = $activo;
        $impuesto->tasa_cuota = $tasaCuota;
        $impuesto->tasa_cuota_str = $this->formatPercentage($tasaCuota);
        $impuesto->save();
        return $impuesto;
    }
    //0.160000
    private function formatPercentage($number)
    {
        // Divide the number by 100
        $result = $number / 100;

        // Format the result as a string with six decimal places
        $formattedResult = number_format($result, 6, '.', '');

        return $formattedResult;
    }
    function updateClavePrivadaSat($value)
    {
        $data = [
            "clave_privada_sat" => Crypt::encryptString($value),
        ];
        // Access the polymorphic relation
        $facturacionInfo = $this->facturacion_info()->firstOrNew([
            'infoable_id' => $this->id, // Assuming 'id' is the local key
            'infoable_type' => get_class($this), // Get the class name dynamically
        ]);

        // Update the existing record or create a new one
        $facturacionInfo->fill($data)->save();
    }
    function updateClavePrivadaLocal($value)
    {
        $data = [
            "clave_privada_local" => $value,
        ];
        // Access the polymorphic relation
        $facturacionInfo = $this->facturacion_info()->firstOrNew([
            'infoable_id' => $this->id, // Assuming 'id' is the local key
            'infoable_type' => get_class($this), // Get the class name dynamically
        ]);

        // Update the existing record or create a new one
        $facturacionInfo->fill($data)->save();
    }
    function updateFacturacionInfo($razonSocial, $regimenFiscal, $rfc, $codigoPostal, $c_periodicidad)
    {
        $data = [
            "razon_social" => $razonSocial,
            "regimen_fiscal" => $regimenFiscal,
            "rfc" => $rfc,
            "codigo_postal" => $codigoPostal,
            "c_periodicidad" => $c_periodicidad,
        ];
        // Access the polymorphic relation
        $facturacionInfo = $this->facturacion_info()->firstOrNew([
            'infoable_id' => $this->id, // Assuming 'id' is the local key
            'infoable_type' => get_class($this), // Get the class name dynamically
        ]);

        // Update the existing record or create a new one
        $facturacionInfo->fill($data)->save();
    }
    function updateKey($path, $fileName)
    {
        $data = [
            "key_path" => $path,
            "key_name" => $fileName,
        ];

        // Access the polymorphic relation
        $facturacionInfo = $this->facturacion_info()->firstOrNew([
            'infoable_id' => $this->id, // Assuming 'id' is the local key
            'infoable_type' => get_class($this), // Get the class name dynamically
        ]);

        // Update the existing record or create a new one
        $facturacionInfo->fill($data)->save();
    }
    function updateCer($path, $fileName)
    {
        $data = [
            "cer_path" => $path,
            "cer_name" => $fileName,
        ];
        // Access the polymorphic relation
        $facturacionInfo = $this->facturacion_info()->firstOrNew([
            'infoable_id' => $this->id, // Assuming 'id' is the local key
            'infoable_type' => get_class($this), // Get the class name dynamically
        ]);

        // Update the existing record or create a new one
        $facturacionInfo->fill($data)->save();
    }
    function getVentatickets($desde, $hasta)
    {
        return Ventaticket::select(
            'ventatickets.id',
            'consecutivo',
            'pagado_en',
            'ventatickets.total',
            DB::raw('GROUP_CONCAT(pre_facturas.id) as pre_factura')  // Combine all pre_facturas for the same ventaticket
        )
            ->leftJoin('pre_facturas', 'ventatickets.id', '=', 'pre_facturas.ventaticket_id')
            ->where('ventatickets.organization_id', $this->id)
            ->whereDate('ventatickets.pagado_en', '>=', $desde)
            ->whereDate('ventatickets.pagado_en', '<=', $hasta)
            ->where('esta_cancelado', 0)
            ->where('total_devuelto', 0)
            ->whereNull('ventatickets.facturado_en')
            ->groupBy('ventatickets.id', 'consecutivo', 'pagado_en', 'ventatickets.total')  // Group by ventaticket's unique columns
            ->get();
    }
    function emitirFacturaGlobal($facturaId, $formaPago, $usoCfdi, $serie, $clavePrivadaLocal, $year, $mes)
    {
        $user = $this->user;
        $this->facturaValidations($clavePrivadaLocal);

        /** @var PreFacturaGlobal $preFactura */
        $preFactura = PreFacturaGlobal::findOrFail($facturaId);

        $oComprobante = $preFactura->initializeComprobante($serie, $formaPago, $year, $mes);

        //emisor
        $oEmisor = $preFactura->createEmisor();
        //receptor
        $oReceptor = $preFactura->createReceptor($usoCfdi);

        //conceptos
        $lstConceptos = $preFactura->createConceptos();

        //NODO IMPUESTO

        $oIMPUESTOS = new ComprobanteImpuestos();

        $facturaImpuestos = $preFactura->getImpuestos();

        if ($facturaImpuestos['trasladados']) {
            $oIMPUESTOS->TotalImpuestosTrasladados = $preFactura->impuesto_traslado;
            $oIMPUESTOS->Traslados = $facturaImpuestos['trasladados'];
            $oComprobante->Impuestos = $oIMPUESTOS;
        }

        $oComprobante->Emisor = $oEmisor;
        $oComprobante->Receptor = $oReceptor;
        $oComprobante->Conceptos = $lstConceptos;

        $MiJson = "";
        $MiJson = json_encode($oComprobante);
        $jsonPath = 'factura_json/' . $this->id;
        Storage::disk('local')->put($jsonPath, $MiJson);

        $preFactura->callServie($jsonPath);
        $pdfFacturaPath = "pdf_factura_global/" . $this->id . "/" . $preFactura->id;
        $fService = new FacturaService;
        $fService->storePdf('facturaTmp/XmlDesdePhpTimbrado.xml', $user, $pdfFacturaPath);
        $preFactura->pdf_factura_path = $pdfFacturaPath . ".pdf";
        $preFactura->save();
    }
    function createPreFacturaGlobal($userId, $ticketIds, $desde, $hasta, $c_periodicidad)
    {
        $preFacturaGlobal = new PreFacturaGlobal();
        $preFacturaGlobal->organization_id = $this->id;
        $preFacturaGlobal->user_id = $userId;
        $preFacturaGlobal->ticket_ids = $ticketIds;
        $preFacturaGlobal->desde = $desde;
        $preFacturaGlobal->hasta = $hasta;
        $preFacturaGlobal->c_periodicidad = $c_periodicidad;
        $preFacturaGlobal->save();
        $chunkSize = 100;
        $chunks = array_chunk($ticketIds, $chunkSize);
        foreach ($chunks as $chunk) {
            Ventaticket::with('ventaticket_articulos.taxes')
                ->whereIn('id', $chunk)
                ->chunkById(100, function ($ventatickets) use ($preFacturaGlobal) {
                    foreach ($ventatickets as $ticket) {
                        $preFactura = new PreFactura();
                        $preFactura->ventaticket_id = $ticket->id;
                        $preFactura->organization_id = $this->id;
                        $preFactura->pre_factura_global_id = $preFacturaGlobal->id;
                        $preFactura->save();
                        $articulos = [];
                        $preFacturaTaxes = [];
                        foreach ($ticket->ventaticket_articulos as $articulo) {
                            $art['cantidad'] = $articulo->cantidad;
                            $art['precio'] = $articulo->precio_usado;
                            $art['importe'] = $articulo->cantidad * $articulo->precio_usado;
                            if ($articulo->taxes->count()) {
                                $art['descuento'] = $articulo->importe_descuento;
                            } else {
                                // $preArticulo->setBaseImpositiva($articulo->product->taxes);
                                $taxes = $articulo->product->taxes;
                                $sumaTasas = $taxes->sum('tasa_cuota') / 100;
                                $art['precio'] = ($articulo->precio_usado - $articulo->descuento) / (1 + $sumaTasas);
                                $art['descuento'] = $articulo->product->getDescuentoCantidad($articulo->cantidad, $articulo->precio);
                                $art['importe'] = $articulo->cantidad * $art['precio'];
                            }
                            if ($articulo->taxes->count()) {
                                $cantidades = [];
                                foreach ($articulo->taxes as $articuloTax) {
                                    if ($articuloTax->tipo == 'retenido') continue;
                                    $preFacturaTaxes[] = [
                                        'tax_id' => $articuloTax->tax_id,
                                        'c_impuesto' => $articuloTax->c_impuesto,
                                        'tipo_factor' => $articuloTax->tipo_factor,
                                        'tasa_o_cuota' => $articuloTax->tasa_o_cuota,
                                        'tipo' => $articuloTax->tipo,
                                        'importe' => $articuloTax->importe,
                                        'base' => $articuloTax->base,
                                    ];
                                    array_push($cantidades, $articuloTax->importe);
                                };
                                $art['impuesto_traslado'] = array_sum($cantidades);
                            } else {
                                $cantidades = [];
                                foreach ($articulo->product->taxes as $tax) {
                                    if ($tax->tipo == 'retenido') continue;
                                    $baseImponible = $art['importe'];
                                    $importe = $baseImponible * ($tax->tasa_cuota / 100);
                                    $preFacturaTaxes[] = [
                                        'tax_id' => $tax->id,
                                        'c_impuesto' => $tax->c_impuesto,
                                        'tipo_factor' => $tax->tipo_factor,
                                        'tasa_o_cuota' => $tax->tasa_cuota_str,
                                        'tipo' => $tax->tipo,
                                        'importe' => round($importe, 2),
                                        'base' => round($baseImponible, 2),
                                    ];
                                    array_push($cantidades, $importe);
                                };
                                $art['impuesto_traslado'] = array_sum($cantidades);
                            }
                            $articulos[] = $art;
                        }
                        $subtotal = array_sum(array_column($articulos, 'importe'));
                        $descuento = array_sum(array_column($articulos, 'descuento'));
                        $impuesto_traslado = array_sum(array_column($articulos, 'impuesto_traslado'));
                        $impuestos = $this->getImpuestosArray($preFacturaTaxes);
                        $preFactura->subtotal = $subtotal;
                        $preFactura->descuento = $descuento;
                        $preFactura->impuesto_traslado = $impuesto_traslado;
                        $preFactura->impuesto_traslado_array = $impuestos['traslados'];
                        $preFactura->setTotal();
                        $preFactura->save();
                    }
                });
        };
        $preFacturaGlobal->subtotal = $preFacturaGlobal->articulos->sum('subtotal');
        $preFacturaGlobal->descuento = $preFacturaGlobal->articulos->sum('descuento');
        $preFacturaGlobal->impuesto_traslado = $preFacturaGlobal->articulos->sum('impuesto_traslado');
        $preFacturaGlobal->total = $preFacturaGlobal->articulos->sum('total');
        $preFacturaGlobal->save();
        return $preFacturaGlobal->id;
    }
    function validatePreFacturaGlobal($ticketIds)
    {
        $chunkSize = 100;
        $chunks = array_chunk($ticketIds, $chunkSize);
        $errors = [];
        foreach ($chunks as $chunk) {
            Ventaticket::with('ventaticket_articulos.taxes', 'ventaticket_articulos.product.taxes')
                ->whereIn('id', $chunk)
                ->chunkById(100, function ($ventatickets) use (&$errors) {
                    foreach ($ventatickets as $ticket) {
                        foreach ($ticket->ventaticket_articulos as $articulo) {
                            if (!$articulo->product->taxes->count()) {
                                $errors[] = $articulo->product->name . ' (' . $articulo->product->codigo . ') ';
                            }
                        };
                    }
                });
        }
        if (count($errors)) {
            throw new OperationalException("Impuestos no configurados en los siguientes productos: " . implode(", ",  $errors), 1);
        }
    }
    private function getGroupedImpuestos($taxes)
    {
        $grouped = [];

        foreach ($taxes as $impuesto) {
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
    function getImpuestosArray($taxes)
    {
        $impuestosTrasladados = [];
        $impuestos = $this->getGroupedImpuestos($taxes);
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

                        $impuestosTrasladados[] = [
                            'c_impuesto' => $c_impuestoK,
                            'tipo_factor' => $tipo_factorK,
                            'tasa_o_cuota' => $tasa_o_cuotaK,
                            'tipo' => $tipoK,
                            'importe' => $importe,
                            'base' => $base,
                        ];
                    }
                }
            }
        }
        return [
            "traslados" => $impuestosTrasladados,
        ];
    }
    function getImpuestosObject($taxes)
    {
        $impuestosTrasladados = [];
        $impuestos = $this->getGroupedImpuestos($taxes);
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
                        $oI = new ComprobanteImpuestosTraslado();
                        $oI->Impuesto = $c_impuestoK;
                        $oI->TipoFactor = $tipo_factorK;
                        $oI->TasaOCuota = $tasa_o_cuotaK;
                        $oI->Importe = $importe;
                        $oI->Base = $base;
                        $impuestosTrasladados[] = $oI;
                    }
                }
            }
        }
        return [
            "traslados" => $impuestosTrasladados,
        ];
    }
    private function facturaValidations($clavePrivadaLocal)
    {
        $facturaHelper = new FacturaService;
        $facturaData = $facturaHelper->getGlobalData($this);
        foreach ($facturaData as $key => $value) {
            if (!$value) {
                throw new OperationalException("No has configurado el siguiente dato necesario para facturar: " . $key, 1);
            }
        }
        if ($facturaData['clave_privada_local'] != $clavePrivadaLocal) {
            throw new OperationalException("La clave privada local no es correcta",  1);
        }
    }
    function getTicketsForFacturaGlobal($ticketsIds): Ventaticket
    {
        return Ventaticket::whereIn($ticketsIds)->get();
    }

    function createCotizacionFromOrder($cart)
    {
        $cotizacion = new Cotizacion();
        $cotizacion->organization_id = $this->id;
        $cotizacion->cart_id = $cart->id;
        $cotizacion->numero_de_articulos = $cart->no_articulos;
        $cotizacion->cliente_foraneo_id = $cart->user_id;
        $cotizacion->pendiente = true;
        $cotizacion->comentarios = $cart->comments;
        $cotizacion->save();
        $cotizacion->addCotizacionArticulos($cart);
    }
    function getFacturasGlobales($desde, $hasta)
    {
        return $this->facturasGlobales()
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta)->paginate(6);
    }
    function getFacturas($desde, $hasta)
    {
        return $this->facturas()->with('ventaticket:id,consecutivo')
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta)
            ->whereNull('pre_factura_global_id')
            ->whereNotNull('facturado_en')
            ->paginate(6);
    }
    function facturasShow($facturaId, $type)
    {
        if ($type == 'global') {
            return PreFacturaGlobal::findOrFail($facturaId);
        }
        return  PreFactura::findOrFail($facturaId);
    }
    function createNewAlmacen($user, $name = 'Sucursal Matriz')
    {
        $newAlmacen = new Almacen();
        $newAlmacen->name = $name;
        $newAlmacen->organization_id = $this->id;
        $newAlmacen->save();
        $almacenService = new AlmacenService;
        $almacenService->attachAlmacenToTeamMembers($user, $newAlmacen->id);
    }
}
