<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FoliosUtilizado extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function facturable(): MorphTo
    {
        return $this->morphTo();
    }
}
