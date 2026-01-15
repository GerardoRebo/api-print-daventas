<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pivot model for the many-to-many relationship between users and organizations.
 * This model handles the multi-org support with shard assignment and per-organization roles.
 * 
 * @property int $id
 * @property int $user_id
 * @property int $organization_id
 * @property int $shard_id
 * @property string $shard_connection
 * @property int|null $assigned_by
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property bool $active
 * @property string|null $role_name - Role in this specific organization
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserOrganization extends Model
{
    use HasFactory;

    protected $table = 'user_organizations';
    protected $fillable = [
        'user_id',
        'organization_id',
        'shard_id',
        'shard_connection',
        'assigned_by',
        'assigned_at',
        'active',
        'role_name',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Get the user that this relationship belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the organization this user is assigned to.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * Get the user who assigned this user to the organization.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get only active relationships.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get by specific shard.
     */
    public function scopeByShard($query, $shardId)
    {
        return $query->where('shard_id', $shardId);
    }
}
