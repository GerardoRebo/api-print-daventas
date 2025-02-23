<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Auth\Data\RegisterDBData as DBData;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //test 200 status only
    //:todo devoluciones con impuestos y descuentos
    public function test_register()
    {
        $dbData = new DBData;
        $dbData->cargarDatos();

        $response = $this->postJson(
            route('auth.register', [
                'name' => 'test',
                'email' => 'example@example.com',
                'password' => '12345678',
                'password_confirmation' => '12345678',
                'organization_name' => 'name',
            ])
        );
        // $response->dump();
        $response->assertStatus(200);
    }
}
