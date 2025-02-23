<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FacturacionInfo extends Model
{
    use HasFactory;
    protected $guarded = [];
    /**
     * Get the parent infoable model (post or video).
     */
    public function infoable(): MorphTo
    {
        return $this->morphTo();
    }
}
