<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComponent extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $with = ['product_hijo'];

     //relacion uno a muchos inversa
    
     public function product(){

        return $this->belongsTo('App\Models\Product');
    }

    public function product_hijo(){

        return $this->belongsTo('App\Models\Product');
    }
    
}
