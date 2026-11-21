<?php

namespace App\Http\Controllers;

use App\Models\AttrUeClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttribuerUeSemestreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('attribuerUeSemestre',['items'=>AttrUeClasse::all()]);
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
            'anneeAcademique'=>'required',
            'semestre'=>'required',
            'ue'=>'required',
            'categorie'=>'required',
            'importanceUe'=>'required',
            'seuil'=>'required',
            'credit'=>'required'
        ]);
        AttrUeClasse::create([
            'id_Annee_Acad'=>$request->anneeAcademique,
            'id_S_Gr_Spe'=>$request->semestre,
            'codeUe'=>$request->ue,
            'Id_Cat_Ue'=>$request->categorie,
            'idImportance'=>$request->importanceUe,
            'Seuil_Ue'=>$request->seuil,
            'Credit'=>$request->credit,
            'Auteur'=>Auth::user()->login,
        ]);
        return to_route('attribuer-ue-semestre.index')->with('succes','Attribution effectuée');
    }

    public function edit($id)
    {
        $item=AttrUeClasse::find($id);
        return view('modifAttrUeSemestre',compact('item'));
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
            'anneeAcademique'=>'required',
            'semestre'=>'required',
            'ue'=>'required',
            'categorie'=>'required',
            'importanceUe'=>'required',
            'seuil'=>'required',
            'credit'=>'required'
        ]);
        AttrUeClasse::find($id)->update([
            'id_Annee_Acad'=>$request->anneeAcademique,
            'id_S_Gr_Spe'=>$request->semestre,
            'codeUe'=>$request->ue,
            'Id_Cat_Ue'=>$request->categorie,
            'idImportance'=>$request->importanceUe,
            'Seuil_Ue'=>$request->seuil,
            'Credit'=>$request->credit,
        ]);
        return to_route('attribuer-ue-semestre.index')->with('succes','Attribution altérée');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attrUeSem=AttrUeClasse::find($id);
        if(!$attrUeSem->attrEcueUes->isEmpty())
            return to_route('attribuer-ue-semestre.index')->with('echec','Suppression impossible. Des ECUEs sont liées à cette attribution');
        return to_route('attribuer-ue-semestre.index')->with('succes','Attribution retirée');
    }
}
