<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $request=DB::select('SELECT evaluation.created_at, attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecuea,libelleEcu,attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,`numeroEval`,evaluation.`dateEval`,`heureDebutEval`,`dureeEval`,`codeGroupe`,evaluation.codTypEval,libTypEval FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval ORDER BY created_at DESC');
        return view('evaluations',['items'=>$request]);
    }

    public function insererNotes()
    {
        $_notes=request()->except('_token','numeroEvaluation');
        foreach ($_notes as $numeroInscription => $note) {
            if($note){
                Note::updateOrCreate([
                    'numeroEval'=>intval(request()->numeroEvaluation),
                    'numeroInscription'=>intval($numeroInscription)
                ],[
                    'Note'=>$note,
                    'login'=>Auth::user()->login
                ]);
            }
        }
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
            "date"=>'required',
            'heureDebut'=>'required',
            'ue'=>'required',
            'ecue'=>'required',
            'type'=>'required',
            'duree'=>'required',
        ]);
        Evaluation::create([
            'dateEval'=>\Carbon\Carbon::parse($request->date)->format('Y-m-d'),
            'heureDebutEval'=>$request->heureDebut,
            'Id_Attr_ue_Sem'=>$request->ue,
            'codeEcu'=>$request->ecue,
            'codTypEval'=>$request->type,
            'dureeEval'=>$request->duree,
            'login'=>Auth::user()->login
        ]);
        return to_route('evaluations.index')->with('succes','Evaluation enregistrée !');
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
        return view('modifEvaluation',['evaluation'=>Evaluation::find($id)]);
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
        Evaluation::find($id)->update([
            'dateEval'=>$request->date,
            'heureDebutEval'=>$request->heureDebut,
            'Id_Attr_ue_Sem'=>$request->ue,
            'codeEcu'=>$request->ecue,
            'codTypEval'=>$request->type,
            'dureeEval'=>$request->duree,
            'codeGroupe'=>$request->groupePedagogique
        ]);
        return to_route('evaluations.index')->with('succes','Evaluation modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Evaluation::destroy($id);
        return to_route('evaluations.index')->with('succes','Evaluation supprimée !');
    }
}
