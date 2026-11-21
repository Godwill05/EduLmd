<?php

namespace App\Http\Controllers;

use App\Models\AttrUeClasse;
use App\Models\Cours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoursController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cours',['items'=>DB::select("SELECT numeroProg,datedebut,datefin,heureDebutCours,heureFinCours,attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,codeGroupe,MasseHoraire,prog_cours.* FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,prog_cours  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND prog_cours.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND prog_cours.codeEcu=attr_ecue_ue.codeEcu ORDER BY Nom_Classe ASC")]);
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
        $cbn=new Carbon();
        $cours=new Cours();
        $request->validate([
            "anneeEtude" => "required",
            "anneeAcademique" => "required",
            "semestreEtude" => "required",
            "ue" => "required",
            "ecue" => "required",
            "dateDebut" => "required",
            "dateFin" => "required",
            "salle" => "required",
            "heureDebut" => "required",
            "heureFin" => "required",
        ]);
        extract($this->extraireEntrees());
        $heureDebut=new Carbon($heureDebut);
        $heureFin=new Carbon($heureFin);
        $dateDebut=$cbn->createFromDate($dateDebut);
        $dateFin=$cbn->createFromDate($dateFin);
        $nbJours=$dateFin->diffInWeekDays($dateDebut);
        $cours->datedebut=$dateDebut->format('Y-m-d');
        $cours->datefin=$dateFin->format('Y-m-d');
        $cours->heureDebutCours=$heureDebut;
        $cours->heureFinCours=$heureFin;
        $cours->codeEcu=$ecue;
        $nbHeures=round((($heureFin->diffInMinutes($heureDebut))/60)*$nbJours,2);
        $cours->MHoraireProg=$nbHeures;
        $cours->Id_Attr_ue_Sem=$ue;
        $cours->codeSalle=$salle;
        $cours->codeGroupe=$groupePedagogique;
        $cours->login=Auth::user()->login;
        $cours->save();
        return to_route('cours.index')->with('succes','Cours programmé !');
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
        return view('coursModif',['cours'=>Cours::find($id)]);
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
        $cbn=new Carbon();
        $cours=Cours::find($id);
        $request->validate([
            "dateDebut" => "required",
            "dateFin" => "required",
            "salle" => "required",
            "heureDebut" => "required",
            "heureFin" => "required",
        ]);
        extract($this->extraireEntrees());
        $heureDebut=new Carbon($heureDebut);
        $heureFin=new Carbon($heureFin);
        $dateDebut=$cbn->createFromDate($dateDebut);
        $dateFin=$cbn->createFromDate($dateFin);
        $nbJours=$dateFin->diffInWeekdays($dateDebut);
        $cours->datedebut=$dateDebut->format('Y-m-d');
        $cours->datefin=$dateFin->format('Y-m-d');
        $cours->heureDebutCours=$heureDebut;
        $cours->heureFinCours=$heureFin;
        $nbHeures=round((($heureFin->diffInMinutes($heureDebut))/60)*$nbJours,2);
        $cours->MHoraireProg=$nbHeures;
        $cours->codeSalle=$salle;
        $cours->codeGroupe=$groupePedagogique;
        $cours->login=Auth::user()->login;
        $cours->save();
        return to_route('cours.index')->with('succes','Cours modifié !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Cours::destroy($id);
        return to_route('cours.index')->with('succes','Cours supprimée !');
    }
}
