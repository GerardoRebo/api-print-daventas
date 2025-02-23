<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['url'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => asset(Storage::url($this->path))
        );
    }
}
