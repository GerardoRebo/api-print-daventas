<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RoleSeeder::class);
        $this->call(OrganizationMujicaSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ProductosMujicaSeeder::class);
        $this->call(AlmacensMujicaSeeder::class);
        $this->call(ProveedorsMujicaSeeder::class);
        $this->call(DepartamentosMujicaSeeder::class);
        $this->call(ProductsPreciosMujicaSeeder::class);
        $this->call(ProductPreciosDosMujicaSeeder::class);
        $this->call(ProductsExistenciasMujicaSeeder::class);
        $this->call(ProductsComponentsMujicaSeeder::class);
        $this->call(ProductsDepartamentosMujicaSeeder::class);
        // $this->call(ProductsProveedorsMujicaSeeder::class);
        // \App\Models\User::factory(10)->create();
    }
}
