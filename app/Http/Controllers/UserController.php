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
        Auth::user()->givePermissionTo('Supprimer les notes');
        return view('users',['utilisateurs'=>User::all()]);
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
        User::create([
            'login'=>$request->login,
            'firstname'=>$request->firstname,
            'lastname'=>$request->lastname,
            'telephone'=>$request->telephone,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
        ]);
        return to_route('utilisateurs.index')->with('Utilisateur ajouté');
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
        User::find($id)->update([
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
        User::find($id)->delete();
        return to_route('utilisateurs.index')->with('succes','Utilisateur supprimé avec succès');
    }
}
