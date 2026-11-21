<?php

namespace App\Http\Controllers;

use App\Exports\RecapAnnuelExport;
use App\Models\AnneeEtude;
use App\Models\AttrEcueUe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AnneeEtudeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('anneesEtudes',['anneesEtudes'=>AnneeEtude::all()]);
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
            'codeAnEtd'=>'required',
            'libelleAnEtd'=>'required',
        ]);
        $anEtd=new AnneeEtude();
        $anEtd->Code_Annee_Etude=$request->codeAnEtd;
        $anEtd->Libelle_AnEtude=$request->libelleAnEtd;
        $anEtd->Auteur=Auth::user()->login;
        $anEtd->save();
        return to_route('annees-etudes.index')->with('succes','Année d\'étude enregistrée !');
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
            'Code_Annee_Etude'=>'required',
            'Libelle_AnEtude'=>'required',
        ]);
        $anEtd=AnneeEtude::find($id);
        $anEtd->Code_Annee_Etude=$request->Code_Annee_Etude;
        $anEtd->Libelle_AnEtude=$request->Libelle_AnEtude;
        $anEtd->save();
        return to_route('annees-etudes.index')->with('succes','Année d\'étude modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        AnneeEtude::find($id)->delete();
        return to_route('annees-etudes.index')->with('succes','Année d\'étude supprimée avec succès');
    }
    public function recapitulatif() {
        extract(request()->except('_token'));
        $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;");
        if(empty($UE))
            die("Aucune UE n'est attribuée à cette filière. <a href='/attribuer-ue-semestre'>Attribuer</a>");
        $UE=array_map(function ($value) {
            return (array)$value;
        }, $UE);
        $rTaux = DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent,Moyenne,MoyenneUE FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;");
        $rTaux=array_map(function ($value) {
            return (array)$value;
        }, $rTaux);
        $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
        $tete=array_map(function ($value) {
            return (array)$value;
        }, $tete);
        $classe = $UE[0]['Nom_Classe'];
        $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
        $moyNor = $rTaux[0]['Moyenne'];
        $moyauto = $rTaux[0]['MoyenneUE'];
        //var_dump($UE[0]); exit;
    ?>
        <p style="text-align:left;color: #8a1f11; font-size: 15px">
            <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo '<b>' . $UE[$i]['ID_UE'] . "</b>===>" . $UE[$i]['libelleUe'] ?> <?php } ?>
            ---@@@--- V = Validé ---  V+R = Validé avec Reprise --- NV = Non Validé.
            </p>
            <table id="liste" class="display table-bordered" style="width:100%; font-size: 8px !important;" border="1" cellspacing="2" cellpadding="2">
            <thead class="thead">
                <tr style="background-color:#7BA7CE">
                    <th colspan="2" rowspan="2" style="text-align: center; font-size: 20px"> Année Etude:<br><?php echo $tete[0]['Libelle_AnEtude']; ?></th>
                    <?php for ($i = 0; $i < count($UE); $i++) { ?><th colspan="2" style="text-align:center;font-size: 11px;background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                    echo "#80C8F2";
                    } else {
                    echo "#667DFD";
                    } ?>"><?php echo $UE[$i]['ID_UE'] ?></th> <?php } ?>
                    <th style="text-align:center;font-size: 11px">Nombre<br />Crédits<br /> Capitalisés</th>
                    <th rowspan="2" style="text-align:center;">Nombre<br>d'UE<br> Validée(s)</th>
                    <th rowspan="2" style="text-align:center;">%age<br> d'UE</th>
                    <th rowspan="2">Moyenne</th>
                    <th rowspan="2">Décision</th>
                </tr>
                <tr style="background-color:#7BA7CE">
                    <?php $tCredit = 0;
                    for ($i = 0; $i < count($UE); $i++) { ?>
                        <th style="background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                        echo "#80C8F2";
                        } else {
                        echo "#667DFD";
                        } ?>">Credit</th>
                        <th style="text-align:center;background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                        echo "#80C8F2";
                        } else {
                        echo "#667DFD";
                        } ?>"><?php $tCredit += $UE[$i]['Credit'];
                        echo $UE[$i]['Credit'] ?></th>
                    <?php } ?>
                    <th style="text-align:center;"><?php echo $tCredit; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $selectedStudents = request('selected_students', request('etud', request('etudiant')));
                if (!empty($selectedStudents)) {
                    if (!is_array($selectedStudents)) {
                        $selectedStudents = [$selectedStudents];
                    }
                    $selectedStudents = array_values(array_filter($selectedStudents));
                }
                if (!empty($selectedStudents)) {
                    $mats = array_map(function($m){ return "'" . addslashes(trim($m)) . "'"; }, $selectedStudents);
                    $matsStr = implode(',', $mats);
                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and e.MATRICULE IN ($matsStr) ORDER BY noms,prenoms ASC;";
                } else if($typeEtudiant && $typeEtudiant!=='all') {
                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut = '$typeEtudiant' ORDER BY noms,prenoms ASC;";
                } else {
                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                }
                $listeE = DB::select($sql);
                $listeE=array_map(function ($value) {
                    return (array)$value;
                }, $listeE);
                $sv = $svr = $snv = 0;
                $resultat = [];
                for ($e = 0; $e < count($listeE); $e++) {
                    extract($listeE[$e]);
                    $ligne = [];
                    $ligne['matricule'] = $matricule;
                    $ligne['noms'] = $noms;
                    $ligne['prenoms'] = $prenoms;
                    $ligne['tCredit'] = $tCredit;
                    $ligne['etat'] = $etat;
                    $tValide = 0;
                    $nV = 0;
                    $pedagogique = 0;
                    $moyGene = 0;
                    for ($i = 0; $i < count($UE); $i++) {
                        $rattrapages = 0;
                        $noteRatr = 0;
                        $noteRepr = 0;
                        $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                        $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad=$anneeAcademique ORDER BY libelleEcu ASC");
                        $rc=array_map(function ($value) {
                            return (array)$value;
                        }, $rc);
                        $aColsEval = [];
                        for ($ii = 0; $ii < count($rc); $ii++) {
                            //        SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
                            $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='DEV';";
                            $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='PART';";
                            $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
                            $nbrRatr = DB::select($sqlNRatr);
                            $nbrRatr=array_map(function ($value) {
                                return (array)$value;
                            }, $nbrRatr);
                            $nbrRatr = (int)$nbrRatr[0]['nbre'];
                            $rattrapages += $nbrRatr;
                            // echo $rattrapages;
                            $nDev = DB::select($sqlDev);
                            $nDev=array_map(function ($value) {
                                return (array)$value;
                            }, $nDev);
                            $nDev = (int)$nDev[0]['nbre'];
                            $nPart = DB::select($sqlPart);
                            $nPart=array_map(function ($value) {
                                return (array)$value;
                            }, $nPart);
                            $nPart = (int)$nPart[0]['nbre'];
                            $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"]);
                        }

                        // calc moyenne
                        $moyenne = 0.0;
                        $moyenneReprise = 0.0;
                        foreach ($aColsEval as $key => $col) { //var_dump($col);
                            //DEVOIRS
                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $md = floatval($r[0]['MD']);
                            //PARTIEL
                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mp = floatval($r[0]['MD']);
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mR = floatval($r[0]['MD']);
                            $noteRatr += $mR;
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mRepr = floatval($r[0]['MD']);
                            $mecue = floatval((int)$md === 0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                            if(AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->pedagogique==1 && $mecue<AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->Seuil_Ecu) $pedagogique=1;
                            $mecue = max($mR, $mecue);
                            // $mecue = $mRepr!=0? max($mRepr, $mR): max($mR, $mecue);
                            $moyenne += $mecue * $col['ponderation'];
                            $moyenneReprise += $mRepr * $col['ponderation'];
                        }
                        //  $moyenne
                        if ($rattrapages >= 1 && $noteRatr > 0 && $moyenneReprise == 0.0) {
                            if ($moyenne > $UE[$i]['Seuil_Ue']) {
                                $moyenne = $UE[$i]['Seuil_Ue'];
                            }
                        } else {
                            if ($moyenneReprise > 0.0) {
                                $moyenne = $moyenneReprise;
                            }
                        }
                        $moyGene += $moyenne * $UE[$i]['Credit'];
                        $rue = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                        $ligne['moyenne'][] = [$UE[$i]['ID_UE'], $moyenne, $rue, $UE[$i]['Credit'], $UE[$i]['Seuil_Ue'], $UE[$i]['Id_Attr_ue_Sem']];
                        $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                        $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                    }
                    $ligne['moyGene'] = $moyGene;
                    $ligne['tValide'] = $tValide;
                    $ligne['nV'] = $nV;
                    $ligne['percentUE'] = 100 * $nV / count($UE);
                    $ligne['moyG'] = $tCredit === 0 ? 0 : $moyGene / $tCredit;
                    $resultat[] = $ligne;
                }
                //   var_dump($resultat[0]);
                for ($rr = 0; $rr < count($resultat); $rr++) {
                    $moyG = $resultat[$rr]['moyG'];
                    $resultat[$rr]['nV'] = 0;
                    $resultat[$rr]['tValide'] = 0;
                    for ($rrm = 0; $rrm < count($resultat[$rr]['moyenne']); $rrm++) {
                        $MoyUE = $resultat[$rr]['moyenne'][$rrm][1];
                        $oldVV = $resultat[$rr]['moyenne'][$rrm][2];
                        $cred = $resultat[$rr]['moyenne'][$rrm][3];
                        $vv = $moyG >= $moyNor && $MoyUE >= $moyauto ? 'V' : $oldVV;
                        if ($vv === 'V') {
                            $resultat[$rr]['nV']++;
                            $resultat[$rr]['tValide'] += intval($cred);
                        }
                        $resultat[$rr]['moyenne'][$rrm][2] = $vv;
                        $resultat[$rr]['percentUE'] = 100 * $resultat[$rr]['nV'] / count($UE);
                    }
                }
                //var_dump($resultat[0]);

                $sv = $svr = $snv = 0;
                for ($e = 0; $e < count($resultat); $e++) {
                    extract($resultat[$e]);
                    // var_dump($resultat[$e]);
                    // exit();

                ?>
                    <tr>
                        <td style="width: 60px;vertical-align: middle;"><?php echo $matricule ?></td>
                        <td style="width: 130px;height: 25px; vertical-align: middle"><?php echo "<b style='text-transform: uppercase'>$noms</b> $prenoms" ?></td>
                        <?php
                        for ($i = 0; $i < count($moyenne); $i++) { ?>
                            <td style="text-align:center;vertical-align: middle;font-size: 11px"> <?php echo (($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] >= $moyauto) && $moyG >= $moyNor) ? '<del>' . number_format($moyenne[$i][1], 2, ',', '') . '</del> <br> <b>' . number_format($moyenne[$i][4], 2, ',', '') . '</b>' : number_format($moyenne[$i][1], 2, ',', ''); ?></td>
                            <td style="text-align:center;vertical-align: middle"><?php if ($etat == "OUI") {
                            echo $moyenne[$i][2];
                            } else {
                            echo 'INV ';
                            } ?></td>
                        <?php
                            $KdeUE1 = $moyenne[$i][5];
                            $KdeUE2 = number_format($moyenne[$i][1], 2, '.', '');
                            $KdeUE3 = $moyenne[$i][2];
                            //echo $KdeUE;
                            //  echo "REPLACE INTO `recap_UE` (`MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE`, `Observation`, `Auteur`, `moment`) VALUES ('$matricule', '$anneeEtude', $KdeUE1, $KdeUE2 ,'$KdeUE3', '-', '".$_SESSION['slogin']."', CURRENT_TIMESTAMP)";
                            /* $requ = mysqli_query($connect->getVarConnexion(), "REPLACE INTO `recap_UE` (`MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE`, `Observation`, `Auteur`, `moment`) VALUES ('$matricule', '$anneeEtude', $KdeUE1, $KdeUE2 ,'$KdeUE3', '-', '" . $_SESSION['slogin'] . "', CURRENT_TIMESTAMP)"); */
                            // exit();
                        } ?>

                        <td style="text-align:center;vertical-align: middle"><?php if ($etat == "OUI") {
                        echo $tValide;
                        } else {
                        echo 'INV ';
                        } ?></td>
                        <td style="text-align:center;vertical-align: middle"><?php if ($etat == "OUI") {
                        echo '<b>' . $nV . '</b> sur <b>' . count($moyenne) . '</b>';
                        $ttcr = number_format(count($moyenne), 0, ',', ' ');
                        } else {
                        $ttcr = 0;
                        echo 'INV';
                        } ?></td>
                        <td style="text-align:center;vertical-align: middle"><?php if ($etat == "OUI") {
                        echo number_format($percentUE, 2, ',', ' ');
                        } else {
                        echo 'INV ';
                        } ?>%</td>
                        <td style="text-align:center;"><?php if ($etat == "OUI") {
                        echo number_format($moyG, 2, ',', ' ');
                        } else {
                        echo 'INV ';
                        } ?></td>
                        <td style="text-align:center;background-color:<?php if ($percentUE >= $taux && $percentUE < 100 && $pedagogique==0) {
                        echo "#FFFF00";
                        $svr += 1;
                        } else {
                        if ($percentUE < $taux || $pedagogique==1) {
                        echo "#F00020";
                        $snv += 1;
                        } else {
                        echo "#008000";
                        $sv += 1;
                        }
                        } ?>"><?php if ($etat == "OUI") {
                        if ($percentUE >= $taux && $percentUE < 100 && $pedagogique==0) {
                        echo "<b>V + R </b>";
                        $deci = "V + R";
                        } else {
                        if ($percentUE < $taux || $pedagogique==1) {
                        echo "<b>NV</b>";
                        $deci = "NV";
                        } else {
                        echo "<b>V</b>";
                        $deci = "V";
                        }
                        }
                        } else {
                        $deci = "INV";
                        }
                        // echo "REPLACE INTO recap_annuel(`MATRICULE`,`Code_Annee_Etude`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,Nbre_UE_Val,DecisionJury,`Observation`,`Auteur`,`Moment`) VALUES('$matricule','$anneeEtude',$anneeAcademique,$tValide,'$percentUE','$moyG',$nV,'$deci','$ttcr','".$_SESSION['slogin']."',CURRENT_TIMESTAMP);";
                        // $requeti=mysql_query("REPLACE INTO recap_annuel(`MATRICULE`,`Code_Annee_Etude`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,Nbre_UE_Val,DecisionJury,`Observation`,`Auteur`,`Moment`) VALUES('$matricule','$anneeEtude',$anneeAcademique,$tValide,'$percentUE','$moyG',$nV,'$deci','$ttcr','".$_SESSION['slogin']."',CURRENT_TIMESTAMP);");
                        $requeti = DB::insert('REPLACE INTO recap_annuel(`MATRICULE`,
                                                                                         `Code_Annee_Etude`,
                                                                                         `id_Annee_Acad`,
                                                                                         `Nbre_Cr_Capi`,
                                                                                         `Ptage_Obt`,
                                                                                         `MoyenneSem`,
                                                                                         Nbre_UE_Val,
                                                                                         DecisionJury,
                                                                                         `Observation`,
                                                                                         `Auteur`,
                                                                                         `Moment`)
                                                               VALUES(?,?,?,?,?,?,?,?,?,?,?)' , [$matricule,$anneeEtude,$anneeAcademique,$tValide,$percentUE,$moyG,$nV,$deci,$ttcr,Auth::user()->login,now()] );
                        ?></td>
                    </tr> <?php  }  ?>
            </tbody>
            </table>
        <?php
        exit();
    }
    public function exportExcel() {
        extract(request()->except('_token'));
        $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;");
        if(empty($UE))
            die("Aucune UE n'est attribuée à cette filière. <a href='/attribuer-ue-semestre'>Attribuer</a>");
        $UE=array_map(function ($value) {
            return (array)$value;
        }, $UE);
        $rTaux = DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent,Moyenne,MoyenneUE FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;");
        $rTaux=array_map(function ($value) {
            return (array)$value;
        }, $rTaux);
        $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
        $tete=array_map(function ($value) {
            return (array)$value;
        }, $tete);
        $classe = $UE[0]['Nom_Classe'];
        $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
        $moyNor = $rTaux[0]['Moyenne'];
        $moyauto = $rTaux[0]['MoyenneUE'];
        $data=[
            ['Année Etude',$tete[0]['Libelle_AnEtude']]
        ];
        $tCredit=0;
        array_push($data,['Matricule','Nom Complet']);
        for ($i = 0; $i < count($UE); $i++) {
            array_push($data[1],$UE[$i]['ID_UE'].'(Crédit : '.$UE[$i]['Credit'].')');
            $tCredit += $UE[$i]['Credit'];
        }
        array_push($data[1],"Nombre Crédits Capitalisés : $tCredit");
        array_push($data[1],"Nombre d'UE Validée(s)");
        array_push($data[1],"%age d'UE");
        array_push($data[1],"Moyenne");
        array_push($data[1],"Décision");
        if($typeEtudiant && $typeEtudiant!=='all') $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut = '$typeEtudiant' ORDER BY noms,prenoms ASC;";
                else if($typeEtudiant=='all' || $typeEtudiant==null) $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                $listeE = DB::select($sql);
                $listeE=array_map(function ($value) {
                    return (array)$value;
                }, $listeE);
                $sv = $svr = $snv = 0;
                $resultat = [];
                for ($e = 0; $e < count($listeE); $e++) {
                    extract($listeE[$e]);
                    $ligne = [];
                    $ligne['matricule'] = $matricule;
                    $ligne['noms'] = $noms;
                    $ligne['prenoms'] = $prenoms;
                    $ligne['tCredit'] = $tCredit;
                    $ligne['etat'] = $etat;
                    $tValide = 0;
                    $nV = 0;
                    $moyGene = 0;
                    for ($i = 0; $i < count($UE); $i++) {
                        $rattrapages = 0;
                        $noteRatr = 0;
                        $noteRepr = 0;
                        $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                        $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad=$anneeAcademique ORDER BY libelleEcu ASC");
                        $rc=array_map(function ($value) {
                            return (array)$value;
                        }, $rc);
                        $aColsEval = [];
                        for ($ii = 0; $ii < count($rc); $ii++) {
                            //        SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
                            $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='DEV';";
                            $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='PART';";
                            $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
                            $nbrRatr = DB::select($sqlNRatr);
                            $nbrRatr=array_map(function ($value) {
                                return (array)$value;
                            }, $nbrRatr);
                            $nbrRatr = (int)$nbrRatr[0]['nbre'];
                            $rattrapages += $nbrRatr;
                            // echo $rattrapages;
                            $nDev = DB::select($sqlDev);
                            $nDev=array_map(function ($value) {
                                return (array)$value;
                            }, $nDev);
                            $nDev = (int)$nDev[0]['nbre'];
                            $nPart = DB::select($sqlPart);
                            $nPart=array_map(function ($value) {
                                return (array)$value;
                            }, $nPart);
                            $nPart = (int)$nPart[0]['nbre'];
                            $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"]);
                        }

                        // calc moyenne
                        $moyenne = 0.0;
                        $moyenneReprise = 0.0;
                        foreach ($aColsEval as $key => $col) { //var_dump($col);
                            //DEVOIRS
                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $md = floatval($r[0]['MD']);
                            //PARTIEL
                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mp = floatval($r[0]['MD']);
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mR = floatval($r[0]['MD']);
                            $noteRatr += $mR;
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mRepr = floatval($r[0]['MD']);
                            $mecue = floatval((int)$md === 0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                            $mecue = max($mR, $mecue);
                            // $mecue = $mRepr!=0? max($mRepr, $mR): max($mR, $mecue);
                            $moyenne += $mecue * $col['ponderation'];
                            $moyenneReprise += $mRepr * $col['ponderation'];
                        }
                        //  $moyenne
                        if ($rattrapages >= 1 && $noteRatr > 0 && $moyenneReprise == 0.0) {
                            if ($moyenne > $UE[$i]['Seuil_Ue']) {
                                $moyenne = $UE[$i]['Seuil_Ue'];
                            }
                        } else {
                            if ($moyenneReprise > 0.0) {
                                $moyenne = $moyenneReprise;
                            }
                        }
                        $moyGene += $moyenne * $UE[$i]['Credit'];
                        $rue = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                        $ligne['moyenne'][] = [$UE[$i]['ID_UE'], $moyenne, $rue, $UE[$i]['Credit'], $UE[$i]['Seuil_Ue'], $UE[$i]['Id_Attr_ue_Sem']];
                        $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                        $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                    }
                    $ligne['moyGene'] = $moyGene;
                    $ligne['tValide'] = $tValide;
                    $ligne['nV'] = $nV;
                    $ligne['percentUE'] = 100 * $nV / count($UE);
                    $ligne['moyG'] = $tCredit === 0 ? 0 : $moyGene / $tCredit;
                    $resultat[] = $ligne;
                }
                //   var_dump($resultat[0]);
                for ($rr = 0; $rr < count($resultat); $rr++) {
                    $moyG = $resultat[$rr]['moyG'];
                    $resultat[$rr]['nV'] = 0;
                    $resultat[$rr]['tValide'] = 0;
                    for ($rrm = 0; $rrm < count($resultat[$rr]['moyenne']); $rrm++) {
                        $MoyUE = $resultat[$rr]['moyenne'][$rrm][1];
                        $oldVV = $resultat[$rr]['moyenne'][$rrm][2];
                        $cred = $resultat[$rr]['moyenne'][$rrm][3];
                        $vv = $moyG >= $moyNor && $MoyUE >= $moyauto ? 'V' : $oldVV;
                        if ($vv === 'V') {
                            $resultat[$rr]['nV']++;
                            $resultat[$rr]['tValide'] += intval($cred);
                        }
                        $resultat[$rr]['moyenne'][$rrm][2] = $vv;
                        $resultat[$rr]['percentUE'] = 100 * $resultat[$rr]['nV'] / count($UE);
                    }
                }

                $sv = $svr = $snv = 0;
                for ($e = 2; $e < count($resultat)+2; $e++) {
                    array_push($data,[]);
                    extract($resultat[$e-2]);
                    array_push($data[$e],$matricule);
                    array_push($data[$e],$noms.' '.$prenoms);
                    for ($i = 0; $i < count($moyenne); $i++) {
                        array_push($data[$e],(($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] >= $moyauto) && $moyG >= $moyNor) ?
                        number_format($moyenne[$i][1], 2, ',', '') . number_format($moyenne[$i][4], 2, ',', '') . ' | ' . (($etat=='OUI')?$moyenne[$i][2]:'INV '):
                        number_format($moyenne[$i][1], 2, ',', '') . ' | ' . (($etat=='OUI')?$moyenne[$i][2]:'INV '));
                    }
                    if ($etat == "OUI") {
                        array_push($data[$e],$tValide);
                        array_push($data[$e],$nV . ' sur ' . count($moyenne));
                        $ttcr = number_format(count($moyenne), 0, ',', ' ');
                        array_push($data[$e],number_format($percentUE, 2, ',', ' '));
                        array_push($data[$e],number_format($moyG, 2, ',', ' '));
                        if ($percentUE >= $taux && $percentUE < 100) {
                            array_push($data[$e],"V + R");
                        }else{
                            if ($percentUE < $taux) {
                                array_push($data[$e],"NV");
                            }else{
                                array_push($data[$e],"V");
                            }
                        }
                    } else {
                        array_push($data[$e],'INV ');
                        array_push($data[$e],'INV ');
                        $ttcr = 0;
                        array_push($data[$e],'INV ');
                        array_push($data[$e],'INV ');
                        array_push($data[$e],'INV ');
                    }
                }
        return Excel::download(new RecapAnnuelExport($data), 'resultats.xlsx');
    }
}
