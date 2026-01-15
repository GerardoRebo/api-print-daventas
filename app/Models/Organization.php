<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use App\MyClasses\Factura\FacturaService;
use App\MyClasses\Services\AlmacenService;
use App\Services\Cfdi\CfdiUtilsGlobalBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['url'];

    //relación muchos a muchos
    /* public function users(){
        return $this->belongsToMany('App\Models\User');
    } */

    /**
     * Get all users assigned to this organization (many-to-many).
     * Users can belong to multiple organizations.
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(
            'App\Models\User',
            'user_organizations',
            'organization_id',
            'user_id'
        )->withPivot('shard_id', 'shard_connection', 'assigned_by', 'assigned_at', 'active')
            ->withTimestamps();
    }

    /**
     * Get all active user assignments for this organization.
     */
    public function activeUsers()
    {
        return $this->assignedUsers()->wherePivot('active', true);
    }

    /**
     * Get the user organization pivot records for this org.
     */
    public function userOrganizations()
    {
        return $this->hasMany(UserOrganization::class, 'organization_id');
    }

    //RELACIÓN UNO A MUCHOS (kept for backwards compatibility, but data is now in user_organizations)
    public function invitations()
    {
        return $this->hasMany('App\Models\Invitation');
    }
    public function users()
    {
        // For backwards compatibility, alias to assignedUsers
        return $this->assignedUsers();
    }
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
    public function almacens()
    {
        return $this->hasMany('App\Models\Almacen');
    }
    public function facturas()
    {
        return $this->hasMany(PreFactura::class);
    }
    public function facturasGlobales()
    {
        return $this->hasMany(PreFacturaGlobal::class);
    }
    public function folios_utilizados()
    {
        return $this->hasMany(FoliosUtilizado::class);
    }
    public function organization_plans()
    {
        return $this->hasMany(OrganizationPlan::class);
    }
    public function venta_plans()
    {
        return $this->hasMany(VentaPlan::class);
    }
    public function system_folios()
    {
        return $this->hasMany(SystemFolio::class);
    }
    //RELACIÓN UNO A MUCHOS Inversa
    public function facturacion_info(): MorphOne
    {
        return $this->morphOne(FacturacionInfo::class, 'infoable');
    }
    public function latestFoliosUtilizado()
    {
        return $this->hasOne(FoliosUtilizado::class)->latestOfMany();
    }
    public function latestSystemFolio()
    {
        return $this->hasOne(SystemFolio::class)->latestOfMany();
    }
    public function image(): MorphOne
    {
        return $this->morphOne(SingleImage::class, 'single_imageable');
    }
    public function latestOrganizationPlan(): HasOne
    {
        return $this->hasOne(OrganizationPlan::class)->latestOfMany();
    }
    public function latestVentaPlan(): HasOne
    {
        return $this->hasOne(VentaPlan::class)->latestOfMany();
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
    function get_active_users_count()
    {
        return $this->users()->where('activo', true)->count();
    }
    function get_active_almacens_count()
    {
        return $this->almacens()->where('is_active', true)->count();
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
            return $item->name;
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
            ->where('retention', false)
            ->whereNull('ventatickets.facturado_en')
            ->groupBy('ventatickets.id', 'consecutivo', 'pagado_en', 'ventatickets.total')  // Group by ventaticket's unique columns
            ->get();
    }
    function emitirFacturaGlobal($facturaId, $formaPago, $usoCfdi, $serie, $clavePrivadaLocal, $year, $mes)
    {
        $user = $this->user;
        $this->facturaValidations($clavePrivadaLocal);

        /** @var PreFacturaGlobal $preFactura */
        $preFactura = PreFacturaGlobal::with('articulos.ventaticket')->findOrFail($facturaId);
        $cfdiUtils = new CfdiUtilsGlobalBuilder($preFactura);
        $xml = $cfdiUtils->createFromVenta($serie, $formaPago, $year, $mes);

        $jsonPath = 'facturaTmp/XmlDesdePhpSinTimbrar.xml';
        Storage::disk('local')->put($jsonPath, $xml);

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
                        if ((float) $ticket->impuesto_retenido > 0) {
                            throw new OperationalException("Producto con impuesto retenido, no procede en factura global", 1);
                        }
                        $preFactura = $ticket->createPreFacturaForGlobal($preFacturaGlobal);
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
                        if ($ticket->retention) {
                            $errors[] = "El ticket de venta " . $ticket->consecutivo . " tiene impuestos retenidos, no se puede facturar globalmente";
                        }
                        foreach ($ticket->ventaticket_articulos as $articulo) {
                            if ($articulo->product->ObjetoImp === null) {
                                $errors[] = "Objeto de impuesto no seleccionado para el producto: " . $articulo->product->name;
                            }
                            if ($articulo->product->ObjetoImp == "01") {
                                continue;
                            }
                            if (!$articulo->product->taxes->count() && $articulo->product->ObjetoImp == "02") {
                                $errors[] = "Impuestos no configurados en el producto: " . $articulo->product->name;
                            }
                            if (!$articulo->product->c_ClaveUnidad) {
                                $errors[] = "Clave unidad no configurada en el producto: " . $articulo->product->name;
                            }
                            if (!$articulo->product->c_claveProdServ) {
                                $errors[] = "Clave Producto Servicio no configurada en el product" . $articulo->product->name;
                            }
                        };
                    }
                });
        }
        if (count($errors)) {
            throw new OperationalException(implode(", ",  $errors), 1);
        }
    }

    private function facturaValidations($clavePrivadaLocal)
    {
        $facturaHelper = new FacturaService;
        $facturaData = $facturaHelper->getGlobalData($this);
        foreach ($facturaData as $key => $value) {
            if ($key == 'pfx_path') {
                continue;
            }
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
            ->orderByDesc('id')
            ->paginate(10);
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
    function assignPlan(PlanPrice $planPrice)
    {
        $lastOrganizationPlan = $this->organization_plans()->where('is_active', true)->first();

        if ($lastOrganizationPlan && $lastOrganizationPlan->plan->id != $planPrice->plan->id) {
            $baseDate = now();
        } else if ($lastOrganizationPlan) {
            // Calculamos nuevo vencimiento
            $baseDate = $lastOrganizationPlan  && $lastOrganizationPlan->ends_at && $lastOrganizationPlan->ends_at->isFuture()
                ? $lastOrganizationPlan->ends_at
                : now();
        }
        $this->makeOldPlansInactive();
        $this->organization_plans()->create([
            'plan_id' => $planPrice->plan->id,
            'started_at' => now(),
            'ends_at' => $planPrice->meses
                ? $baseDate->copy()->addMonths($planPrice->meses)
                : null,
            'is_active' => true,
        ]);
        $this->setSystemFolios($planPrice->plan->timbres_mensuales);
    }
    function makeOldPlansInactive()
    {
        $this->organization_plans()->update(['is_active' => false]);
    }
    function resetDefaultAssets()
    {
        // Desactiva todos los almacenes excepto el primero
        $firstAlmacenId = $this->almacens()->orderBy('id')->value('id');

        $this->almacens()
            ->where('id', '!=', $firstAlmacenId)
            ->update(['is_active' => false]);

        // Desactiva todos los usuarios excepto el primero
        $firstUserId = $this->users()->orderBy('id')->value('id');

        $this->users()
            ->where('id', '!=', $firstUserId)
            ->update(['activo' => false]);
    }
    function assignDefaultPlan()
    {
        $freePlan = Plan::where('is_free', true)->where('is_default', true)->first();
        if (!$freePlan) {
            throw new OperationalException("Ha ocurrido un error con los planes, por favor contacta a soporte tecnico", 1);
        }
        $freePlanPrice = $freePlan->plan_prices()->whereNull('meses')->first();

        if (!$freePlanPrice) {
            throw new OperationalException("Plan gratis no encontrado", 1);
        }

        $this->makeOldPlansInactive();
        $this->organization_plans()->create([
            'plan_id' => $freePlanPrice->plan->id,
            'started_at' => now(),
            'ends_at' => null,
            'is_active' => true,
        ]);
    }
    function assignInitialPlan()
    {
        $freePlan = Plan::where('is_free', true)->where('is_default', false)->first();
        if (!$freePlan) {
            throw new OperationalException("Ha ocurrido un error con los planes, por favor contacta a soporte tecnico", 1);
        }
        $planPrice = $freePlan->plan_prices()->first();

        if (!$planPrice) {
            throw new OperationalException("Plan inicial no encontrado", 1);
        }

        $this->makeOldPlansInactive();
        $endsDate = $planPrice->meses ? now()->addMonths($planPrice->meses) : null;
        $this->organization_plans()->create([
            'plan_id' => $planPrice->plan->id,
            'started_at' => now(),
            'ends_at' => $endsDate,
            'is_active' => true,
        ]);
    }
    function setSystemFolios($quantityFolios)
    {
        if (!$quantityFolios) return;
        $this->system_folios()->create([
            'cantidad' => $quantityFolios,
            'saldo' => $quantityFolios,
            'facturable_type' => 'Reseteo mensual',
            'facturable_id' => 1,
        ]);
    }
    function getOverallTimbresCount()
    {
        $systemFoliosCount = $this->latestSystemFolio?->saldo ?? 0;
        $usedFoliosCount = $this->latestFoliosUtilizado?->saldo ?? 0;
        return $systemFoliosCount + $usedFoliosCount;
    }
}
