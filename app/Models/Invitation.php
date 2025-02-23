<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['organization', 'user'];

    //relacion uno a muchos inversa

    public function organization()
    {

        return $this->belongsTo('App\Models\Organization');
    }
    public function user(){
        return $this->belongsTo('App\Models\User');
    }
}
