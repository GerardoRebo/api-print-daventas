<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
    public function ventaticket()
    {
        return $this->belongsTo(Ventaticket::class);
    }
    public function ventaticket_articulo()
    {
        return $this->belongsTo(VentaticketArticulo::class);
    }
}
