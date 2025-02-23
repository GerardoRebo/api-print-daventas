<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role1= Role::create(['name' => 'SuperAdmin']);
        $role2= Role::create(['name' => 'Owner']);
        $role3= Role::create(['name' => 'Admin']);
        $role4= Role::create(['name' => 'Cajero']);

        Permission::create(['name' => 'codigos.show'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'codigos.attach'])->syncRoles([$role1, $role2, $role3, $role4]);
        
        Permission::create(['name' => 'clients.getallclients'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'clients.show'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'clients.setcliente'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'clients.allclients'])->syncRoles([$role1, $role2, $role3, $role4 ]);
        Permission::create(['name' => 'clients.store'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'clients.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'clients.delete'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'clients.search'])->syncRoles([$role1, $role2, $role3, $role4]);



        Permission::create(['name' => 'puntoventa.specific'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.misventas'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.setpendiente'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.pendientes'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.register'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.ventaticket'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.destroyarticulo'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.guardarventa'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.realizardevolucion'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.borrarticket'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.artiulos'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.getexistencias'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.asignaralmacen'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.setnombreticket'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'puntoventa.cancelarventa'])->syncRoles([$role1, $role2, $role3, $role4]);
        
        
        Permission::create(['name' => 'movimientos.specific'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.mismovimientos'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.setpendiente'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.setproveedor'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.setmovimiento'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.pendientes'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.register'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.ventaticket'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.destroyarticulo'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.guardar'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.borrarticket'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.artiulos'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.getexistencias'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.asignaralmacen'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.setnombreticket'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.cancelarmovimiento'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'movimientos.cambiaprecio'])->syncRoles([$role1, $role2, $role3, $role4]);
        
        
        
        Permission::create(['name' => 'products.search'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.searchkeyword'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.historials'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.searchkeywordsimple'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.agregarcomponente'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.getcomponents'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.showextend'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.showextended'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.searchcode'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.searchcodesimple'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.ajustar'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.ajustarminmax'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.index'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.store'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.show'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'products.destroy'])->syncRoles([$role1, $role2, $role3, $role4]);



        Permission::create(['name' => 'proveedors.agregarp'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'proveedors.showpp'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'proveedors.search'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'proveedors.index'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'proveedors.store'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'proveedors.show'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'proveedors.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'proveedors.destroy'])->syncRoles([$role1, $role2, $role3, $role4]);


        Permission::create(['name' => 'organizacions.search'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.searchAlmacen'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.misuser'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.misalmacens'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.registeruser'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.setuserorganization'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.registeralmacen'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.detachuser'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.detachalmacen'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.index'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.store'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.show'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'organizacions.destroy'])->syncRoles([$role1, $role2, $role3, $role4]);
        
        
        
        Permission::create(['name' => 'almacens.search'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'almacens.index'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'almacens.store'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'almacens.show'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'almacens.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'almacens.destroy'])->syncRoles([$role1, $role2, $role3, $role4]);


        Permission::create(['name' => 'departamentos.agregard'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'departamentos.showpd'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'departamentos.search'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'departamentos.index'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'departamentos.store'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'departamentos.show'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'departamentos.update'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'departamentos.destroy'])->syncRoles([$role1, $role2, $role3, $role4]);
        
        
        Permission::create(['name' => 'cortes.habilitarcaja'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'cortes.getturnoactual'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'cortes.realizarcorte'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'cortes.realizarmovimiento'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'cortes.getconceptos'])->syncRoles([$role1, $role2, $role3, $role4]);
        
        Permission::create(['name' => 'creditos.getcreditos'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'creditos.getdeudas'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'creditos.realizarabono'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'creditos.getabonos'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'creditos.getsaldo'])->syncRoles([$role1, $role2, $role3, $role4]);
        
    }
}
