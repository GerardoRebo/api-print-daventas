<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetentionRule extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function tax()
    {
        return $this->belongsTo('App\Models\Tax');
    }
}
