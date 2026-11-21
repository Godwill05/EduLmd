<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    public function index()
    {
        if(request()->isMethod('get')){
            return view('notes');
        }elseif (request()->isMethod('post')) {
            $request=request();
            $evaluations=DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,`numeroEval`,evaluation.`dateEval`,`heureDebutEval`,`dureeEval`,`codeGroupe`,evaluation.codTypEval,libTypEval FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval  AND attr_ue_classe.`id_Annee_Acad`='$request->anneeAcademique' AND attr_ue_classe.`id_S_Gr_Spe`='$request->semestreEtude'  AND attr_ue_classe.Id_Attr_ue_Sem='$request->ue'  AND evaluation.codeEcu='$request->ecue' ORDER BY `dateEval`,`heureDebutEval` ASC");
            /* $evaluations=Evaluation::whereRelation('ueSem','id_Annee_Acad',request()->anneeAcademique)
            ->where('Id_Attr_ue_Sem',request()->ue)
            ->where('codeEcu',request()->ecue)->get(); */
            return view('notes',compact('evaluations'));
        }
    }
    public function insererNotes()
    {
        $_notes=request()->except('_token','numeroEvaluation');
        foreach ($_notes as $numeroInscription => $note) {
            if($note){
                Note::updateOrCreate([
                    'numeroEval'=>intval(request()->numeroEvaluation),
                    'numeroInscription'=>$numeroInscription
                ],[
                    'Note'=>$note,
                    'login'=>Auth::user()->login
                ]);
            }
        }
    }
}
