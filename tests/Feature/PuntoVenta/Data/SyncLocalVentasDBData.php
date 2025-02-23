<?php

namespace Tests\Feature\PuntoVenta\Data;

use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class SyncLocalVentasDBData extends RegisterDBData
{
    public function cargarDatos()
    {
        $this->organization= Organization::create([
            'name' => 'mpmujica',
            'estado' => 'Guerrero',
            'ciudad' => 'Iguala',
            'pais' => 'Mexico',
        ]);
        Sanctum::actingAs(
            /** @var User $user */
            $this->user = User::factory()->create(),
            ['*']
        );
        $this->createProducts();
        $this->turno = $this->user->createTurno();
    }
}
