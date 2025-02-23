<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed'],
            'organization_name' => "required|string|max:70",
        ]);
        $newOrganization = new Organization();
        $newOrganization->name = $request->name;
        $newOrganization->activa = true;
        $newOrganization->save();

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->organization_id = $newOrganization->id;
        $user->assignRole('Owner');
        $user->save();

        DB::table('responsables')->insert([
            'user_id' => $user->id,
            'organization_id' => $newOrganization->id
        ]);

        $newOrganization->createNewAlmacen($user);

        event(new Registered($user));
        return $user->createToken('desktop')->plainTextToken;
    }
}
