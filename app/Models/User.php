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


    //relacion uno a uno
    public function configuration()
    {
        return $this->hasOne('App\Models\UserConfiguration');
    }
    public function organization()
    {
        return $this->belongsTo('App\Models\Organization');
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

    public function almacens()
    {
        return $this->belongsToMany('App\Models\Almacen');
    }
    function getMyOrgAlmacens()
    {
        return Almacen::where('organization_id', $this->organization_id)->get();
    }
    function createTurno(): Turno
    {
        return Turno::create([
            'operacion_id_inicio' => null,
            'operacion_id_fin' => null,
            'user_id' => $this->id,
            'organization_id' => $this->organization_id,
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
        return Turno::where('user_id', $this->id)
            ->where('inicio_en', '!=', null)
            ->where('termino_en', null)->first();
    }
    public function getCompraticketAlmacenCliente()
    {
        $ordencompra = OrdenCompra::where('organization_id', $this->organization_id)
            ->where('estado', 'B')->where('pendiente', 0)
            ->where('user_id', $this->id)->first();
        if (!$ordencompra) {
            return $this->createOrdenCompra();
        }
        return $this->ticket = $ordencompra;
    }
    function createOrdenCompra(): OrdenCompra
    {
        $cuenta = $this->getConsecutivo();
        return OrdenCompra::create([
            'organization_id' => $this->organization_id,
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
        $ventaticket = Ventaticket::where('user_id', $this->id)
            ->where('organization_id', $this->organization_id)
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
        $ventaticket = Cotizacion::where('esta_abierto', 1)
            ->where('organization_id', $this->organization_id)
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
        $cuenta = 0;
        try {
            $cuenta =  Redis::incr('compra' . $this->organization_id);
        } catch (Exception $e) {
        }
        return $cuenta;
    }
    public function getUsersInMyOrg()
    {
        $org = $this->organization_id;
        return Cache::remember('org:' . $org . ':users:', 172800, function () use ($org) {
            return User::where('organization_id', $org)->get();
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
        $filtered = $users->filter(function ($value, $key) use ($almacen) {
            return $value->almacens->contains(function ($value, $key) use ($almacen) {
                return $value->id == $almacen;
            });
        });
        return $filtered;
    }
    function getLastVentaTicket()
    {
        return Ventaticket::where('esta_abierto', 0)
            ->where('organization_id', $this->organization_id)
            ->where('pendiente', 0)
            ->whereNotNull('pagado_en')
            ->where('user_id', $this->id)->latest()->first();
    }
}
