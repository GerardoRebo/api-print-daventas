<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventHistorial extends Model
{
    use HasFactory;
    protected $with = [];
    protected $guarded=[];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('d-m-Y H:i');
    }
    //relacion uno a muchos inversa
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }
    public function almacen()
    {
        return $this->belongsTo('App\Models\Almacen');
    }
}
