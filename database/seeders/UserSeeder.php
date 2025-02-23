<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Gerardo Rebolledo Montiel',
            'email' => 'gerardo.rebo93@gmail.com',
            'password' => bcrypt('12345678'),
        ])->assignRole('SuperAdmin');
        User::create([
            'name' => 'Fernanda Itzel López',
            'email' => 'fer.lpzmdn@outlook.com',
            'password' => bcrypt('12345678'),
        ])->assignRole('SuperAdmin');
        
        $user=User::create([
            'name' => 'Noel Mujica',
            'email' => 'renoel0053@hotmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Owner');
        DB::table('responsables')->insert([
            'user_id' => $user->id,
            'organization_id' => 1
        ]);
        User::create([
            'name' => 'Caja1Arbolito',
            'email' => 'caja1arbolito@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja1Lamina',
            'email' => 'caja1lamina@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja1Plasticos',
            'email' => 'caja1plasticos@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja2Plasticos',
            'email' => 'caja2plasticos@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja1MpMujica',
            'email' => 'caja1mpmujica@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja2MpMujica',
            'email' => 'caja2mpmujica@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja3MpMujica',
            'email' => 'caja3mpmujica@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja1Colmena',
            'email' => 'caja1colmena@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'Caja2Colmena',
            'email' => 'caja2colmena@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        User::create([
            'name' => 'CajaAdminColmena',
            'email' => 'cajaadmincolmena@gmail.com',
            'password' => bcrypt('12345678'),
            'organization_id' => 1,
        ])->assignRole('Cajero');
        
    }
}
