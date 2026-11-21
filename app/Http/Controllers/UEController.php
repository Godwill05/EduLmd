<?php

namespace App\Http\Controllers;

use App\Models\Ue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UEController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /* Pagination : la table UE comptait déjà 253 lignes en production
           et grossit chaque année. */
        $items = Ue::query()
            ->orderBy('codeUe')
            ->paginate(50)
            ->withQueryString();

        return view('ue', ['items' => $items]);
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
            'codeUE'=>'required',
            'intitule'=>'required'
        ]);
        Ue::create([
            'codeUe'=>$request->codeUE,
            'libelleUe'=>$request->intitule,
            'Auteur'=>Auth::user()->login
        ]);
        return to_route('ue.index')->with('succes','UE enregistrée !');
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
            'codeUE'=>'required',
            'intitule'=>'required'
        ]);
        Ue::find($id)->update([
            'codeUe'=>$request->codeUE,
            'libelleUe'=>$request->intitule,
        ]);
        return to_route('ue.index')->with('succes','UE modifiée !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Ue::find($id)->delete();
        return to_route('ue.index')->with('succes','UE supprimée !');
    }
    public function recapitulatif() {
        if (!request()->except('_token','anneeAcademique','anneeEtude')) {
            return('Aucun résultat');
        }
        extract($this->extraireEntrees());
        $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"); //echo count($rc);
        $rc=array_map(function ($value) {
            return (array)$value;
        }, $rc);
        if (!$rc) {
            return('Aucun résultat');
        }
        ?>
        <table id="liste" class="table table-bordered table-striped" style="width:100%" border="1px" cellspacing="2" cellpadding="2">

            <thead class="thead">
                <tr>
                    <th rowspan="2" colspan="2" style="text-align: center;"><?php echo $rc[0]["libelleUe"] ?></th>
                    <?php
                    $aColsEval = [];
                    for ($i = 0; $i < count($rc); $i++) {
                        $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$i]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                        $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$i]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
                        $nDev = DB::select($sqlDev);
                        $nDev = (int)$nDev[0]->nbre;
                        $nPart = DB::select($sqlPart);
                        $nPart = (int)$nPart[0]->nbre;
                        $aColsEval[$rc[$i]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$i]["Ponderation"], 'pdev' => $rc[$i]["PoidsDev"], 'ppart' => $rc[$i]["PoidsPart"]);
                    ?>
                        <th colspan="<?php echo ($nDev + ($nDev == 0 ? 0 : 1)) + ($nPart + 1) + 1; ?>" style="text-align: center;"> <?php echo $rc[$i]["libelleEcu"]; ?></th>
                        <th style="text-align: center;"><?php echo ($rc[$i]["Ponderation"]) * 100; ?>%</th>
                    <?php
                    } ?>

                </tr>
                <tr>
                    <?php
                    foreach ($aColsEval as $col) { //var_dump($col);
                        for ($i = 0; $i < $col['dev']; $i++) { ?><th style="text-align: center;"><?php echo "D" . ($i + 1); ?></th> <?php }
                        if ($col['dev'] !== 0) { ?><th style="text-align: center;">MD</th><?php }
                        for ($i = 0; $i < $col['part']; $i++) { ?><th style="text-align: center;"><?php echo "P" . ($i + 1); ?></th> <?php } ?>
                        <th style="text-align: center;">MP</th>
                        <th style="text-align: center;">Rattr</th>
                        <th style="text-align: center;">M Ecue</th><?php
                        } ?>
                </tr>

            </thead>
            <tbody>
                <?php
                $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique'  and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC ;";
                $listeE = DB::select($sql);
                $listeE=array_map(function ($value) {
                    return (array)$value;
                }, $listeE);
                for ($e = 0; $e < count($listeE); $e++) {
                    extract($listeE[$e]); ?>
                    <tr>
                        <td><?php echo $matricule ?></td>
                        <td><?php echo "<b>$noms</b> $prenoms" ?></td>
                        <?php
                        $moyenne = 0.0;
                        $tou = 0;
                        foreach ($aColsEval as $key => $col) {
                            //var_dump($col);
                            //DEVOIRS
                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $tou = count($r);
                            if ($tou == 0 && $col['dev'] !== 0) {
                                echo '<td style="text-align: center;" > Abs </td>';
                            } else {
                                for ($i = 0; $i < count($r); $i++) { ?><td style="text-align: right;"><?php echo $r[$i]['Note']; ?></td> <?php }
                            }
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $md = floatval($r[0]['MD']);
                            if ($col['dev'] !== 0) { ?><td style="text-align: right;"><?php echo number_format($md, 2, ',', ' '); ?></td><?php }
                            //PARTIEL
                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $tou = count($r);
                            if ($tou == 0) {
                            echo '<td style="text-align: center;" > Abs </td>';
                            } else {
                            for ($i = 0; $i < count($r); $i++) { ?><td style="text-align: center;"><?php echo $r[$i]['Note']; ?></td> <?php }
                            }
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mp = floatval($r[0]['MD']);
                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                            $r = DB::select($listeN);
                            $r=array_map(function ($value) {
                                return (array)$value;
                            }, $r);
                            $mR = floatval($r[0]['MD']);
                            // echo "$matricule == $md, $key --- $listeN<br>";
                            ?><td style="text-align: right;"><?php echo number_format($mp, 2, ',', ' '); ?></td>
                            <td style="text-align: right;"><?php $rattr = $mR;
                            echo (int)$rattr === 0 ? "N/A" : number_format($rattr, 2, ',', ' '); ?></td>
                            <td style="text-align: right;"><?php $mecue = floatval((int)$md === 0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                            $mecue = max($rattr, $mecue);
                            echo number_format($mecue, 2, ',', ' '); ?></td><?php
                            $moyenne += $mecue * $col['ponderation'];
                            } ?>
                    </tr><?php } ?>
            </tbody>
        </table>
    <?php
    }
}
