<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ejercicio extends Model
{
    use HasFactory;
    protected $guarded=[];

    //Relacion Uno a muchos inversa

    public function user()
    {

        return $this->belongsTo('App\Models\User');
    }
    
    
}
