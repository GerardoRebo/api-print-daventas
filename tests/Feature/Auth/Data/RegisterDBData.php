<?php

namespace Tests\Feature\Auth\Data;

use Spatie\Permission\Models\Role;

class RegisterDBData
{
    public function cargarDatos()
    {
        Role::create(['name' => 'Owner']);
        Role::create(['name' => 'Admin']);
    }
}
