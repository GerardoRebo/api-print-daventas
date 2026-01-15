<?php

namespace App\Http\Controllers;

use App\Exceptions\OperationalException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (!$user->activo) {
            throw new OperationalException("Usuario no activo dentro del sistema", 1);
        }

        // Check if user has any active organizations
        if (!$user->activeOrganizations()->exists()) {
            throw new OperationalException("Usuario no tiene organizaciones asignadas", 2);
        }

        // Set the user's first active organization as the default
        $defaultOrg = $user->activeOrganizations()->first();

        // Create token and set active organization in session
        $token = $user->createToken($request->device_name)->plainTextToken;
        session(['active_organization_id' => $defaultOrg->id]);

        // Return token and default organization info
        return response()->json([
            'token' => $token,
            'user' => $user,
            'active_organization_id' => $defaultOrg->id,
            'organizations' => $user->activeOrganizations()->select('organizations.id', 'organizations.name', 'organizations.slug_name')->get(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json('logout', 201);
    }
}
