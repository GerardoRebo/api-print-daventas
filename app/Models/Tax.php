<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];

    //Relación muchos a muchos
    public function products()
    {
        return $this->belongsToMany('App\Models\Product');
    }
}
