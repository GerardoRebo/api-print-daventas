<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class SingleImage extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['url'];

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn() => asset(Storage::url($this->path))
        );
    }
    public function single_imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
