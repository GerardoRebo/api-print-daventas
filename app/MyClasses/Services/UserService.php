<?php
declare(strict_types=1);

namespace App\MyClasses\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserService  
{
    public function getOrganizationUsers(int $orgId):Collection
    {
        return User::where('organization_id', $orgId)->get();
    }
    public function getTeamMembers(int $orgId):Collection
    {
        return User::where('organization_id', $orgId)->role(['Owner', 'Admin'])->get();
    }
}
