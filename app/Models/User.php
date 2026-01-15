<?php

namespace App\Models;

use App\MyClasses\PuntoVenta\CreateCotizacion;
use App\MyClasses\PuntoVenta\CreateVentaTicket;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    protected $with = ['roles', 'configuration'];
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail());
    }
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'external_id',
        'activo',
        'external_provider',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['active_organization_id'];

    //relacion uno a uno
    public function configuration()
    {
        return $this->hasOne('App\Models\UserConfiguration');
    }

    /**
     * Get all organizations this user belongs to.
     * Uses many-to-many through user_organizations pivot table.
     */
    public function organizations()
    {
        return $this->belongsToMany(
            'App\Models\Organization',
            'user_organizations',
            'user_id',
            'organization_id'
        )->withPivot('shard_id', 'shard_connection', 'assigned_by', 'assigned_at', 'active', 'role_name')
            ->withTimestamps();
    }

    /**
     * Get only active organizations for this user.
     */
    public function activeOrganizations()
    {
        return $this->organizations()->wherePivot('active', true);
    }

    /**
     * Get the shard assignment for this user.
     */
    public function userShard()
    {
        return $this->hasOne(UserShard::class, 'user_id');
    }

    /**
     * Get user's organization assignments through pivot model.
     */
    public function userOrganizations()
    {
        return $this->hasMany(UserOrganization::class, 'user_id');
    }

    /**
     * Get the currently active organization ID from session/context.
     * This is set during login or context switching.
     */
    public function getActiveOrganizationIdAttribute()
    {
        // Try to get from session/cache first
        if (auth()->check() && auth()->user()->id === $this->id) {
            return session('active_organization_id') ?? $this->getDefaultOrganizationId();
        }

        // If not authenticated, return first active org
        return $this->getDefaultOrganizationId();
    }

    /**
     * Get the user's first active organization (used as default).
     */
    public function getDefaultOrganizationId()
    {
        return $this->activeOrganizations()->first()?->id;
    }

    /**
     * Get the organization object for the currently active organization.
     */
    public function getActiveOrganization()
    {
        if ($activeOrgId = $this->active_organization_id) {
            return $this->organizations()->find($activeOrgId);
        }
        return $this->organizations()->first();
    }

    /**
     * Check if user belongs to an organization.
     */
    public function belongsToOrganization($organizationId): bool
    {
        return $this->organizations()
            ->wherePivot('organization_id', $organizationId)
            ->exists();
    }

    /**
     * Get the role this user has in a specific organization.
     * 
     * @param int $organizationId
     * @return string|null
     */
    public function getRoleInOrganization($organizationId): ?string
    {
        return $this->userOrganizations()
            ->where('organization_id', $organizationId)
            ->first()?->role_name;
    }

    /**
     * Check if user has a specific role in a specific organization.
     * 
     * @param string $roleName
     * @param int $organizationId
     * @return bool
     */
    public function hasRoleInOrganization(string $roleName, $organizationId): bool
    {
        return $this->userOrganizations()
            ->where('organization_id', $organizationId)
            ->where('role_name', $roleName)
            ->exists();
    }

    /**
     * Assign a role to this user in a specific organization.
     * 
     * @param string $roleName
     * @param int $organizationId
     * @return void
     */
    public function assignRoleInOrganization(string $roleName, $organizationId): void
    {
        $this->userOrganizations()
            ->where('organization_id', $organizationId)
            ->update(['role_name' => $roleName]);
    }

    /**
     * @deprecated Use organizations() instead
     */
    public function organization()
    {
        // For backwards compatibility, return the active organization
        return $this->getActiveOrganization();
    }

    function cuenta()
    {
        return $this->hasOne(Cuenta::class);
    }
    //RELACIÓN UNO A MUCHOS
    public function corteoperacions()
    {
        return $this->hasMany('App\Models\CorteOperacion');
    }

    public function ordencompras()
    {
        return $this->hasMany('App\Models\OrdenCompra');
    }

    public function devoluciones()
    {
        return $this->hasMany('App\Models\Devolucione');
    }

    public function turnos()
    {
        return $this->hasMany('App\Models\Turno');
    }

    public function abonos()
    {
        return $this->hasMany('App\Models\Abono');
    }
    public function inventario_ajustes()
    {
        return $this->hasMany('App\Models\InventarioAjuste');
    }

    public function inventario_recibos()
    {
        return $this->hasMany('App\Models\InventarioRecibo');
    }

    public function ventatickets()
    {
        return $this->hasMany('App\Models\Ventaticket');
    }
    public function inventario_historials()
    {
        return $this->hasMany('App\Models\InventarioHistorial');
    }
    public function histories()
    {
        return $this->hasMany('App\Models\History');
    }
    public function ejercicios()
    {
        return $this->hasMany('App\Models\Ejercicio');
    }
    public function diarios()
    {
        return $this->hasMany('App\Models\Diario');
    }
    public function invitations()
    {
        return $this->hasMany('App\Models\Invitation');
    }

    //relación muchos a muchos

    public function lifecycleEmailEvents()
    {
        return $this->hasMany(LifecycleEmailEvent::class);
    }

    public function almacens()
    {
        return $this->belongsToMany('App\Models\Almacen');
    }

    public function hasStageSent(string $stage): bool
    {
        return $this->lifecycleEmailEvents()->where('stage', $stage)->exists();
    }

    /**
     * Get almacenes where this user can exercise their faculties in a specific organization.
     * Filters the many-to-many relationship by organization_id.
     * 
     * @param int|null $organizationId - If null, uses the active organization
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAlmacenesByOrganization($organizationId = null)
    {
        $orgId = $organizationId ?? $this->active_organization_id;

        if (!$orgId) {
            return collect();
        }

        return Almacen::where('almacens.organization_id', $orgId)
            ->get();
    }

    /**
     * Convenience method: Get almacenes in the active organization.
     * Equivalent to: $user->getAlmacenesByOrganization($user->active_organization_id)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMyOrgAlmacens()
    {
        return $this->getAlmacenesByOrganization();
    }
    function createTurno(): Turno
    {
        $activeOrgId = $this->active_organization_id;
        return Turno::create([
            'operacion_id_inicio' => null,
            'operacion_id_fin' => null,
            'user_id' => $this->id,
            'organization_id' => $activeOrgId,
            'inicio_en' => getMysqlTimestamp($this->configuration?->time_zone),
            'termino_en' => null,
            'dinero_inicial' => 0,
            'acumulado_ventas' => 0,
            'acumulado_entradas' => 0,
            'acumulado_salidas' => 0,
            'acumulado_ganancias' => 0,
            'ventas_efectivo' => 0,
            // 'ventas_tarjeta' => 0,
            'ventas_credito' => 0,
            'efectivo_al_cierre' => 0,
            'compras' => 0,
            'abonos_efectivo' => 0,
            'devoluciones_ventas_efectivo' => 0,
            'devoluciones_ventas_credito' => 0,
            'devoluciones_abonos_efectivo' => 0,
            'numero_ventas' => 0,
            'abonos_tarjeta' => 0,
            'comisiones_tarjeta' => 0,
            'devoluciones_ventas' => 0,
        ]);
    }
    function getLatestTurno(): ?Turno
    {
        $activeOrgId = $this->active_organization_id;
        return Turno::where('user_id', $this->id)
            ->where('organization_id', $activeOrgId)
            ->where('inicio_en', '!=', null)
            ->where('termino_en', null)->first();
    }
    public function getCompraticketAlmacenCliente()
    {
        $activeOrgId = $this->active_organization_id;
        $ordencompra = OrdenCompra::where('organization_id', $activeOrgId)
            ->where('estado', 'B')->where('pendiente', 0)
            ->where('user_id', $this->id)->first();
        if (!$ordencompra) {
            return $this->createOrdenCompra();
        }
        return $this->ticket = $ordencompra;
    }
    function createOrdenCompra(): OrdenCompra
    {
        $activeOrgId = $this->active_organization_id;
        $cuenta = $this->getConsecutivo();
        return OrdenCompra::create([
            'organization_id' => $activeOrgId,
            'user_id' => $this->id,
            'proveedor_id' => null,
            'consecutivo' => $cuenta,
            'almacen_origen_id' => null,
            'almacen_destino_id' => null,
            'tipo' => null,
            'pendiente' => 0,
            'cancelada_en' => null,
            'enviada_en' => null,
            'recibida_en' => null,
            'estado' => 'B',
            'utilidad_enviado' => 0,
            'impuestos_enviado' => 0,
            'subtotal_enviado' => 0,
            'total_enviado' => 0,
            'utilidad_recibido' => 0,
            'impuestos_recibido' => 0,
            'subtotal_recibido' => 0,
            'total_recibido' => 0,
            'notas' => '',
            'total_articulos' => 0,
        ]);
    }
    public function getVentaticketAlmacenCliente(): Ventaticket
    {
        $activeOrgId = $this->active_organization_id;
        $ventaticket = Ventaticket::where('user_id', $this->id)
            ->where('organization_id', $activeOrgId)
            ->where('pendiente', 0)
            ->where('esta_abierto', 1)
            ->first();

        if (!$ventaticket) {
            $puntoVentaLogic = new CreateVentaTicket;
            $ventaticket = $puntoVentaLogic->creaTicket($this);
        }
        return $ventaticket;
    }
    public function getCotizacionAlmacenCliente(): Cotizacion
    {
        $activeOrgId = $this->active_organization_id;
        $ventaticket = Cotizacion::with('ventaticket')->where('esta_abierto', 1)
            ->where('organization_id', $activeOrgId)
            ->where('pendiente', 0)
            ->where('user_id', $this->id)->first();

        if (!$ventaticket) {
            $puntoVentaLogic = new CreateCotizacion;
            $ventaticket = $puntoVentaLogic->creaCotizacion($this);
        }
        return $ventaticket;
    }
    function getConsecutivo(): int
    {
        $activeOrgId = $this->active_organization_id;
        $cuenta = 0;
        try {
            $cuenta =  Redis::incr('compra' . $activeOrgId);
        } catch (Exception $e) {
        }
        return $cuenta;
    }
    public function getUsersInMyOrg()
    {
        $activeOrgId = $this->active_organization_id;
        if (!$activeOrgId) {
            return collect();
        }
        return Cache::remember('org:' . $activeOrgId . ':users:', 172800, function () use ($activeOrgId) {
            return User::whereHas('userOrganizations', function ($q) use ($activeOrgId) {
                $q->where('organization_id', $activeOrgId)->where('active', true);
            })->get();
        });
    }
    public function getUsersOwners()
    {
        $users = $this->getUsersInMyOrg();

        $filtered = $users->filter(function ($value, $key) {
            return $value->roles->contains(function ($value, $key) {
                return $value->name == 'Owner';
            });
        });
        return $filtered;
    }
    public function getUsersAdmins()
    {
        $users = $this->getUsersInMyOrg();

        $filtered = $users->filter(function ($value, $key) {
            return $value->roles->contains(function ($value, $key) {
                return $value->name == 'Admin';
            });
        });
        return $filtered;
    }
    public function getUsersEncargados()
    {
        $users = $this->getUsersInMyOrg();

        $filtered = $users->filter(function ($value, $key) {
            return $value->roles->contains(function ($value, $key) {
                return $value->name == 'Encargado';
            });
        });
        return $filtered;
    }
    public function getUsersCajeros()
    {
        $users = $this->getUsersInMyOrg();

        $filtered = $users->filter(function ($value, $key) {
            return $value->roles->contains(function ($value, $key) {
                return $value->name == 'Cajero';
            });
        });
        return $filtered;
    }
    public function getUsersAccordingAlmacen($almacen)
    {
        $users = $this->getUsersInMyOrg();
        $activeOrgId = $this->active_organization_id;

        $filtered = $users->filter(function ($value, $key) use ($almacen, $activeOrgId) {
            $userAlmacens = $value->getAlmacenesByOrganization($activeOrgId);
            return $userAlmacens->contains('id', $almacen);
        });
        return $filtered;
    }
    function getLastVentaTicket()
    {
        $activeOrgId = $this->active_organization_id;
        return Ventaticket::where('esta_abierto', 0)
            ->where('organization_id', $activeOrgId)
            ->where('pendiente', 0)
            ->whereNotNull('pagado_en')
            ->where('user_id', $this->id)->latest()->first();
    }
    public function getReferralLinkAttribute()
    {
        return config('app.spa_url') . '/front/register?ref=' . $this->id . '&ref_type=dav';
    }
}
