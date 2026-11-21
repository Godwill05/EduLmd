<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /* `Auth::user()->givePermissionTo(...)` accordait silencieusement
           la permission « Supprimer les notes » à quiconque ouvrait la
           page : chaque simple visiteuse ou visiteur gagnait un droit.
           Retiré. */
        $utilisateurs = User::with('roles')->orderBy('lastname')->orderBy('firstname')->get();

        $acteur = Auth::user();

        return view('users', [
            'utilisateurs' => $utilisateurs,
            'roles' => \App\Models\Role::query()->orderBy('order')->orderBy('name')->get(),
            'monNiveau' => \App\Support\Hierarchie::niveau($acteur),
            'roleAttribuables' => \App\Support\Hierarchie::rolesAttribuables($acteur)->pluck('id')->all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'login'=>'required',
            'firstname'=>'required',
            'lastname'=>'required',
            'telephone'=>'required',
            'email'=>'required',
            'password'=>'required'
        ]);
        $email=$request->input('email');
        $login=$request->input('login');
        $existingEmail=User::where('email',$email)->first();
        if ($existingEmail) {
            return back()->with('error','Cet email est déjà utilisé');
        }
        $existingLogin=User::where('login',$login)->first();
        if ($existingLogin) {
            return back()->with('error','Ce nom d\'utilisateur est déjà utilisé');
        }
        $role = null;
        if ($request->filled('role_id')) {
            $role = \App\Models\Role::find($request->input('role_id'));
            if (! $role || ! \App\Support\Hierarchie::peutAttribuer(Auth::user(), $role)) {
                return back()->withInput()->with('echec',
                    'Vous ne pouvez pas attribuer ce rôle : il est au moins aussi élevé que le vôtre.');
            }
        }

        $utilisateur = User::create([
            'login'=>$request->login,
            'firstname'=>$request->firstname,
            'lastname'=>$request->lastname,
            'telephone'=>$request->telephone,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
        ]);

        if ($role) {
            $utilisateur->assignRole($role);
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return to_route('utilisateurs.index')->with('succes','Utilisateur ajouté' . ($role ? ' avec le rôle « ' . $role->name . ' »' : '') . ' !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $cible = User::findOrFail($id);
        if (! \App\Support\Hierarchie::peutGererCible(Auth::user(), $cible)) {
            return back()->with('echec', 'Vous ne pouvez pas modifier ce compte.');
        }
        $cible->update([
            'login'=>$request->login,
            'lastname'=>$request->lastname,
            'firstname'=>$request->firstname,
            'telephone'=>$request->telephone,
            'email'=>$request->email,
        ]);
        return to_route('utilisateurs.index')->with('succes','Utilisateur modifié');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $cible = User::findOrFail($id);
        if (! \App\Support\Hierarchie::peutGererCible(Auth::user(), $cible)) {
            return back()->with('echec', 'Vous ne pouvez pas supprimer ce compte.');
        }
        $cible->delete();
        return to_route('utilisateurs.index')->with('succes','Utilisateur supprimé avec succès');
    }
}
