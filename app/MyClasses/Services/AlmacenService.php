<?php

declare(strict_types=1);

namespace App\MyClasses\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AlmacenService
{
    protected $userService;
    public function __construct()
    {
        $this->userService = new UserService;
    }

    public function attachAlmacen(User $attacher, int $userEnviado, int $almacenId)
    {
        $usuarios = $this->userService->getOrganizationUsers($attacher->organization_id);
        if (!$usuarios->contains('id', $userEnviado)) return;
        $userEnviado = User::find($userEnviado);
        $almacens = $userEnviado->almacens;
        if ($almacens->contains('id', $almacenId)) return true;
        $userEnviado->almacens()->attach([$almacenId]);
        $userEnviado->refresh();
        $this->putCache($userEnviado);
        return true;
    }

    public function attachAlmacenToTeamMembers(User $attacher, int $almacen)
    {
        $users = $this->userService->getTeamMembers($attacher->organization_id);
        foreach ($users as $user) {
            $user->load('almacens');
            $user->almacens()->attach([$almacen]);
            $user->refresh();
            $this->putCache($user);
            return true;
        }
    }
    public function putCache(User $user): void
    {
        Cache::tags(['orgAlmacens:' . $user->active_organization_id])->put('userAlmacens:' . $user->id, $user->almacens);
    }
    public function getMyAlmacens($user)
    {
        $almacens = Cache::tags(['orgAlmacens:' . $user->active_organization_id])->get('userAlmacens:' . $user->id);
        if ($almacens) {
            return $almacens;
        };
        $almacens = $user->almacens;
        Cache::tags(['orgAlmacens:' . $user->active_organization_id])->put('userAlmacens:' . $user->id, $almacens);
        return $almacens;
    }
}
