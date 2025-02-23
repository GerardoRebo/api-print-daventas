<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    protected $guarded=[];

    //RELACIÓN UNO A MUCHOS
    public function abonos(){
        return $this->hasMany('App\Models\Abono');
    }

    public function ventatickets(){
        return $this->hasMany('App\Models\Ventaticket');
    }

    public function pagos_mixtos(){
        return $this->hasMany('App\Models\PagosMixto');
    }
    //relacion uno a uno
    public function cuenta(){

        return $this->belongsTo('App\Models\Cuenta');
    }
    //:todo esta mal
    public function deuda(){

        return $this->belongsTo('App\Models\Deuda');
    }
}
