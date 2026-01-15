<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * Append custom attributes to model
     */
    protected $appends = ['es_apto_para_cfdi'];

    /**
     * Check if cliente has complete fiscal data for CFDI stamping
     */
    public function getEsAptoParaCfdiAttribute()
    {
        return !empty($this->rfc) &&
            !empty($this->razon_social) &&
            !empty($this->regimen_fiscal) &&
            !empty($this->codigo_postal);
    }

    //RELACIÓN UNO A MUCHOS
    public function abonos()
    {
        return $this->hasMany('App\Models\Abono');
    }

    public function ventatickets()
    {
        return $this->hasMany('App\Models\Ventaticket');
    }

    public function pagos_mixtos()
    {
        return $this->hasMany('App\Models\PagosMixto');
    }
    //relacion uno a uno
    public function cuenta()
    {

        return $this->belongsTo('App\Models\Cuenta');
    }
    //:todo esta mal
    public function deuda()
    {

        return $this->belongsTo('App\Models\Deuda');
    }
}
