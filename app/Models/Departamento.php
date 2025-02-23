<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $guarded =[];

    //RELACIÓN UNO A MUCHOS
    public function corteventapordeptos(){
        return $this->hasMany('App\Models\CorteVentaPorDepto');
    }

    public function ventaticket_articulos(){
        return $this->hasMany('App\Models\VentaticketArticulo');
    }
    //Relación muchos a muchos

    public function products(){
        return $this->belongsToMany('App\Models\Product');
    }

    public function corte_venta_depto_operacions(){
        return $this->belongsToMany('App\Models\CorteVentaDeptoOperacion');
    }
    public function abono_tickets(){
        return $this->hasMany('App\Models\AbonoTicket');
    }
}
