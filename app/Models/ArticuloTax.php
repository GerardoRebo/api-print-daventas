<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticuloTax extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function tax(){
        return $this->belongsTo('App\Models\Tax');
    }
    public function ventaticket_articulo()
    {
        return $this->belongsTo('App\Models\VentaticketArticulo');
    }
    public function product()
    {
        return $this->hasOneThrough('App\Models\Product', 'App\Models\VentaticketArticulo');
    }
}
