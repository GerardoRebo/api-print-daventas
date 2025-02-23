<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;
    protected $guarded=[];

    //Relación muchos a muchos
    public function products(){
        return $this->belongsToMany('App\Models\Product');
    }
}
