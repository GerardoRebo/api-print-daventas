<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArticuloFile extends Model
{
    use HasFactory;
    protected $fillable = ['filename', 'path', 'mime_type', 'size', 'd_id_animation_id'];
    protected $appends = ['url'];

    public function articulo()
    {
        return $this->belongsTo(VentaticketArticulo::class);
    }
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn() => asset(Storage::url($this->path))
        );
    }
}
