<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentIntent extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'organization_id',
        'status',
        'payment_id',
        'collection_status',
        'payment_type',
        'merchant_order_id',
        'payable_id',
        'payable_type',
    ];
    public function payable()
    {
        return $this->morphTo();
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
