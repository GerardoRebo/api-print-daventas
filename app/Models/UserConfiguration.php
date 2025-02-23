<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserConfiguration extends Model
{
    use HasFactory;
    protected $guarded= [];
    public function user() {
        return $this->belongsTo('App\Models\User');
    }
    protected function features(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ? json_decode($value, true) : [],
        );
    }
}
