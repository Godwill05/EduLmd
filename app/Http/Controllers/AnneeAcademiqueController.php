<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnneeAcademiqueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('anneesAcademiques',['items'=>AnneeAcademique::all()]);
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
            'annee'=>'required',
            'dateDebut'=>'required',
            'dateFin'=>'required',
            'pourcentage'=>'required',
            'moyenne'=>'required',
            'moyenneUE'=>'required',
            'moyenneECUE'=>'required',
        ]);
        $model=new AnneeAcademique();
        $model->ANNEE_ACAD=$request->annee;
        $model->Date_Debut=$request->dateDebut;
        $model->Date_Fin=$request->dateFin;
        $model->Ptge_Validation=$request->pourcentage/100;
        $model->Moyenne=$request->moyenne;
        $model->MoyenneUE=$request->moyenneUE;
        $model->MoyenneECUE=$request->moyenneECUE;
        $model->MoyenneECUE=$request->moyenneECUE;
        $model->Auteur=Auth::user()->login;
        $model->save();
        return to_route('annees-academiques.index')->with('succes','Année académique enregistrée !');
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
            'annee'=>'required',
            'dateDebut'=>'required',
            'dateFin'=>'required',
            'pourcentage'=>'required',
            'moyenne'=>'required',
            'moyenneUE'=>'required',
            'moyenneECUE'=>'required',
        ]);
        $model=AnneeAcademique::find($id);
        $model->ANNEE_ACAD=$request->annee;
        $model->Date_Debut=$request->dateDebut;
        $model->Date_Fin=$request->dateFin;
        $model->Ptge_Validation=$request->pourcentage/100;
        $model->Moyenne=$request->moyenne;
        $model->MoyenneUE=$request->moyenneUE;
        $model->MoyenneECUE=$request->moyenneECUE;
        $model->MoyenneECUE=$request->moyenneECUE;
        $model->save();
        return to_route('annees-academiques.index')->with('succes','Année académique modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        AnneeAcademique::find($id)->delete();
        return to_route('annees-academiques.index')->with('succes','Année académique supprimée !');
    }
}
