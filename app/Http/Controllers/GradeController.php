<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\FiliereCpl;
use App\Models\Grade;
use App\Models\Inscription;
use App\Models\RecapAnnuel;
use App\Models\SemestreAnneeEtude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    public function anneeAcademiqueNext($annee) {
        if (empty($annee)) return '';
        $anAc = AnneeAcademique::find($annee);
        if (!$anAc || empty($anAc->ANNEE_ACAD)) return '';
        $a = explode('-', $anAc->ANNEE_ACAD);
        if (count($a) < 2) return '';
        $a[1] += 1;
        $a[0] += 1;
        $a = implode('-', $a);
        $next = AnneeAcademique::where('ANNEE_ACAD', $a)->first();
        return $next ? $next->id_Annee_Acad : '';
    }
    public function anneeAcademiquePrevious($annee) {
        $anAc = AnneeAcademique::find($annee);
        if (!$anAc) return '';
        $a = explode('-', $anAc->ANNEE_ACAD);
        if (count($a) < 2) return '';
        $a[0] -= 1;
        $a[1] -= 1;
        $a = implode('-', $a);
        return $a;
    }
    public function convert_array($var){
        $var=array_map(function ($value) {
            return (array)$value;
        }, $var);
        return $var;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('grades',['grades'=>Grade::all()]);
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
            'codeGrade'=>'required',
            'libelleGrade'=>'required',
        ]);
        $grade=new Grade();
        $grade->codeGrade=$request->codeGrade;
        $grade->libelleGrade=$request->libelleGrade;
        $grade->Auteur=Auth::user()->login;
        $grade->save();
        return to_route('grades.index')->with('succes','Grade enregistré !');
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
            'codeGrade'=>'required',
            'libelleGrade'=>'required',
        ]);
        $grade=Grade::find($id);
        $grade->codeGrade=$request->codeGrade;
        $grade->libelleGrade=$request->libelleGrade;
        $grade->save();
        return to_route('grades.index')->with('succes','Grade modifié !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Grade::find($id)->delete();
        return to_route('grades.index')->with('succes','Grade supprimé avec succès');
    }
    public function recapitulatif()
    {
        extract($this->extraireEntrees());
        if (empty(FiliereCpl::find($filiere)->annee3)) {
            $anneeEtude=FiliereCpl::find($filiere)->anneeEtude2->Code_Annee_Etude;
            $lastYear=$this->anneeAcademiqueNext($anneeAcademique);
        } else {
            $anneeEtude=FiliereCpl::find($filiere)->anneeEtude3->Code_Annee_Etude;
            $lastYear=$this->anneeAcademiqueNext($this->anneeAcademiqueNext($anneeAcademique));
        }

        $anneesEtudes=AnneeEtude::whereIn('Code_Annee_Etude',[FiliereCpl::find($filiere)->annee1,FiliereCpl::find($filiere)->annee2,FiliereCpl::find($filiere)->annee3])->get();
        //dump($anneesEtudes);
        $ttcredit = 0;
        foreach ($anneesEtudes as $anetd) {
            foreach ($anetd->anneeEtudeSemestres as $semestreAnneeEtude) {
                $ttcredit+=$semestreAnneeEtude->classe->attrUeClasses->sum('Credit');
            }
        }

        // var_dump($UE[0]);
        //exit;
        ?>
        <table id="liste" class="display table-bordered" style="width:100%;font-size: 10px;" border="1" cellspacing="2" cellpadding="2">
            <thead class="thead">
                <tr>
                    <th colspan="2" style="text-align: center;"><?php echo (FiliereCpl::find($filiere)->nom ?? '') . '<br>' . ' PROMOTION' . "<br/>" . (AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD ?? ''); ?></th>
                    <?php foreach ($anneesEtudes as $as) { ?>
                            <th colspan="4" style="text-align:center;font-size: 11px">
                                <?php echo $as->Libelle_AnEtude;?>
                            </th><?php
                          }
                    ?>
                    <th width="5%" colspan="4" style="text-align:center;font-size: 11px">RECAPITULATIF</th>
                </tr>

                <tr>
                    <th rowspan="2" width="50%" class="text-center">Matricule</th>
                    <th rowspan="2" width="50%" class="text-center">Nom & Prénoms</th>
                    <?php foreach ($anneesEtudes as $as) { ?>
                        <th width="5%" rowspan="2" style="text-align:center;font-size: 11px">Nombre Crédit Capitalisé</th>
                        <th width="5%" rowspan="2" style="text-align:center;">%age d'UE (en %)</th>
                        <th width="5%" rowspan="2">Moyenne</th>
                        <th width="5%" rowspan="2">Décision</th>
                    <?php } ?>

                    <th width="5%" style="text-align:center;font-size: 11px">Nombre Crédit Capitalisé</th>
                    <th width="5%" rowspan="2" style="text-align:center;">%age d'UE (en %)</th>
                    <th width="5%" rowspan="2">Moyenne</th>
                    <th width="5%" rowspan="2">Décision</th>
                </tr>
                </tr>
                <th width="5%" style="text-align:center;font-size: 11px"><?php echo $ttcredit ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                    if(Inscription::where('Id_Annee',$lastYear)->where('ID_FILIERE',$anneeEtude)->get()->isEmpty()) echo '<div class="text-center">Aucune donnée trouvée pour cette année. Veuillez d\'abord effectuer la <a href="'.route('recapitulatifAnnuel').'">délibération annuelle</a></div>';
                    foreach (Inscription::where('Id_Annee',$lastYear)->where('ID_FILIERE',$anneeEtude)->get() as $inscription) {
                        $anac=$anneeAcademique;
                ?>
                <tr>
                    <td class="text-center"><?=$inscription->MATRICULE?></td>
                    <td><b><?=$inscription->etudiant->nomEtu?></b> <?=$inscription->etudiant->prenomsEtu?></td>
                    <?php $totalCredit=0;$ptge=0;$moy=0.0;$nbAnnees=$anneesEtudes->count();$decision='';$i=0;foreach($anneesEtudes as $as){
                        $recap = RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first();
                        $totalCredit += $recap->Nbre_Cr_Capi ?? 0;
                        $ptge += number_format($recap->Ptage_Obt ?? 0, 2);
                        $moy += $recap->MoyenneSem ?? 0;
                        $decJury = $recap->DecisionJury ?? 'INV';
                        if ($anac==$anneeAcademique) {?>
                            <?php if($decJury !== 'INV'){?>
                                <td class="text-center"><?=$recap->Nbre_Cr_Capi ?? '-'?></td>
                                <td class="text-center"><?=number_format($recap->Ptage_Obt ?? 0.0,2)?></td>
                                <td class="text-center"><?=$recap->MoyenneSem ?? '-'?></td>
                            <?php }else{ ?>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                            <?php }?>
                            <td class="text-center"><?=$decJury?></td>
                        <?php }else{ ?>
                            <?php if($decJury !== 'INV'){?>
                                <td class="text-center"><?=$recap->Nbre_Cr_Capi ?? '-'?></td>
                                <td class="text-center"><?=number_format($recap->Ptage_Obt ?? 0.0,2)?></td>
                                <td class="text-center"><?=$recap->MoyenneSem ?? '-'?></td>
                            <?php }else{ ?>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                            <?php }?>
                            <td class="text-center"><?=$decJury?></td>
                        <?php } ?>
                    <?php  $anac=$this->anneeAcademiqueNext($anac);}?>
                    <?php if($inscription->S_Finance=="OUI"){?>
                        <td class="text-center"><?=$totalCredit?></td>
                        <td class="text-center"><?=number_format($ptge/$nbAnnees,2)?></td>
                        <td class="text-center"><?=number_format($moy/$nbAnnees,2)?></td>
                    <?php }else{ ?>
                        <td class="text-center">?</td>
                        <td class="text-center">?</td>
                        <td class="text-center">?</td>
                    <?php } ?>
                    <td  style="text-align:center;">
                                <?php
                                if ($inscription->S_Finance=='NON') $decision='INV';
                                else $decision=$ptge==100 ? "V" : "NV" ;
                                $grise=$decision=="V" ?"":"disabled";
                                ?>
                                <?php if($decision=='V') { ?>
                                    <button title="Imprimer attestation" onclick="attestation('<?= $inscription->MATRICULE ?>','<?=$anneeEtude?>','<?= $lastYear ?>','<?= $totalCredit ?>','<?= $decision ?>','<?= $ptge ?>','<?= $moy ?>','<?= $dateDeliberation ?>' );" >Attestation</button>
                                    <button title="Imprimer diplôme" onclick="diplome('<?= $inscription->MATRICULE ?>','<?=$anneeEtude?>','<?= $lastYear ?>','<?= $totalCredit ?>','<?= $decision ?>','<?= $ptge ?>','<?= $moy ?>','<?= $dateDeliberation ?>' );" >Diplôme</button>
                                <?php } ?>
                                <?php if($decision=='INV') {?>
                                    <span>INV</span>
                                <?php } ?>
                                <?php if($decision=='NV' && $inscription->S_Finance=='OUI') { ?>
                                    <span>NV</span>
                                <?php }?>
                            </td>
                </tr>
                <?php }?>
            </tbody>
        </table>
        <script>
            function attestation(matricule, anEtude, anneeaca, credits, decision, pourcentage, moyenne, datedelib) {
                var d = {
                    matricule: matricule,
                    anEtude: anEtude,
                    anneeaca: anneeaca,
                    credits: credits,
                    decision: decision,
                    pourcentage: pourcentage,
                    moyenne: moyenne,
                    datedelib: datedelib
                };
                //  var url="impressions.php?matricule="+matricule+"&anEtude="+anEtude+"&anneeaca="+anneeaca+"&credits="+credits+"&decision="+decision+"&pourcentage="pourcentage
                var form = document.createElement("form");
                form.target = "_blank";
                form.method = "POST";
                form.action = '/impression-attestation?'+"matricule="+matricule+"&anEtude="+anEtude+"&anneeaca="+anneeaca+"&credits="+credits+"&decision="+decision+"&pourcentage="+pourcentage;
                var input = document.createElement("input");
                input.name = "Imp_Attestation";
                input.value = JSON.stringify(d);
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();

            }

            function diplome(matricule, anEtude, anneeaca, credits, decision, pourcentage, moyenne, datedelib) {
                var d = {
                    matricule: matricule,
                    anEtude: anEtude,
                    anneeaca: anneeaca,
                    credits: credits,
                    decision: decision,
                    pourcentage: pourcentage,
                    moyenne: moyenne,
                    datedelib: datedelib
                };
                //  var url="impressions.php?matricule="+matricule+"&anEtude="+anEtude+"&anneeaca="+anneeaca+"&credits="+credits+"&decision="+decision+"&pourcentage="pourcentage
                var form1 = document.createElement("form");
                form1.target = "_blank";
                form1.method = "POST";
                form1.action = '/impression-diplome?'+"matricule="+matricule+"&anEtude="+anEtude+"&anneeaca="+anneeaca+"&credits="+credits+"&decision="+decision+"&pourcentage="+pourcentage;
                var input = document.createElement("input");
                input.name = "Imp_Diplome";
                input.value = JSON.stringify(d);
                form1.appendChild(input);
                document.body.appendChild(form1);
                form1.submit();

            }
        </script>
        <?php
            exit();
    }
    public function recapSolo(){
        extract($this->extraireEntrees());
        $Promotion = [[$annee_etude, $anneeAcademique]];
        $ttcredit = 0;

        // var_dump($UE[0]);
        //exit;
        ?>
        <table id="liste" class="display table-bordered" style="width:100%;font-size: 10px;" border="1" cellspacing="2" cellpadding="2">
            <thead class="thead">
                <tr>
                    <th colspan="2" rowspan="3" style="text-align: center;"><?php echo 'PROMOTION' . "<br/>" . (AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD ?? ''); ?></th>
                    <?php foreach ($Promotion as list($annee_etude, $anneeacad)) { ?> <th colspan="4" style="text-align:center;font-size: 11px"><?php echo $annee_etude ?></th><?php $UE = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$annee_etude' AND attr_ue_classe.id_Annee_Acad='$anneeacad' ORDER BY Nom_Classe,libelleUe ASC;"));

                                                                                                                                                                                $rTaux = $this->convert_array(DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeacad' LIMIT 1;"));
                                                                                                                                                                                $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
                                                                                                                                                                                $tCredit = 0;
                                                                                                                                                                                for ($i = 0; $i < count($UE); $i++) {
                                                                                                                                                                                    $tCredit += $UE[$i]['Credit'];
                                                                                                                                                                                    $UE[$i]['Credit'];
                                                                                                                                                                                }
                                                                                                                                                                                $ttcredit += $tCredit;
                                                                                                                                                                            } ?>
                    <th width="5%" colspan="4" style="text-align:center;font-size: 11px">RECAPITULATIF</th>
                </tr>

                <tr>
                    <?php foreach ($Promotion as list($annee_etude, $anneeacad)) { ?>
                        <th width="5%" rowspan="2" style="text-align:center;font-size: 11px">Nombre Crédit Capitalisé</th>
                        <th width="5%" rowspan="2" style="text-align:center;">%age d'UE</th>
                        <th width="5%" rowspan="2">Moyenne</th>
                        <th width="5%" rowspan="2">Décision</th>
                    <?php } ?>

                    <th width="5%" style="text-align:center;font-size: 11px">Nombre Crédit Capitalisé</th>
                    <th width="5%" rowspan="2" style="text-align:center;">%age d'UE</th>
                    <th width="5%" rowspan="2">Moyenne</th>
                    <th width="5%" rowspan="2">Décision</th>
                </tr>
                </tr>
                <th width="5%" style="text-align:center;font-size: 11px"><?php echo $ttcredit ?></th>
                </tr>
            </thead>
            <tbody> <?php
                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeacad'  and `ID_FILIERE` = '$annee_etude' ORDER BY noms,prenoms ASC;";
                    $listeE = $this->convert_array(DB::select($sql));
                    for ($e = 0; $e < count($listeE); $e++) {
                        extract($listeE[$e]);
                        $creditPromo = $ValiPromo = $MoyPromo = 0; ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $matricule ?></td>
                        <td><?php echo "<b>".$noms."</b> ".$prenoms ?></td>

                        <?php foreach ($Promotion as list($annee_etude, $anneeacad)) { ?>
                            <?php $tValide = 0;
                            $nV = 0;
                            $moyGene = 0;
                            for ($i = 0; $i < count($UE); $i++) {
                                $rattrapages = 0;
                                $noteRatr = 0;
                                $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                                // $tCredit+=$UE[$i]['Credit'];
                                $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude='$annee_etude' AND attr_ue_classe.id_Annee_Acad=$anneeacad ORDER BY libelleEcu ASC"));
                                $aColsEval = [];
                                for ($ii = 0; $ii < count($rc); $ii++) {
                                    //        SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$annee_etude and evaluation.codTypEval='DEV'
                                    $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$annee_etude'  and evaluation.codTypEval='DEV';";
                                    $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$annee_etude'  and evaluation.codTypEval='PART';";
                                    $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$annee_etude'  and evaluation.codTypEval='RATTR';";
                                    $nbrRatr = $this->convert_array(DB::select($sqlNRatr));
                                    $nbrRatr = (int)$nbrRatr[0]['nbre'];
                                    $rattrapages += $nbrRatr;
                                    $nDev = $this->convert_array(DB::select($sqlDev));
                                    $nDev = (int)$nDev[0]['nbre'];
                                    $nPart = $this->convert_array(DB::select($sqlPart));
                                    $nPart = (int)$nPart[0]['nbre'];
                                    $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"]);
                                }

                                // calc moyenne
                                $moyenne = 0.0;
                                $moyenneReprise = 0.0;
                                foreach ($aColsEval as $key => $col) { //var_dump($col);
                                    //DEVOIRS
                                    $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $md = floatval($r[0]['MD']);

                                    //PARTIEL
                                    $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $mp = floatval($r[0]['MD']);
                                    $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $mR = floatval($r[0]['MD']);
                                    $noteRatr += $mR;
                                    $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                    $r = $this->convert_array(DB::select($listeN));
                                    $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                    $mecue = floatval((int)$md === 0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                    $mecue = max($mR, $mecue);
                                    $moyenne += $mecue * $col['ponderation'];
                                    $moyenneReprise += $mRepr * $col['ponderation'];
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
                                $moyGene += $moyenne * $UE[$i]['Credit'];
                                $rue = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                                $ligne['moyenne'][] = [$UE[$i]['ID_UE'], $moyenne, $rue, $UE[$i]['Credit'], $UE[$i]['libelleUe'], $UE[$i]['Nom_Classe'], $UE[$i]['Seuil_Ue']];
                                $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                            ?>
                            <?php } ?>
                            <td style="text-align:center;"><?php $creditPromo += $tValide;
                                                            echo $tValide; ?></td>
                            <td style="text-align:center;"><?php count($UE) === 0 ? 1 : $percentUE = 100 * $nV / count($UE);
                                                            $ValiPromo += $percentUE;
                                                            echo number_format($percentUE, 2, ',', ' '); ?>%</td>
                            <td style="text-align:center;"><?php echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' ');
                                                            $MoyPromo += ($moyGene / $tCredit); ?></td>
                            <td style="text-align:center;"><?php echo $percentUE < $taux ? "NV" : ($percentUE < 100 ? "V+R" : "V"); ?></td><?php
                                                                                                                                        } ?>
                        <td style="text-align:center;"><?php echo $creditPromo; ?></td>
                        <td style="text-align:center;"><?php count($Promotion) === 0 ? 1 : $ValiPromo = $ValiPromo / count($Promotion);
                                                        echo number_format($ValiPromo, 2, ',', ' '); ?>%</td>
                        <td style="text-align:center;"><?php $m = $MoyPromo / count($Promotion);
                                                        echo count($Promotion) === 0 ? 1 : number_format($m, 2, ',', ' '); ?></td>
                        <td style="text-align:center;">
                            <?php $decision = $percentUE < $taux ? "NV" : ($percentUE < 100 ? "V+R" : "V");
                            $grise = $decision === "V" ? "" : "disabled";
                            //$clik=$decision==="V"?"alert(123)":"";
                            ?>
                            <?= $decision ?>
                        </td>
                    </tr>

                <?php } ?>

            </tbody>
        </table>
        <?php exit;
    }
}
