<?php

namespace App\Http\Controllers;

use App\Models\Mention;
use App\Models\Specialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpecialiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('specialites',['mentions'=>Mention::all(),'specialites'=>Specialite::all()]);
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
            'codeSpecialite'=>'required',
            'libelleSpecialite'=>'required',
            'mentionSpecialite'=>'required',
        ]);
        $specialite=new Specialite();
        $specialite->codeSpe=$request->codeSpecialite;
        $specialite->libelleSpe=$request->libelleSpecialite;
        $specialite->codeMention=$request->mentionSpecialite;
        $specialite->Auteur=Auth::user()->login;
        $specialite->save();
        return to_route('specialites.index')->with('succes','Spécialité enregistrée !');
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
        $specialite=Specialite::find($id);
        $specialite->codeSpe=$request->codeSpecialite;
        $specialite->libelleSpe=$request->libelleSpecialite;
        $specialite->codeMention=$request->mentionSpecialite;
        $specialite->save();
        return to_route('specialites.index')->with('succes','Spécialité modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Specialite::find($id)->delete();
        return to_route('specialites.index')->with('succes','Spécialité supprimée avec succès');
    }
}
