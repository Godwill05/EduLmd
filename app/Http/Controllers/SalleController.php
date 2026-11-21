<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('salles',['items'=>Salle::all()]);
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
            'codeSalle'=>'required',
            'salle'=>'required',
            'nbPlacesCours'=>'required',
            'nbPlacesCompositions'=>'required',
        ]);
        $model=new Salle();
        $model->codeSalle=$request->codeSalle;
        $model->Lib_Salle=$request->salle;
        $model->nombrePlaceCours=$request->nbPlacesCours;
        $model->nombrePlaceCompo=$request->nbPlacesCompositions;
        $model->Auteur=Auth::user()->login;
        $model->save();
        return to_route('salles.index')->with('succes','Salle enregistrée !');
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
            'codeSalle'=>'required',
            'salle'=>'required',
            'nbPlacesCours'=>'required',
            'nbPlacesCompositions'=>'required',
        ]);
        $model=Salle::find($id);
        $model->codeSalle=$request->codeSalle;
        $model->Lib_Salle=$request->salle;
        $model->nombrePlaceCours=$request->nbPlacesCours;
        $model->nombrePlaceCompo=$request->nbPlacesCompositions;
        $model->save();
        return to_route('salles.index')->with('succes','Salle modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Salle::find($id)->delete();
        return to_route('salles.index')->with('succes','Salle supprimée !');
    }
}
