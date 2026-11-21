<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Semestre;
use App\Models\SemestreEtude;
use App\Models\Specialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SemestresEtudesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('semestresEtudes',['semestresEtudes'=>SemestreEtude::all(),'grades'=>Grade::all(),'specialites'=>Specialite::all(),'semestres'=>Semestre::all()]);
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
            'libelleSemestre'=>'required',
            'specialite'=>'required',
            'semestre'=>'required',
            'grade'=>'required'
        ]);
        $smEtd=new SemestreEtude();
        $smEtd->Nom_Classe=$request->libelleSemestre;
        $smEtd->numeroSemestre=$request->semestre;
        $smEtd->codeSpe=$request->specialite;
        $smEtd->options=$request->option;
        $smEtd->codeGrade=$request->grade;
        $smEtd->Auteur=Auth::user()->login;
        $smEtd->save();
        return to_route('semestres-etudes.index')->with('succes','Semestre d\'étude enregistré');
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
            'libelleSemestre'=>'required',
            'specialite'=>'required',
            'semestre'=>'required',
            'grade'=>'required'
        ]);
        $smEtd=SemestreEtude::find($id);
        $smEtd->Nom_Classe=$request->libelleSemestre;
        $smEtd->numeroSemestre=$request->semestre;
        $smEtd->codeSpe=$request->specialite;
        $smEtd->options=$request->option;
        $smEtd->codeGrade=$request->grade;
        $smEtd->save();
        return to_route('semestres-etudes.index')->with('succes','Semestre d\'étude modifié');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        SemestreEtude::find($id)->delete();
        return to_route('semestres-etudes.index')->with('succes','Semestre d\'étude supprimé');
    }
    public function convert_array($var){
        $var=array_map(function ($value) {
            return (array)$value;
        }, $var);
        return $var;
    }
    public function recapitulatif()
    {
        extract($this->extraireEntrees());
        $UE = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.id_S_Gr_Spe='$semestreEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique';"));
        if (!$UE) {
            return('<div class="row"><div class="col-auto">Aucun résultat...</div><div class="col-auto"><a href="javascript:void(0);" onclick="afficher()">Relancer</a> | <a href="/attribuer-ue-semestre">Attribuer</a></div></div>');
        }
        $rTaux = $this->convert_array(DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;"));
        $tete = $this->convert_array(DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'"));
        $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
        $anne = $UE[0]['an'];
        $cl =  $UE[0]['clas'];
        //var_dump($UE[0]); exit;
        ?>
         <p style="text-align:justify;color: #8a1f11; font-size: 15px; ">
                <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo '<b>' . $UE[$i]['ID_UE'] . "==>" . $UE[$i]['libelleUe'] . '</b>(' ?>
            <?php
                                    $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                    $recue = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"));
                                    for ($iecuee = 0; $iecuee < count($recue); $iecuee++) {
                                        echo $recue[$iecuee]['ecue'] . ":" . $recue[$iecuee]['libelleEcu'] . ' ';
                                    }
                                    echo ')';
                                }    ?>
            ---@@@--- V = Validé --- NV = Non Validé -- * Rattrapage -- ** Reprise
        </p>
        <div>
            <input type="checkbox" name="select_all_students" id="select_all_students" onclick="$('input[name^=selected_students]').prop('checked', this.checked);" checked>
            <label for="select_all_students">Sélectionner tous les étudiants</label>
        </div>
        <div class="p-2"><form action="<?=route('impression.semestre-selection')?>" method="POST" id="recapSemestre" target="_blank">
            <input type="hidden" name="type_document" value="pv">
            <input type="hidden" name="anneeAcademique" value="<?php echo $anneeAcademique; ?>">
            <input type="hidden" name="semestreEtude" value="<?php echo $semestreEtude; ?>">
            <input type="hidden" name="anneeEtude" value="<?php echo $anneeEtude; ?>">
        <div class="table-responsive"><table id="liste" class="table table-bordered table-sm display" style="width:60%; margin:auto; font-size:12px;" border="1">
                <thead>
                    <tr style="background-color:#7BA7CE">
                        <th colspan="3" rowspan="2" style="text-align: center; font-size: 25px"> Semestre:<br> <?php echo $UE[0]['Nom_Classe']; ?></th>
                        <?php
                        $tCredit = 0;
                        for ($i = 0; $i < count($UE); $i++) {
                           // $unie=$UE[$i]['ID_UE'];
                          //  $$unie=0;
                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                            $recue = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"));
                            $oo = count($recue);
                        ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center"><?php echo $UE[$i]['ID_UE'] . ' (' . $UE[$i]['Credit'] . ' Cr)   ';
                                                                                                                $tCredit += $UE[$i]['Credit'];

                                                                                                                ?></th> <?php
                                                                                                                                            } ?>
                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;">Nombre<br />Crédits<br /> Capitalisés sur <b style="color:#d12b3d; font-size:15px"><br /><?php echo ' ' . $tCredit; ?></b> </th>
                        <th rowspan="2" width="5%" style="vertical-align: middle;">% UE</th>
                        <th rowspan="2" width="5%" style="vertical-align: middle;">Moyenne</th>
                        <th rowspan="2" width="5%" style="vertical-align: middle;">Nbre UE<br /> Validé</th>
                    </tr>
                    <tr style="background-color:#dad55f">
                        <?php for ($i = 0; $i < count($UE); $i++) {
                           // $$unie=0;
                            $cue_af = $UE[$i]['Id_Attr_ue_Sem'];
                            $rcue = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$cue_af AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"));
                            for ($iae = 0; $iae < count($rcue); $iae++) {
                        ?>
                                <th style="text-align: center"><?php echo $rcue[$iae]['ecue'] . '<br>' . $rcue[$iae]['Ponderation'] * 100 . '%'; ?></th> <?php } ?>
                            <th style="text-align: center;vertical-align: middle;background-color:#7BA7CE">MoY</th>
                            <th style="text-align: center;vertical-align: middle;background-color:#7BA7CE">Etat</th>
                        <?php

                        } ?>

                    </tr>
                </thead>
                <tbody> <?php
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
                            $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and e.MATRICULE IN ($matsStr) ORDER BY noms,prenoms ASC ;";
                        } else {
                            $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC ;";
                        }
                        $listeE = $this->convert_array(DB::select($sql));
                        $sv = $svr = $snv = $svr_70 = 0;
                        for ($e = 0; $e < count($listeE); $e++) {
                            extract($listeE[$e]); ?>
                        <tr>
                            <td><input type="checkbox" name="selected_students[]" id="selected_students<?=$e?>" value="<?php echo $matricule; ?>" checked></td>
                            <td style="vertical-align: middle"><?php echo $matricule ?></td>
                            <td style="width: 150px;height: 18px; vertical-align: middle"> <?php echo "<b style='text-transform: uppercase'>" . htmlentities($noms) . "</b>  " . htmlentities($prenoms) ?></td>
                            <?php $ooo = $tValide = 0;
                            $nV = 0;
                            $moyGene = 0;

                            for ($i = 0; $i < count($UE); $i++) {
                               //  ${$UE}[$i]['ID_UE']=0;
                                $rattrapages = 0;
                                $noteRatr = 0;
                                $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                                $$ue_num=0;
                                $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"));
                                $ooo = count($rc);
                                $aColsEval = [];
                                for ($ii = 0; $ii < count($rc); $ii++) {
                                    $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                                    $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
                                    $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
                                    $nbrRatr = $this->convert_array(DB::select($sqlNRatr));
                                    $nbrRatr = (int)$nbrRatr[0]['nbre'];
                                    $rattrapages += $nbrRatr;
                                    $nDev = $this->convert_array(DB::select($sqlDev));
                                    $nDev = (int)$nDev[0]['nbre'];
                                    $nPart = $this->convert_array(DB::select($sqlPart));
                                    $nPart = (int)$nPart[0]['nbre'];
                                    $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"], 'slecue' => $rc[$ii]["Seuil_Ecu"]);
                                }

                                // calc moyenne
                                $moyenne = 0.0;
                                $moyenneReprise = 0.0;
                                $cpseDev = $cpsePar = 0;
                                foreach ($aColsEval as $key => $col) { //var_dump($col);
                                    //DEVOIRS
                                    $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $cpseDev = count($r);
                                    $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $md = floatval($r[0]['MD']);

                                    //PARTIEL
                                    $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $cpsePar = count($r);
                                    $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $mp = floatval($r[0]['MD']);
                                    $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $mR = floatval($r[0]['MD']);
                                    $noteRatr += $mR;
                                    $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                    //  $listeN = "SELECT o.Note MD, o.moment moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = 'RD' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='385' ORDER BY `moment` DESC LIMIT 1;";
                                    // $r = $this->convert_array(DB::select($listeN));
                                    //  echo  $listeN.'---@@---'.floatval($r[0]['MD']);  exit();
                                    $r = $this->convert_array(DB::select($listeN));
                                    $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                    $mecue = floatval((int)$md === 0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                    $McalEcue = $mecue;
                                    $mecue = max($mR, $mecue);
                                    $moyenne += $mecue * $col['ponderation'];

                                    $moyenneReprise += $mRepr * $col['ponderation'];
                            ?>
                                    <td style="text-align: center;vertical-align: middle;">
                                        <?php
                                        echo ($cpseDev == 0 && $cpsePar == 0) && ($noteRatr == 0 || $mRepr == 0) ? 'Abs' : number_format($McalEcue, 2, ',', ' ');
                                        echo ($mR > 0 && $noteRatr != 0) ? '<b>' . '<br>' . number_format($mR, 2, ',', ' ') . '<sup>*</sup>' . '</b>' : '';
                                        echo ($moyenneReprise > 0.0 && $moyenneReprise != 0.0) ? '<b>' . '<br>' . number_format($mRepr, 2, ',', ' ') . '<sup>**</sup>' . '</b>' : '';

                                        ?> </td>
                                <?php
                                    //echo $mecue<$col['slecue'] ?'#FFFFFF':'#FFFFFF'
                                }
                                if ($rattrapages >= 1 && $noteRatr > 0 && $moyenneReprise == 0.0) {
                                    if ($moyenne > $UE[$i]['Seuil_Ue']) {
                                        $moyenne = $UE[$i]['Seuil_Ue'];
                                    }
                                } else {
                                    if ($moyenneReprise > 0.0) {
                                        $moyenne = $moyenneReprise;
                                    }
                                }
                                //ici j'ai déjà les moyennes
                                // $matricule,$anneeEtude,$ue_num,$moyenne,$evz
                                ?>

                                <td style="text-align: center;vertical-align: middle;background-color:#7BA7CE"><?php echo number_format($moyenne, 2, ',', ' ');
                                                                                                                $moyGene += $moyenne * $UE[$i]['Credit']; ?></td>
                                <td style="text-align: center;vertical-align: middle;background-color:#7BA7CE"><?php
                                                                                                                //if($moyenne>$UE[$i]['Seuil_Ue']){echo "V";}else{if($moyenne==$UE[$i]['Seuil_Ue']){echo "V";}else{echo "NV";}}
                                                                                                                //echo $UE[$i]['Seuil_Ue'];
                                                                                                                echo '<b>' . $evz = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" . '</b>' : "NV" . '</b>';

                                                                                                            //    round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ?   ${$UE}[$i]['ID_UE']= ${$UE}[$i]['ID_UE']+1  :   ${$UE}[$i]['ID_UE']=  ${$UE}[$i]['ID_UE']+0; ;

                                                                                                                $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                                                                                                $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                                                                                                                $KdeUE_Date_Val = $Etat_UE = "";
                                                                                                                $existee = 0;
                                                                                                                $Etat_UE = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                                                                                                                $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$ue_num';";
                                                                                                                $dater = $this->convert_array(DB::select($rrrrrr));
                                                                                                                $existee = count($dater);
                                                                                                                //$rrr=


                                                                                                                //mis à jour recap_Ue
                                                                                                                //  $requ=mysql_query("REPLACE INTO `recap_UE` (`MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE`, `Observation`, `Auteur`, `moment`) VALUES ('$matricule', '$anneeEtude', $ue_num, $moyenne ,'$evz', 'Delib_Sem', '".$_SESSION['slogin']."', CURRENT_TIMESTAMP)");

                                                                                                                ?></td>
                            <?php
                        //echo $ue_num.'------@@@@------'.$$ue_num.'----------------';
                      // $UEVAL[] = array( 'NON_UE' => $ue_num, 'NBRE_UE' => $$ue_num+$$ue_num);

                        } ?>
                            <td style="text-align: center;vertical-align: middle"><?php echo $tValide; ?></td>
                            <td style="text-align: center;vertical-align: middle"><?php $percentUE = 100 * $nV / count($UE);
                                                                                    echo number_format($percentUE, 2, ',', ' '); ?>%</td>
                            <td style="text-align: center"><?php $mgs = $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, '.', ' ');
                                                            echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' '); ?></td>
                            <td style="vertical-align: middle;text-wrap: nowrap;text-align:center;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
                                                                                                        echo "#FFFFFF";
                                                                                                        $svr += 1;
                                                                                                    } else {
                                                                                                        if ($percentUE >= 70 && $percentUE < $taux) {
                                                                                                            echo "#FFFFFF";
                                                                                                            $svr_70 += 1;
                                                                                                        } else {
                                                                                                            if ($percentUE < $taux) {
                                                                                                                echo "#FFFFFF";
                                                                                                                $snv += 1;
                                                                                                            } else {
                                                                                                                echo "#FFFFFF";
                                                                                                                $sv += 1;
                                                                                                            }
                                                                                                        }
                                                                                                    } ?>"><?php echo $moyGene / $tCredit <= 3 ? 'Abandon' : '<b>' . $nV . '</b>  sur  <b>' . count($UE) . '</b>';
                                                                                                            ?></td>

                        </tr> <?php


                        }


                        ?>




                </tbody>

            </table></div>

            <div style=" text-align:left;">
                <div>
                    <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                    Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                    Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                    Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                    <?php $t = $sv + $svr + $snv + $svr_70;
                    echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                    echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / $t, 2, '.', ' ') . '% </b> <br/> ';
                    echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / $t, 2, '.', ' ') . '%  </b> <br/> ';
                    echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / $t, 2, '.', ' ') . '% </b> <br/>';
                    echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / $t, 2, '.', ' ') . '% </b> <br/>';

                    ?>

                </div>
            </div>
            <input type="submit" name="btn" value="Imprimer">
            <input type="submit" name="btn" value="Rattrapages"/>
            <input type="submit" name="btn" value="Reprises"/>
            <input type="submit" name="btn" value="Reprises après rattrapage"/>

        </form></div>
        <?php
    }
}
