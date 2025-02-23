<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaticketRetentionTaxes extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function ventaticket()
    {
        return $this->belongsTo(Ventaticket::class);
    }
    function retention_rule()
    {
        return $this->belongsTo(RetentionRule::class);
    }
}
