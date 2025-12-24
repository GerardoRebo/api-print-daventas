<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserConfiguration;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('configuration');
        return [
            $user,
            $user->getRoleNames()
        ];
    }
    public function getAll(Request $request)
    {
        if (!$request->user()->hasRole('SuperAdmin')) return;
        return User::orderByDesc('id')->get();
    }
    public function getUserRol(Request $request)
    {
        $user = $request->input('userId');
        $user = User::find($user);
        return $user->getRoleNames()->first();
    }
    public function actualizarInfo(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name' => ['required', 'string'],
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string'],
        ]);
        $direccion = $request->direccion;
        if ($direccion == null) {
            $direccion = "";
        }
        $telefono = $request->telefono;
        if ($telefono == null) {
            $telefono = "";
        }
        DB::table('users')->where('id', $user->id)->update([
            'direccion' => $direccion,
            'telefono' => $telefono,
            'name' => $request->name,
        ]);
    }
    public function cambiaConstrasena(Request $request)
    {

        $request->validate([
            'passwordA' => ['required', 'min:8'],
            'password' => ['required', 'min:8', 'confirmed']
        ]);

        $user = auth()->user();

        if (!$user || !Hash::check($request->passwordA, $user->password)) {
            throw ValidationException::withMessages([
                'passwordA' => ['The provided credentials are incorrect.'],
            ]);
        }
        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($request->password),
        ]);
    }
    public function getAllRoles(Request $request)
    {
        return Role::whereNotIn('name', ['SuperAdmin'])->get();
    }

    public function asignarRol(Request $request)
    {
        $rolName = request()->input('rolName');
        $user = request()->input('userId');
        $user = User::find($user);
        if ($user->hasRole('Owner')) return;
        if ($rolName === 'SuperAdmin') return;
        if ($rolName === 'Owner') return;
        $user->syncRoles($rolName);
    }
    public function getCountNotf()
    {
        return auth()->user()->unreadNotifications()->count();
    }
    public function getNotifications(Request $request)
    {
        $notifications = auth()->user()->unreadNotifications()->get();
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        return $notifications;
    }
    public function getAllNotifications(Request $request)
    {

        $dfecha = request()->input('dfecha');
        $hfecha = request()->input('hfecha');

        $fecha = new DateTime($hfecha);
        $fecha->add(new DateInterval('P1D'));
        $notifications = auth()->user()->notifications()
            ->whereBetween('created_at', [$dfecha, $fecha])->paginate(12);
        return $notifications;
    }
    public function getUserInfo(Request $request)
    {
        return User::findOrFail(request()->input('userId'))->name;
    }
    public function searchTimezones(Request $request)
    {
        return DateTimeZone::listIdentifiers();
    }
    public function setTimezone(Request $request)
    {
        $request->validate([
            "id" => "required|string"
        ]);
        $user = auth()->user();
        $timezone = $request->input('id');
        // Get a list of valid timezone identifiers
        $validTimezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

        if (!in_array($timezone, $validTimezones)) {
            throw new Exception("NoTz", 1);
        }
        UserConfiguration::updateOrCreate(
            ['user_id' => $user->id],
            ['time_zone' => $timezone],
        );
        $user = $user->load('configuration');
        return [
            $user,
            $user->getRoleNames()
        ];
    }
    public function updateFeature(Request $request)
    {
        $request->validate([
            "key" => "required|string",
            "value" => "sometimes|nullable|in:true,false,1,0"
        ]);

        $user = auth()->user()->load('configuration');

        if ($user->configuration) {
            $features = [];
            if ($user->configuration->features) {
                $features = $user->configuration->features;
            }
            $features[$request->key] = $request->value;
            $user->configuration->features = json_encode($features);
            $user->configuration->save();
        } else {
            $user->configuration()->create([
                "features" => json_encode([$request->key => $request->value])
            ]);
        }
        return [
            $user,
            $user->getRoleNames()
        ];
    }
}
