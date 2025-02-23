<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    //RELACIÓN UNO A MUCHOS
    public function organizations(){
        return $this->hasMany('App\Models\Organization');
    }
    public function plan_prices(){
        return $this->hasMany('App\Models\PlanPrice');
    }
    protected $guarded=[];
}
