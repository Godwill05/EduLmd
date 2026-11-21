<?php

namespace App\Http\Controllers;

use App\Models\AttrEcueUe;
use App\Models\AttrUeClasse;
use App\Models\Ecue;
use App\Models\Enseignant;
use App\Models\ModeEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttribuerEcueUeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /* 1 282 requêtes SQL : chaque ligne déréférençait ueSemestre, ue,
           semestre, anneeacademique, ecue et professeur. Eager loading. */
        $items = AttrEcueUe::query()
            ->with(['ueSemestre.ue', 'ueSemestre.semestre', 'ueSemestre.anneeacademique', 'ecue', 'professeur'])
            ->paginate(50)
            ->withQueryString();

        /* La liste déroulante « UE – semestre » parcourt AttrUeClasse::all()
           et déréférence ue / semestre / anneeacademique pour chaque ligne :
           415 × 3 requêtes. Chargées ici une fois pour toutes. */
        $ueSemestres = AttrUeClasse::query()
            ->with(['ue', 'semestre', 'anneeacademique'])
            ->orderByDesc('Id_Attr_ue_Sem')
            ->get();

        return view('attribuerEcueUe', [
            'items' => $items,
            'ueSemestres' => $ueSemestres,
            'ecues' => Ecue::orderBy('libelleEcu')->get(),
            'enseignants' => Enseignant::orderBy('nomProf')->get(),
            'modesEvaluation' => ModeEvaluation::orderBy('libEvaMode')->get(),
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
            'ueSemestre'=>'required',
            'ecue'=>'required',
            'enseignant'=>'required',
            'modeEvaluation'=>'required',
            'pedagogique'=>'required',
            'ponderation'=>'required',
            'poidsTpDevoir'=>'required',
            'poidsPartiel'=>'required',
            'seuilEcue'=>'required',
            'masseHoraire'=>'required',
        ]);
        AttrEcueUe::create([
            'Id_Attr_ue_Sem'=>$request->ueSemestre,
            'codeEcu'=>$request->ecue,
            'numeroProf'=>$request->enseignant,
            'Eva_Mode'=>$request->modeEvaluation,
            'Ponderation'=>($request->ponderation)/100,
            'pedagogique'=>$request->pedagogique,
            'PoidsDev'=>($request->poidsTpDevoir)/100,
            'PoidsPart'=>($request->poidsPartiel)/100,
            'Seuil_Ecu'=>$request->seuilEcue,
            'MasseHoraire'=>$request->masseHoraire,
            'MasseHoraireExec'=>0,
            'Observation'=>$request->observations,
            'Auteur'=>Auth::user()->login
        ]);
        return to_route('attribuer-ecue-ue.index')->with('succes','Attribution effectuée');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $item=AttrEcueUe::where('Id_Attr_ue_Sem',request()->uesem)
        ->where('codeEcu',request()->ecUe)
        ->first();
        return view('modifAttrEcueUe',compact('item'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $mhe=AttrEcueUe::where('Id_Attr_ue_Sem',request()->us)->where('codeEcu',request()->ec)->first()->MasseHoraireExec;
        AttrEcueUe::where('Id_Attr_ue_Sem',request()->us)
        ->where('codeEcu',request()->ec)
        ->delete();
        AttrEcueUe::create([
            'Id_Attr_ue_Sem'=>$request->ueSemestre,
            'codeEcu'=>$request->ecue,
            'numeroProf'=>$request->enseignant,
            'Eva_Mode'=>$request->modeEvaluation,
            'Ponderation'=>($request->ponderation)/100,
            'pedagogique'=>$request->pedagogique,
            'PoidsDev'=>($request->poidsTpDevoir)/100,
            'PoidsPart'=>($request->poidsPartiel)/100,
            'Seuil_Ecu'=>$request->seuilEcue,
            'MasseHoraire'=>$request->masseHoraire,
            'MasseHoraireExec'=>$mhe,
            'Observation'=>$request->observations,
            'Auteur'=>Auth::user()->login
        ]);
        /* AttrEcueUe::where('Id_Attr_ue_Sem',request()->ueSemestre)
        ->where('codeEcu',request()->ecue)
        ->update([
            'Id_Attr_ue_Sem'=>$request->ueSemestre,
            'codeEcu'=>$request->ecue,
            'numeroProf'=>$request->enseignant,
            'Eva_Mode'=>$request->modeEvaluation,
            'Ponderation'=>($request->ponderation)/100,
            'pedagogique'=>$request->pedagogique,
            'PoidsDev'=>($request->poidsTpDevoir)/100,
            'PoidsPart'=>($request->poidsPartiel)/100,
            'Seuil_Ecu'=>$request->seuilEcue,
            'MasseHoraire'=>$request->masseHoraire,
            'Observation'=>$request->observations,
        ]); */
        return to_route('attribuer-ecue-ue.index')->with('succes','Attribution modifiée');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        AttrEcueUe::where('Id_Attr_ue_Sem',request()->uesem)
        ->where('codeEcu',request()->ecUe)
        ->delete();
        return to_route('attribuer-ecue-ue.index')->with('succes','Attribution retirée');
    }
}
