<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    use HasFactory;
    protected $guarded=[];
    //RELACIÓN UNO A MUCHOS Inversa
    public function plan(){
        return $this->belongsTo('App\Models\Plan');
    }
}
