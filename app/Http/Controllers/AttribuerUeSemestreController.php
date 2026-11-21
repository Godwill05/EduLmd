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
        /* Avant : AttrUeClasse::all() + relations déréférencées ligne par
           ligne dans la vue (anneeacademique, semestre, ue, categorieUe,
           importance) → 1 078 ms et 643 Ko. Eager loading + pagination. */
        $items = AttrUeClasse::query()
            ->with(['anneeacademique', 'semestre', 'ue', 'categorieUe', 'importance'])
            ->orderByDesc('Id_Attr_ue_Sem')
            ->paginate(50)
            ->withQueryString();

        return view('attribuerUeSemestre', [
            'items' => $items,
            /* Les 5 listes déroulantes étaient construites dans la boucle des
               lignes : elles sont désormais chargées une seule fois. */
            'anneesAcademiques' => \App\Models\AnneeAcademique::orderByDesc('id_Annee_Acad')->get(),
            'constantesImportance' => \App\Models\ConstanteImportance::orderBy('libelleImp')->get(),
            'semestresEtude' => \App\Models\SemestreEtude::orderBy('Nom_Classe')->get(),
            'ues' => \App\Models\Ue::orderBy('libelleUe')->get(),
            'categoriesUe' => \App\Models\CategorieUe::orderBy('Lib_Cat_Ue')->get(),
        ]);
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
