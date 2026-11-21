<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnseignantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('enseignants',['items'=>Enseignant::all()]);
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
            'nom'=>'required',
            'prenom'=>'required',
            'dateNaissance'=>'required',
            'lieuNaissance'=>'required',
            'sexe'=>'required',
            'telephone'=>'required',
            'email'=>'required',
            'typeProfesseur'=>'required',
            'nationalite'=>'required',
        ]);
        Enseignant::create([
            'nomProf'=>$request->nom,
            'prenomsProf'=>$request->prenom,
            'DateNais'=>$request->dateNaissance,
            'LieuNais'=>$request->lieuNaissance,
            'Sexe'=>$request->sexe,
            'telProf'=>$request->telephone,
            'emailProf'=>$request->email,
            'codTypProf'=>$request->typeProfesseur,
            'codeNationalite'=>$request->nationalite,
            'Auteurs'=>Auth::user()->login
        ]);
        return to_route('enseignants.index')->with('succes','Enseignant enregistré !');
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
        $request->validate([
            'nom'=>'required',
            'prenom'=>'required',
            'dateNaissance'=>'required',
            'lieuNaissance'=>'required',
            'sexe'=>'required',
            'telephone'=>'required',
            'email'=>'required',
            'typeProfesseur'=>'required',
            'nationalite'=>'required',
        ]);
        Enseignant::find($id)->update([
            'nomProf'=>$request->nom,
            'prenomsProf'=>$request->prenom,
            'DateNais'=>$request->dateNaissance,
            'LieuNais'=>$request->lieuNaissance,
            'Sexe'=>$request->sexe,
            'telProf'=>$request->telephone,
            'emailProf'=>$request->email,
            'codTypProf'=>$request->typeProfesseur,
            'codeNationalite'=>$request->nationalite,
            'Auteurs'=>Auth::user()->login
        ]);
        return to_route('enseignants.index')->with('succes','Enseignant modifié !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Enseignant::find($id)->delete();
        return to_route('enseignants.index')->with('succes','Enseignant supprimé !');
    }
}
