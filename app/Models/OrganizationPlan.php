<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationPlan extends Model
{
    use HasFactory;
    protected $casts = [
        'ends_at' => 'datetime',
    ];
    protected $fillable = [
        'organization_id',
        'plan_id',
        'ends_at',
        'is_active',
    ];
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
