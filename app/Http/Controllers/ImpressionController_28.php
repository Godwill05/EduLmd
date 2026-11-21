<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\AttrEcueUe;
use App\Models\FiliereCpl;
use App\Models\Inscription;
use App\Models\Nationalite;
use App\Models\Prevision;
use App\Models\RecapAnnuel;
use App\Models\SemestreEtude;
use App\Models\Universite;
use Carbon\Carbon;
use DateTime;
use Dompdf\Dompdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Ignition\Ignition;
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;

class ImpressionController extends Controller
{
    public function anneeAcademiqueNext($annee) {
        $a=explode('-',AnneeAcademique::find($annee)->ANNEE_ACAD);
        $a[1]+=1;
        $a[0]+=1;
        $a=implode('-',$a);
        return AnneeAcademique::where('ANNEE_ACAD',$a)->first()->id_Annee_Acad ?? '';
    }
    public function impressionGrade(){
        if(request('btn')=='Imprimer'){
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
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
            ob_start();?>
            <page backtop="43mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:41%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                PROCES VERBAL DE DELIBERATION DE GRADE<br>
                                -----*-----*-----<br>
                                <strong>FILIERE : </strong><?=FiliereCpl::find($filiere)->nom?> | <strong>PROMOTION : </strong><?=substr(AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD,0,4).'-'.substr(AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD,0,4)+$anneesEtudes->count()?><br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="text-align: right;width: 29%;"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>

                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <div style="padding-top: 40px; ">
                    <table id="liste" class="display" style="width:100%;font-size: 12px;margin: auto;" border="1" cellspacing="2" cellpadding="2">
                        <thead class="thead">
                            <tr>
                                <th colspan="2" style="text-align: center;background-color: #7BA7CE;"><?php echo FiliereCpl::find($filiere)->nom.'<br>'.' PROMOTION' . "<br/>" . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD; ?></th>
                                <?php foreach ($anneesEtudes as $as) { ?>
                                        <th colspan="4" style="text-align:center;font-size: 11px;background-color: #80C8F2">
                                            <?php echo $as->Libelle_AnEtude;?>
                                        </th><?php
                                    }
                                ?>
                                <th width="5%" colspan="4" style="text-align:center;font-size: 11px;background-color: #7BA7CE">RECAPITULATIF</th>
                            </tr>

                            <tr>
                                <th rowspan="2" width="50%" class="text-center">Matricule</th>
                                <th rowspan="2" width="50%" style="text-align: center;">Nom & Prénoms</th>
                                <?php foreach ($anneesEtudes as $as) { ?>
                                    <th width="5%" rowspan="2" style="text-align:center;font-size: 10px">Nombre <br> Crédit <br> Capitalisé</th>
                                    <th width="5%" rowspan="2" style="text-align:center;">%age d'UE</th>
                                    <th width="5%" rowspan="2">Moyenne</th>
                                    <th width="5%" rowspan="2">Décision</th>
                                <?php } ?>

                                <th width="5%" style="text-align:center;font-size: 10px">Nombre <br> Crédit <br> Capitalisé</th>
                                <th width="5%" rowspan="2" style="text-align:center;">%age d'UE</th>
                                <th width="5%" rowspan="2">Moyenne</th>
                                <th width="5%" rowspan="2">Décision</th>
                            </tr>
                            <tr>
                            <th width="5%" style="text-align:center;font-size: 11px"><?php echo $ttcredit ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if(Inscription::where('Id_Annee',$lastYear)->where('ID_FILIERE',$anneeEtude)->get()->isEmpty()) echo '<div class="text-center">Aucune donnée trouvée pour cette année. Veuillez d\'abord effectuer la <a href="'.route('recapitulatifAnnuel').'">délibération annuelle</a></div>';
                                foreach (Inscription::where('Id_Annee',$lastYear)->where('ID_FILIERE',$anneeEtude)->get() as $inscription) {
                                    $anac=$anneeAcademique;
                            ?>
                            <tr style="vertical-align: middle;">
                                <td style="text-align: center;"><?=$inscription->MATRICULE?></td>
                                <td style="height: 20px;"><b><?=$inscription->etudiant->nomEtu?></b> <?=$inscription->etudiant->prenomsEtu?></td>
                                <?php $totalCredit=0;$ptge=0;$moy=0.0;$nbAnnees=$anneesEtudes->count();$decision='';foreach($anneesEtudes as $as){
                                    $totalCredit+=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Nbre_Cr_Capi;
                                    $ptge+=number_format(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Ptage_Obt,2);
                                    $moy+=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->MoyenneSem;
                                    if ($anac==$anneeAcademique) {?>
                                        <?php if(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury!=='INV'){?>
                                            <td style="text-align: center;"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Nbre_Cr_Capi ?? '-'?></td>
                                            <td style="text-align: center;"><?=number_format(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Ptage_Obt ?? 0.0,2)?></td>
                                            <td style="text-align: center;"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->MoyenneSem ?? '-'?></td>
                                        <?php }else{ ?>
                                            <td style="text-align: center;">?</td>
                                            <td style="text-align: center;">?</td>
                                            <td style="text-align: center;">?</td>
                                        <?php }?>
                                        <td style="text-align: center;"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury ?? '-'?></td>
                                    <?php }else{ ?>
                                        <?php if(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury!=='INV'){?>
                                            <td style="text-align: center;"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Nbre_Cr_Capi ?? '-'?></td>
                                            <td style="text-align: center;"><?=number_format(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Ptage_Obt ?? 0.0,2)?></td>
                                            <td style="text-align: center;"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->MoyenneSem ?? '-'?></td>
                                        <?php }else{ ?>
                                            <td style="text-align: center;">?</td>
                                            <td style="text-align: center;">?</td>
                                            <td style="text-align: center;">?</td>
                                        <?php }?>
                                        <td style="text-align: center;"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury ?? '-'?></td>
                                    <?php } ?>
                                <?php  $anac=$this->anneeAcademiqueNext($anac);}?>
                                <?php if($inscription->S_Finance=="OUI"){?>
                                    <td style="text-align: center;"><?=$totalCredit?></td>
                                    <td style="text-align: center;"><?=number_format($ptge/$nbAnnees,2)?></td>
                                    <td style="text-align: center;"><?=number_format($moy/$nbAnnees,2)?></td>
                                <?php }else{ ?>
                                    <td style="text-align: center;">?</td>
                                    <td style="text-align: center;">?</td>
                                    <td style="text-align: center;">?</td>
                                <?php } ?>
                                <td  style="text-align:center;">
                                    <?php
                                    if ($inscription->S_Finance=='NON') $decision='INV';
                                    else $decision= $ptge==100 ? "V" : "NV" ;
                                    ?>
                                    <?= $decision ?>
                                </td>
                            </tr>
                            <?php }?>
                        </tbody>
                    </table>
                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('L', 'A3', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('PV_Grade_'.FiliereCpl::find($filiere)->nom.'_PROMO_'.AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD.'.pdf');
            } catch (Exception $e) {

                abort(500, 'erreur lors de la création du pdf !');
            }

        }
        if(request('btn')=='Ordre de mérite'){
            extract($this->extraireEntrees());
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            $rc = $this->convert_array(DB::select("SELECT recap_annuel.`MATRICULE` as Matricule, nomEtu,prenomsEtu,ANNEE_ACAD, annee_etude.Libelle_AnEtude, recap_annuel.*  FROM etudiant,anneeacademique,annee_etude,recap_annuel WHERE recap_annuel.MATRICULE=etudiant.MATRICULE AND recap_annuel.id_Annee_Acad=anneeacademique.id_Annee_Acad AND recap_annuel.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND recap_annuel.Code_Annee_Etude='" . FiliereCpl::find($filiere)->annee1 . "' and recap_annuel.id_Annee_Acad=" . $anneeAcademique . " ORDER BY Ptage_Obt DESC, recap_annuel.DecisionJury DESC, `recap_annuel`.`MoyenneSem`  DESC"));
            $anneesEtudes=AnneeEtude::whereIn('Code_Annee_Etude',[FiliereCpl::find($filiere)->annee1,FiliereCpl::find($filiere)->annee2,FiliereCpl::find($filiere)->annee3])->get();
            if (empty(FiliereCpl::find($filiere)->annee3)) {
                $anneeEtude=FiliereCpl::find($filiere)->anneeEtude2->Code_Annee_Etude;
                $lastYear=$this->anneeAcademiqueNext($anneeAcademique);
            } else {
                $anneeEtude=FiliereCpl::find($filiere)->anneeEtude3->Code_Annee_Etude;
                $lastYear=$this->anneeAcademiqueNext($this->anneeAcademiqueNext($anneeAcademique));
            }
            if (empty($rc)) {
                $message='Aucune donnée trouvée pour cette année. Veuillez d\'abord effectuer la délibération annuelle';
                abort(404);
            }
            ob_start(); ?>
            <page backtop="40mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                        <td style="width: 10%"><img style="width: 150px; height: 150px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:63%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                <u style="color: red; font-size: 15px"> CLASSEMENT DES ETUDIANTS PAR ORDRE DE MERITE<br></u>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 150px; height: 150px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>

                </page_header>
                <page_footer>

                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>

                <div style="display: block;  width:100%;align-content: center;">
                    <div style="text-align: center; margin-bottom: 5px;">
                        <u style="line-height: 10px"> <b style="color: red; font-size: 15px"><?php echo "  " . " <br><br> " . 'Promotion : ' . $rc[0]["ANNEE_ACAD"] . ' | Filière :  ' . FiliereCpl::find($filiere)->nom ?> </b></u>
                    </div>

                    <table style="width:100%; margin: auto" border="1px">
                        <thead>
                            <tr>
                                <th style="padding: 2px;" width="10%" scope="col"> Matricule</th>
                                <th style="padding: 2px;" width="30%" scope="col">Nom </th>
                                <th style="padding: 2px;" width="30%" scope="col"> Prénoms </th>
                                <th style="padding: 2px;" width="10%" scope="col">Moyenne </th>
                                <th style="padding: 2px;" width="10%" scope="col">%age </th>
                                <th style="padding: 2px;" width="10%" scope="col">Décision </th>
                                <th style="padding: 2px;" width="5%" scope="col">Rang </th>
                            </tr>
                        </thead>

                        <?php $nbr = count($rc);
                        $i=0;
                        $rangs=[];
                        foreach (Inscription::where('Id_Annee',$lastYear)->where('ID_FILIERE',$anneeEtude)->get() as $inscription) {
                            $anac=$anneeAcademique;
                            $totalCredit=0;$ptge=0;$moy=0.0;$nbAnnees=$anneesEtudes->count();$decision='';
                            foreach($anneesEtudes as $as){
                                $ptge+=number_format(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Ptage_Obt ?? 0.0,2);
                                $moy+=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->MoyenneSem ?? 0.0;
                                $anac=$this->anneeAcademiqueNext($anac);
                            }
                            $rangs[$i]['mat']=$inscription->MATRICULE;
                            $rangs[$i]['nom']=$inscription->etudiant->nomEtu;
                            $rangs[$i]['prenom']=$inscription->etudiant->prenomsEtu;
                            $rangs[$i]['moy']=number_format($moy/$nbAnnees,2);
                            $rangs[$i]['ptge']=number_format($ptge/$nbAnnees,2);
                            $rangs[$i]['deci']=($inscription->S_Finance=="NON")?'INV':((int)$ptge==100?'V':'NV');
                            //$rangs[$i]['rang']=$i + 1 == 1 ? $i + 1 . ' er' : $i + 1 . ' e';
                            ?>
                        <?php
                                $i++;
                            }
                            array_multisort(array_column($rangs, 'moy'), SORT_DESC, $rangs);
                        ?>
                        <?php $i=0;foreach(Inscription::where('Id_Annee',$lastYear)->where('ID_FILIERE',$anneeEtude)->get() as $inscription):?>
                            <tr>
                                <td style="padding: 2px; font-size:11px;height: 20px; width: 50px; vertical-align: middle"><?=$rangs[$i]['mat']?></td>
                                <td style="padding: 2px; height: 20px; width: 150px; vertical-align: middle"><?=$rangs[$i]['nom']?></td>
                                <td style="padding: 2px; height: 20px; width: 200px; vertical-align: middle"><?=$rangs[$i]['prenom']?></td>
                                <td style="padding: 2px; height: 20px; width: 50px; vertical-align: middle;text-align:center"><?=$rangs[$i]['moy']?></td>
                                <td style="padding: 2px; height: 20px; width: 50px;vertical-align: middle; text-align:center"><?=$rangs[$i]['ptge']?></td>
                                <td style="padding: 2px; height: 20px; width: 50px;vertical-align: middle; text-align:center"><?=$rangs[$i]['deci']?></td>
                                <td style="padding: 2px; height: 20px; width: 50px; vertical-align: middle; text-align:center"><?=$i + 1 == 1 ? $i + 1 . ' er' : $i + 1 . ' e' ?></td>

                            </tr>
                        <?php $i++; endforeach;?>
                    </table>
                    <div style="padding-top: 20px;">
                        <i> <b style="font-size: 10px;color: red"><u>NB:</u> Au total <?php echo ' ' . $nbr . ' '; ?> etudiants au total au sein de ce groupe pédagogique</b></i><br /><br />

                    </div>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('ORDRE_DE_MERITE_' . FiliereCpl::find($filiere)->nom . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD . '.pdf');
            } catch (Exception $e) {
                abort(500, 'erreur lors de la création du pdf !');
            }


        }

    }
    public function impressionAttestationRang(){
        if(request('btn')=='Imprimer'){
            extract($this->extraireEntrees());
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            $rc = $this->convert_array(DB::select("SELECT recap_annuel.`MATRICULE` as Matricule, nomEtu,prenomsEtu,ANNEE_ACAD, annee_etude.Libelle_AnEtude, recap_annuel.*  FROM etudiant,anneeacademique,annee_etude,recap_annuel WHERE recap_annuel.MATRICULE=etudiant.MATRICULE AND recap_annuel.id_Annee_Acad=anneeacademique.id_Annee_Acad AND recap_annuel.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND recap_annuel.Code_Annee_Etude='" . $annee_etude . "' and recap_annuel.id_Annee_Acad=" . $anneeAcademique . " ORDER BY Ptage_Obt DESC, recap_annuel.DecisionJury DESC, `recap_annuel`.`MoyenneSem`  DESC"));
            ob_start(); ?>
            <page backtop="40mm" backbottom="10mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                        <td style="width: 20%"><img style="width: 150px; height: 150px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:63%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                <u style="color: red; font-size: 15px"> CLASSEMENT DES ETUDIANTS PAR ORDRE DE MERITE<br></u>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 17%;vertical-align: middle; text-align: right"><img style="width: 150px; height: 150px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>

                </page_header>
                <page_footer>

                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>

                <div style="display: block;  width:100%;align-content: center;">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <u style="line-height: 10px"> <b style="color: red; font-size: 15px"><?php echo "  " . " <br><br> " . 'Promotion : ' . $rc[0]["ANNEE_ACAD"] . ' | Année Etude :  ' . $annee_etude ?> </b></u>
                    </div>

                    <table style="width:100%; margin: auto" border="1px">
                        <thead>
                            <tr style="text-align: center;">
                                <th width="10%" scope="col"> Matricule</th>
                                <th width="30%" scope="col">Nom </th>
                                <th width="30%" scope="col"> Prénoms </th>
                                <th width="10%" scope="col">Moyenne </th>
                                <th width="10%" scope="col">%age </th>
                                <th width="10%" scope="col">Décision </th>
                                <th width="5%" scope="col">Rang </th>
                            </tr>
                        </thead>

                        <?php $nbr = count($rc);
                        for ($i = 0; $i < count($rc); $i++) { ?>

                            <tr>
                                <td style=" font-size:11px;height: 20px; width: 50px; vertical-align: middle"><?php echo $rc[$i]["Matricule"] ?></td>
                                <td style=" height: 20px; width: 150px; vertical-align: middle"><?php echo utf8_encode($rc[$i]["nomEtu"]) ?></td>
                                <td style=" height: 20px; width: 200px; vertical-align: middle"><?php echo utf8_encode($rc[$i]["prenomsEtu"]) ?></td>
                                <td style=" height: 20px; width: 50px; vertical-align: middle;text-align:center"><?php echo number_format($rc[$i]["MoyenneSem"], 2, ',', ' '); ?></td>
                                <td style=" height: 20px; width: 50px;vertical-align: middle; text-align:center"><?php echo number_format($rc[$i]["Ptage_Obt"], 2, ',', ' '); ?></td>
                                <td style=" height: 20px; width: 50px;vertical-align: middle; text-align:center"><?php echo $rc[$i]["DecisionJury"] ?></td>
                                <td style=" height: 20px; width: 50px; vertical-align: middle; text-align:center"><?php echo $i + 1 == 1 ? $i + 1 . ' er' : $i + 1 . ' e' ?></td>

                            </tr>
                        <?php   } ?>

                    </table>
                    <div style="padding-top: 20px;">
                        <i> <b style="font-size: 10px;color: red"><u>NB:</u> Au total <?php echo ' ' . $nbr . ' '; ?> etudiants au total au sein de ce groupe pédagogique</b></i><br /><br />

                    </div>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('L', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('ORDRE_DE_MERITE_' . $annee_etude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
            }
        }
    }

    public function impressionAttestation() {
        $donnes = json_decode(request("Imp_Attestation"), true);
        extract($donnes);
        $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
        // echo var_dump($_GET);
        // exit();
        $tete = $this->convert_array(DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anEtude'"));
        $etudiant = $this->convert_array(DB::select("SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms,e.dateNaisEtu datenais,e.lieuNaisEtu lieunais,e.sexeEtu sexe,e.codeNationalite nation FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = $anneeaca and `ID_FILIERE` = '$anEtude' and e.MATRICULE = '$matricule' ORDER BY noms,prenoms ASC;"));
        //  $nation=$mysql->national_cod($etudiant[0]['nation']);
        $anac = $this->convert_array(DB::select("SELECT *  FROM `anneeacademique` WHERE `id_Annee_Acad` = $anneeaca"));
        ob_start();  ?>

        <page backtop="60mm" backbottom="4mm" backleft="2mm" backright="2mm">

            <page_header>
                <br><br><br><br>
                <div style="width: 98%;">
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="0">

                        <tr>
                            <td style="width: 15%;padding: right 30px;vertical-align: middle; text-align: right"><img style="width: 120px; height: 120px;" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:70%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                <b style="color:RED;font-size: 20px"> ATTESTATION DE DIPLÔME DE <?php echo strtoupper($tete[0]['libelleGrade']) . ' ' ?><?php echo $tete[0]['libelleGrade'] == "Licence" ? "PROFESSIONNELLE <br>" : "PROFESSIONNEL <br>" ?> </b>

                                <b style="font-size: 12px; margin:auto;"><?php echo '<br><br> N°____________ - ' . date('Y') . '/UAC/INEPS/DA/SGE/SS'; ?></b>
                            </td>
                            <td style="width: 15%;vertical-align: middle; text-align: left"><img style="width: 120px; height: 120px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>
                        <tr>
                            <td colspan="3" style="vertical-align: middle; text-align: justify">
                                <div style="width: 90%;margin-left:50px;">
                                    <p style="text-align: justify; line-height: 15px;font-size:11px;">
                                        Vu le décret n°96-550 du 6 décembre 1996 portant création, organisation et fonctionnement de l’Institut national de la Jeunesse, de l’Education physique et du Sport (INJEPS) ;<br />
                                        Vu l’arrêté interministériel année 2021 n°144/MESRS/MESTFP/DC MESRS/DC MESTFP/SGM MESRS/SGM/MESTFP/D/PIQ/DESG/DETFP/MESRS/SA002SGG21 portant Règlement pédagogique des Ecoles normales supérieures (ENS / ENSET) et des Instituts publics et privés de Formation des Enseignants de l’Enseignement du Second Degré ;<br />
                                        Vu le procès-verbal n°___________/UAC/INEPS/DA/SGE/SS/SA en date du <?php echo $datedelib ?>
                                    </p>
                                </div>
                            </td>

                        </tr>

                    </table>

                </div>
            </page_header>
            <page_footer>

                <div style="width: 100%; margin: 0px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; vertical-align: middle; "></div>
                    <div style="display: inline; width: 49%; text-align: center;font-size: 8px">
                        <?php echo $tete[0]['libelleGrade'] == "Licence" ? '' : '<p> <b style="display: inline;text-align: center;font-size: 11px"> Enregistrée au rectorat le .......................... sous le numéro................./UAC/VR-AA/SA</b></p>' ?>
                        Copyright © <?=now()->year?> LMD Web Solutions All rights reserved. <br><br><br><br><br><br>
                    </div>

                </div>
                <!--div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; ">www.ens.uac.bj</div>
                    <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © 2022 LMD Web Solutions All rights reserved.<br>
                        01 BP 583 Porto-Novo Tel: (229) 20 22 26 64 email:injeps@uac.bj Avakpa Kpodji, Porto-Novo République du Benin s
                    </div>
                    <div style="display: inline; width: 25%; text-align: right">ENS-PORTO/NOVO</div>
                </div -->
            </page_footer>

            <div style="width: 88%; margin: 65px ;font-size: 14px; margin-left: 60px ">
                <p style="text-align: justify; line-height: 30px">
                    <?php $ment = "";
                    if ($moyenne >= 18) {
                        $ment = "Excellent";
                    } elseif ($moyenne >= 16 && $moyenne < 18) {
                        $ment = " Très - Bien";
                    } elseif ($moyenne >= 14 && $moyenne < 16) {
                        $ment = " Bien";
                    } elseif ($moyenne >= 12 && $moyenne < 14) {
                        $ment = " Assez - Bien";
                    } elseif ($moyenne >= 10 && $moyenne < 12) {
                        $ment = " Passable";
                    } elseif ($moyenne == 10) {
                        $ment = " Passable";
                    } elseif ($moyenne < 10) {
                        $ment = " insuffisant";
                    } ?>
                    Le Directeur de l'<?php echo '<b>' . UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) . '</b>' ?> de l'<?php echo '<b>' . strtoupper($univ[0]['nom']) . '</b>' ?> atteste que <?php echo $etudiant[0]['sexe'] == 'M' ? 'M.' : 'Mme' ?> <b> <?php echo ($etudiant[0]['noms']) ?> <?php echo ($etudiant[0]['prenoms']) ?> </b> né(e) le <?php echo Carbon::parse($etudiant[0]['datenais'])->format('d/m/Y') ?> &agrave; <?php echo $etudiant[0]['lieunais'] ?> ,de nationalité <?php echo Nationalite::find($etudiant[0]['nation'])->libelleNationalite; ?>, inscrit(e) sur le numéro <b> <?php echo $matricule ?> </b> au titre de l'année académique <b> <?php echo $anac[0]['ANNEE_ACAD'] ?> </b> ,a participé avec succès aux activités académiques et a obtenu <b> <?php echo $tete[0]['libelleGrade'] == 'Licence' ? $credits * 3 : $credits * 2 ?> </b> Crédits validés qui correspondent &agrave; <b> <?php echo $pourcentage ?> % </b> de validation des Unités d'Enseignements sanctionnant l'obtention du grade de <b> <?php echo $tete[0]['libelleGrade'] ?> </b> dans la spécialité <b> <?php echo $tete[0]['libelleSpe'] ?> </b> du Département <b> <?php echo $tete[0]['libelleMention'] ?> </b> avec une moyenne de <b> <?php echo number_format($moyenne, 2, ',', ' ')  ?> </b>sur 20 correspondant &agrave; la mention <b> <?php echo $ment  ?></b>.
                    <br /> En foi de quoi, la présente attestation de diplôme lui est délivrée pour servir et valoir ce que de droit.
                </p>
                <div style="text-align: right;">Fait à <?=$univ[0]['ville']?>; le <?=date('d/m/Y')?></div>
                <!-- <div style="text-align:left">Le Directeur Adjoint,</div>
                <div style="text-align: right;">Le Directeur,</div> -->
                <table style="width: 100%;margin-top: 10px;">
                    <tr>
                        <td style="width: 50%;">Le Directeur Adjoint,<br><br><strong><u><?=$univ[0]['directeurAdjoint']?></u></strong></td>
                        <td style="width: 50%;text-align:right">Le Directeur,<br><br><strong><u><?=$univ[0]['directeur']?></u></strong></td>
                    </tr>
                </table>
            </div>
            <!--Bas de page-->



            <?php
            //  var_dump($donnes);
            ?>


        </page>


        <?php
        $strHtml = ob_get_clean();
        try {

            // require('../html2pdf/html2pdf.class.php');
            $tabMarges = array(10, 10, 10, 10);
            $objPDF = new HTML2PDF('L', 'A4', 'fr');
            $objPDF->pdf->SetDisplayMode('fullpage');
            $objPDF->pdf->SetAuthor('Dean Kong A Siou');
            $objPDF->writeHTML($strHtml);
            $dateNow = new DateTime('now');
            $dateNow = $dateNow->format('Y.m.d.H.i.s');
            $objPDF->Output('Attestation_ ' . $tete[0]['libelleGrade'] . '_' . UTF8_encode($etudiant[0]['noms']) . '_' . UTF8_encode($etudiant[0]['prenoms']) . '.pdf');
        } catch (Exception $e) {
            abort(500, 'erreur lors de la création du pdf !');
        }
    }

    public function impressionDiplome() {
        $donnes = json_decode(request("Imp_Diplome"), true);
        extract($donnes);
        $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
        // echo var_dump($_GET);
        // exit();
        $tete = $this->convert_array(DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anEtude'"));
        $etudiant = $this->convert_array(DB::select("SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms,e.dateNaisEtu datenais,e.lieuNaisEtu lieunais,e.sexeEtu sexe,e.codeNationalite nation FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = $anneeaca and `ID_FILIERE` = '$anEtude' and e.MATRICULE = '$matricule' ORDER BY noms,prenoms ASC;"));
        //  $nation=$mysql->national_cod($etudiant[0]['nation']);
        $anac = $this->convert_array(DB::select("SELECT *  FROM `anneeacademique` WHERE `id_Annee_Acad` = $anneeaca"));
        ob_start();  ?>

        <page backtop="60mm" backbottom="4mm" backleft="2mm" backright="2mm">

            <page_header>
                <br><br><br><br>
                <div style="width: 98%;">
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="0">

                        <tr>
                            <td style="width: 15%;padding: right 30px;vertical-align: middle; text-align: right"><img style="width: 120px; height: 120px;" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:71.5%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                <b style="color:RED;font-size: 20px">DIPLÔME DE <?php echo strtoupper($tete[0]['libelleGrade']) . ' ' ?><?php echo $tete[0]['libelleGrade'] == "Licence" ? "PROFESSIONNELLE <br>" : "PROFESSIONNEL <br>" ?> </b>

                                <b style="font-size: 12px; margin:auto;"><?php echo '<br><br> N°____________ - ' . date('Y') . '/UAC/INEPS/DA/SGE/SS'; ?></b>
                            </td>
                            <td style="width: 15%;vertical-align: middle; text-align: left"><img style="width: 120px; height: 120px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>
                        <tr>
                            <td colspan="3" style="vertical-align: middle; text-align: justify">
                                <div style="width: 90%;margin-left:50px;">
                                    <p style="text-align: justify; line-height: 15px;font-size:11px;">
                                        Vu le décret n°96-550 du 6 décembre 1996 portant création, organisation et fonctionnement de l’Institut national de la Jeunesse, de l’Education physique et du Sport (INJEPS) ;<br />
                                        Vu l’arrêté interministériel année 2021 n°144/MESRS/MESTFP/DC MESRS/DC MESTFP/SGM MESRS/SGM/MESTFP/D/PIQ/DESG/DETFP/MESRS/SA002SGG21 portant Règlement pédagogique des Ecoles normales supérieures (ENS / ENSET) et des Instituts publics et privés de Formation des Enseignants de l’Enseignement du Second Degré ;<br />
                                        Vu le procès-verbal n°___________/UAC/INEPS/DA/SGE/SS/SA en date du <?php echo $datedelib ?>
                                    </p>
                                </div>
                            </td>

                        </tr>

                    </table>

                </div>
            </page_header>
            <page_footer>

                <div style="width: 100%; margin: 0px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; vertical-align: middle; "></div>
                    <div style="display: inline; width: 58%; text-align: center;font-size: 8px">
                        <?php echo $tete[0]['libelleGrade'] == "Licence" ? '' : '<p> <b style="display: inline;text-align: center;font-size: 11px"> Enregistrée au rectorat le .......................... sous le numéro................./UAC/VR-AA/SA</b></p>' ?>
                        Copyright © <?=now()->year?> LMD Web Solutions All rights reserved. <br><br><br><br><br><br>
                    </div>

                </div>
                <!--div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; ">www.ens.uac.bj</div>
                    <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © 2022 LMD Web Solutions All rights reserved.<br>
                        01 BP 583 Porto-Novo Tel: (229) 20 22 26 64 email:injeps@uac.bj Avakpa Kpodji, Porto-Novo République du Benin s
                    </div>
                    <div style="display: inline; width: 25%; text-align: right">ENS-PORTO/NOVO</div>
                </div -->
            </page_footer>

            <div style="width: 100%;margin-left: 44px;margin-bottom: 20px;margin-top: 65px ;font-size: 14px;">
                <p style="text-align: justify; line-height: 30px">
                    <?php $ment = "";
                    if ($moyenne >= 18) {
                        $ment = "Excellent";
                    } elseif ($moyenne >= 16 && $moyenne < 18) {
                        $ment = " Très - Bien";
                    } elseif ($moyenne >= 14 && $moyenne < 16) {
                        $ment = " Bien";
                    } elseif ($moyenne >= 12 && $moyenne < 14) {
                        $ment = " Assez - Bien";
                    } elseif ($moyenne >= 10 && $moyenne < 12) {
                        $ment = " Passable";
                    } elseif ($moyenne == 10) {
                        $ment = " Passable";
                    } elseif ($moyenne < 10) {
                        $ment = " insuffisant";
                    } ?>
                    Il est délivré à <?=$etudiant[0]['sexe']=='M'?'Monsieur':'Mademoiselle/Madame'?> <strong><?=$etudiant[0]['noms']?> <?=$etudiant[0]['prenoms']?></strong><br>
                    de nationalité <?=Nationalite::find($etudiant[0]['nation'])->libelleNationalite?> né(e) le <?=Carbon::parse($etudiant[0]['datenais'])->format('d/m/Y')?> à <?=$etudiant[0]['lieunais']?><br>inscrite à <?=$univ[0]['nom']?> sous le numéro matricule <?=$etudiant[0]['matricule']?>
                </p>
                <div style="text-align: center;margin-right: 20px;">
                    LE DIPLÔME DE <?php echo strtoupper($tete[0]['libelleGrade']) . ' ' ?><?php echo $tete[0]['libelleGrade'] == "Licence" ? "PROFESSIONNELLE <br>" : "PROFESSIONNEL <br>" ?>
                </div>
                <table style="width:100%;margin-top: 10px;">
                    <tr style="width: 94%;">
                        <td style="width: 33%;text-align: left;">Spécialité : <?=$tete[0]['libelleSpe']?></td>
                        <td style="width: 38%;text-align: center;">Option : <?=$tete[0]['Libelle_AnEtude']?></td>
                        <td style="width: 30%;text-align: right;">Côte : <?=$ment?></td>
                    </tr>
                </table>
                <div style="margin-top: 10px;">En foi de quoi, le présent diplôme lui est délivré pour servir et valoir ce que de droit.</div>
                <table style="width: 94.2%;margin-top: 40px;">
                    <tr>
                        <td style="width: 50%;">Le Directeur de <?=$univ[0]['Ecol_Ufr']?>,<br><br><br><br><strong><u><?=$univ[0]['directeur']?></u></strong></td>
                        <td style="width: 50%;text-align:right">Le Recteur de l'<?=$univ[0]['nom']?>,<br><br><br><br><strong><u><?=$univ[0]['recteur']?></u></strong></td>
                    </tr>
                </table>
            </div>



        </page>


        <?php
        $strHtml = ob_get_clean();
        try {

            // require('../html2pdf/html2pdf.class.php');
            $tabMarges = array(10, 10, 10, 10);
            $objPDF = new HTML2PDF('L', 'A4', 'fr');
            $objPDF->pdf->SetDisplayMode('fullpage');
            $objPDF->pdf->SetAuthor('Dean Kong A Siou');
            $objPDF->writeHTML($strHtml);
            $dateNow = new DateTime('now');
            $dateNow = $dateNow->format('Y.m.d.H.i.s');
            $objPDF->Output('Attestation_ ' . $tete[0]['libelleGrade'] . '_' . UTF8_encode($etudiant[0]['noms']) . '_' . UTF8_encode($etudiant[0]['prenoms']) . '.pdf');
        } catch (Exception $e) {
            abort(500, 'erreur lors de la création du pdf !');
        }
    }

    public function impressionUesNonValidees(){
        $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
        //sélectionner les étudiants
        $etud = $this->convert_array(DB::select("SELECT recap_annuel.MATRICULE as MATRICULE, etudiant.nomEtu as nomEtu, etudiant.prenomsEtu as prenomsEtu FROM recap_annuel, etudiant WHERE recap_annuel.MATRICULE=etudiant.MATRICULE AND recap_annuel.id_Annee_Acad=" . $_GET['annee_acad_non_val'] . " AND recap_annuel.Code_Annee_Etude='" . $_GET['annee_etude_non_val'] . "' AND recap_annuel.DecisionJury != 'V' ORDER BY nomEtu, prenomsEtu ASC;"));

        if (!empty($_GET['annee_acad_non_val']) && (trim($_GET['annee_acad_non_val']) != "") && !empty($_GET['annee_etude_non_val']) && (trim($_GET['annee_etude_non_val']) != "")) {
            $temp1 = $this->convert_array(DB::select("SELECT Libelle_AnEtude FROM annee_etude WHERE Code_Annee_Etude='" . $_GET['annee_etude_non_val'] . "';"));
            $temp2 = $this->convert_array(DB::select("SELECT ANNEE_ACAD FROM anneeacademique WHERE id_Annee_Acad=" . $_GET['annee_acad_non_val'] . ";"));
        }

        ob_start();
        ini_set('max_execution_time', 600);
        ?>
        <page backtop="47mm" backbottom="12mm" backleft="2mm" backright="2mm" style="text-align:'center';">
            <page_header>
                <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                    <tr>
                        <td style="width: 10%; margin-top:0px"><img style="width: 150px; height: 150px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:62%; text-align: center; font-size:14px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(UTF8_encode(strtoupper($univ[0]['Ecol_Ufr']))) ?><br>
                            ----*----*----*---*---<br>
                            UE non validées pour <?php echo ($_GET['annee_etude_non_val'] . " | " . $temp2[0]["ANNEE_ACAD"]); ?><br>
                            ----*----*----*---*---<br><br><br>
                        </td>
                        <td style="width: 25%;vertical-align: middle; text-align: left"><img style="width: 150px; height: 150px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>
                    </tr>

                </table>
            </page_header>
            <page_footer>
                <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                    <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © 2022 LMD Web Solutions Tous droits réservés.<br>
                        <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                    </div>
                    <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                </div>
            </page_footer>

            <br />
            <br />

            <table id="liste" class="display" style="width:100%;  font-size: 12px; border-collapse: collapse; margin: top 10px; margin:auto;" border="1">
                <thead>
                    <tr style="text-align : center;">
                        <th>Matricules</th>
                        <th>Noms et Prénoms</th>
                        <th>Unités d'Enseignement (Semestres)</th>
                        <th>Moyenne UE</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                    for ($i = 0; $i < count($etud); $i++) {
                        //sélectionner les IdUE non validées précise et leurs moyennes
                        $idue = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem, Moyenne_UE FROM recap_ue WHERE MATRICULE=" . $etud[$i]["MATRICULE"] . " AND Code_Annee_Etude='" . $_GET['annee_etude_non_val'] . "' AND Etat_UE!='V';"));

                        $nbr = count($idue);
                    ?>
                        <?php if ($nbr > 1) { ?>
                            <?php for ($j = 0; $j < count($idue); $j++) { ?>
                                <tr>
                                    <?php if ($j == 0) { ?>
                                        <td style="width: 80px;" rowspan=<?php echo $nbr . ">" . $etud[$i]['MATRICULE']; ?></td>
                                        <td style="width: 200px;" rowspan=<?php echo $nbr . ">" . $etud[$i]['nomEtu'] . " " . $etud[$i]['prenomsEtu']; ?></td>
                                        <?php } ?>
                                        <td style="width: 300px;">
                                            <?php
                                            //sélectionner le libelleUe de chaque IdUE non validées
                                            $libel = $this->convert_array(DB::select("SELECT ue.libelleUe as libelleUe FROM attr_ue_classe, ue WHERE Id_Attr_ue_Sem=" . $idue[$j]["Id_Attr_ue_Sem"] . " AND id_Annee_Acad=" . $_GET['annee_acad_non_val'] . " AND ue.codeUe=attr_ue_classe.codeUe ;"));

                                            $semUE = $this->convert_array(DB::select("SELECT classe.Nom_Classe as semestre FROM recap_ue, attr_ue_classe, classe WHERE recap_ue.Id_Attr_ue_Sem=" . $idue[$j]["Id_Attr_ue_Sem"] . " AND recap_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem AND attr_ue_classe.id_S_Gr_Spe=classe.id_S_Gr_Spe ;"));

                                            echo $libel[0]["libelleUe"] . " (<b>" . $semUE[0]['semestre'] . "</b>)";
                                            ?>
                                        </td>
                                        <td style="width: 20px; text-align: center"><?php echo ($idue[$j]["Moyenne_UE"]) ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td style="width: 80px;"><?php echo $etud[$i]['MATRICULE']; ?></td>
                                <td style="width: 200px;" rowspan=<?php echo $nbr . ">" . $etud[$i]['nomEtu'] . " " . $etud[$i]['prenomsEtu']; ?></td>
                                <td style="width: 300px;">
                                    <?php
                                    //sélectionner le libelleUe de chaque IdUE non validées
                                    $libel = $this->convert_array(DB::select("SELECT ue.libelleUe as libelleUe FROM attr_ue_classe, ue WHERE Id_Attr_ue_Sem=" . $idue[0]["Id_Attr_ue_Sem"] . " AND id_Annee_Acad=" . $_GET['annee_acad_non_val'] . " AND ue.codeUe=attr_ue_classe.codeUe ;"));
                                    $semUE = $this->convert_array(DB::select("SELECT classe.Nom_Classe as semestre FROM recap_ue, attr_ue_classe, classe WHERE recap_ue.Id_Attr_ue_Sem=" . $idue[0]["Id_Attr_ue_Sem"] . " AND recap_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem AND attr_ue_classe.id_S_Gr_Spe=classe.id_S_Gr_Spe ;"));

                                    echo $libel[0]["libelleUe"] . " (<b>" . $semUE[0]['semestre'] . "</b>)";
                                    ?>
                                </td>
                                <td style="width: 20px; text-align: center"><?php echo ($idue[0]["Moyenne_UE"]) ?></td>
                            </tr>
                        <?php } ?>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </page>
        <?php
        $strHtml = ob_get_clean();
        try {
            $tabMarges = array(10, 10, 10, 10);
            $objPDF = new HTML2PDF('P', 'A4', 'fr');
            $objPDF->pdf->SetDisplayMode('fullpage');
            $objPDF->pdf->SetAuthor('Dean Kong A Siou');
            $objPDF->writeHTML($strHtml);
            $dateNow = new DateTime('now');
            $dateNow = $dateNow->format('Y.m.d.H.i.s');
            $objPDF->Output('Cursus_' . $temp1[0]["Libelle_AnEtude"] . '_' . $temp2[0]["ANNEE_ACAD"] . '.pdf');
        } catch (Exception $e) {
            abort(500, 'Erreur lors de la création du document.');
        }
    }

    public function impressionEcuesSemestre(){
        $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
        $rc = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.codeUe as codeUe ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe` AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.`id_Annee_Acad`=" . $_POST['anneeacademique'] . " AND attr_ue_classe.`id_S_Gr_Spe`=" . $_POST['semestre'] . " ORDER BY `classe`.`Nom_Classe` ASC"));
        ob_start();
        ini_set('max_execution_time', 600);
        ?>
        <page backtop="45mm" backbottom="12mm" backleft="2mm" backright="2mm" style="text-align:'center';">
            <page_header>
                <table style="width:98%;" cellpadding="0" cellspacing="0">

                    <tr>
                        <td style="width: 10%"><img style="width: 150px; height: 150px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:65%; text-align: center; font-size:14px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                            ----*----*----*---*---<br>
                            TABLE DE SPECIFICATION DU SEMESTRE : <?php
                                                                    $anne = $rc[0]['ANNEE_ACAD'];
                                                                    $cl =  $rc[0]['Nom_Classe'];
                                                                    echo $cl . '  pour le compte de l\'année: ' . $anne; ?><br>
                            ----*----*----*---*---<br><br><br>
                        </td>
                        <td style="width: 10%;vertical-align: middle; text-align: left"><img style="width: 150px; height: 150px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                    </tr>

                </table>
            </page_header>
            <page_footer>
                <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                    <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->year?> LMD Web Solutions Tous droits réservés.<br>
                        <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                    </div>
                    <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                </div>
            </page_footer>

            <br />
            <br />
            <br />
            <br />
            <table id="liste" class="display" style="width:100%;font-size: 12px; margin:auto;" border="1">
                <thead>
                    <tr>
                        <th>Unité d'Enseignement ( <b>Credit</b> ) </th>
                        <th>Elts. Cons. d'UE (ECUE) </th>
                        <th>Pondération </th>
                        <th>Masse <br> horaire </th>
                        <th>Enseignant <br> Responsable </th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($rc); $i++) {
                        $ecue = $this->convert_array(DB::select("SELECT attr_ecue_ue.codeEcu AS codeEcu, ecue.libelleEcu AS libelleEcu, attr_ecue_ue.Ponderation AS Ponderation, attr_ecue_ue.MasseHoraire AS MasseHoraire, professeur.nomProf AS nomProf, professeur.prenomsProf AS prenomsProf FROM attr_ue_classe, attr_ecue_ue, ecue, professeur WHERE attr_ue_classe.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND attr_ecue_ue.numeroProf =professeur.numeroProf AND attr_ecue_ue.codeEcu=ecue.codeEcu AND attr_ecue_ue.Id_Attr_ue_Sem=" . $rc[$i]["Id_Attr_ue_Sem"] . " ORDER BY libelleEcu ASC"));

                        $nbr = count($ecue);
                    ?>
                        <?php if ($nbr > 1) { ?>
                            <?php for ($j = 0; $j < count($ecue); $j++) { ?>
                                <tr>
                                    <?php if ($j == 0) { ?>
                                        <td style="width: 280px;" rowspan=<?php echo $nbr . ">" . ($rc[$i]['libelleUe']) . "( <b>" . $rc[$i]['Credit'] . "</b> )"; ?></td>
                                        <?php } ?>
                                        <td style="width: 250px;"><?php echo ($ecue[$j]["libelleEcu"]) ?></td>
                                        <td style="text-align: center"><?php echo (($ecue[$j]["Ponderation"]) * 100) . '%' ?></td>
                                        <td style="text-align: center;width: 20px"><?php echo ($ecue[$j]["MasseHoraire"]) . 'h' ?></td>
                                        <td style="text-align: center;"><?php echo utf8_decode($ecue[$j]["nomProf"] . " " . $ecue[$j]["prenomsProf"]) ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td style="width: 280px;"><?php echo $rc[$i]['libelleUe'] . "( <b>" . $rc[$i]['Credit'] . "</b> )"; ?></td>
                                <td style="width: 250px;"><?php echo $ecue[0]["libelleEcu"] ?></td>
                                <td style="text-align: center"><?php echo (($ecue[0]["Ponderation"]) * 100) . '%' ?></td>
                                <td style="text-align: center;width: 20px"><?php echo ($ecue[0]["MasseHoraire"]) . 'h' ?></td>
                                <td style="text-align: center;"><?php echo $ecue[0]["nomProf"] . " " . $ecue[0]["prenomsProf"] ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </page>
        <?php
        $strHtml = ob_get_clean();
        try {
        // require('../html2pdf/html2pdf.class.php');
            $tabMarges = array(10, 10, 10, 10);
            $objPDF = new HTML2PDF('P', 'A4', 'fr');
            $objPDF->pdf->SetDisplayMode('fullpage');
            $objPDF->pdf->SetAuthor('Dean Kong A Siou');
            $objPDF->writeHTML($strHtml);
            $dateNow = new DateTime('now');
            $dateNow = $dateNow->format('Y.m.d.H.i.s');
            $objPDF->Output('TS_' . $anne . '_' . $cl . '.pdf');
        } catch (Exception $e) {
            abort(500, 'Erreur lors de la création du document.');
        }
    }

    public function impressionUe()
    {
        if (request()->btn=="Imprimer") {
            request()->validate([
                'anneeEtude'=>'required',
                'anneeAcademique'=>'required',
                'ue'=>'required',
                'semestreEtude'=>'required',
            ]);
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')) || empty(request('ue')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            extract($this->extraireEntrees());
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,Ponderation,PoidsDev,PoidsPart,attr_ue_classe.`Seuil_Ue` as Seuil_Ue FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"); //echo count($rc);
            $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
            ob_start();
            ini_set('max_execution_time', 600); ?>
            <page backtop="48mm" backbottom="10mm" backleft="2mm" backright="2mm" pagegroup="new" style="font-family: freeserif;">
                <page_header>
                    <table style="width:100%; margin:auto" cellpadding="0" cellspacing="0" border="0">

                        <tr>
                            <td style="width: 12%;vertical-align: top; text-align: left;"><img style="width: 100px; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]->logo?>" /></td>
                            <td style="width:75%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]->nom) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo (strtoupper($univ[0]->Ecol_Ufr)) ?><br>
                                ----*----*----*---*---<br>
                                RECAPITULATIF PAR UE<br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 12.5%;vertical-align: top; text-align: left"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]->logo_ufr?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]->Site_web) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]->Adres_Post) ?> Tel: <?php echo strtoupper($univ[0]->Tel) ?> email: <?php echo ($univ[0]->mail) . ",    " . $univ[0]->obs ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <div style="text-align: center; font-size: 10px">
                    Domaine: <b><?php echo $tete[0]->lib_domaine ?></b> ---@@@--- Mention: <b><?php echo $tete[0]->libelleMention ?></b> Spécialité:<b><?php echo $tete[0]->libelleSpe ?></b> ---@@@--- Grade: <b><?php echo $tete[0]->libelleGrade ?></b> ---@@@--- Année Académique:<b><?php echo  $rc[0]->ANNEE_ACAD ?></b> ---@@@--- Année Etude: <b><?php echo  $tete[0]->Libelle_AnEtude ?></b>
                </div>

                <div style=" margin:auto;">

                        <table id="liste" class="display" style="width:100%; margin:auto; table-layout: fixed; " border="1px">

                            <thead class="thead">
                                <tr style="background-color: #0a6aa1;color: white;">
                                    <th rowspan="2" colspan="2" style="text-align: center; vertical-align:middle; width: 150px"><?php echo $rc[0]->libelleUe ?></th>
                                    <?php
                                    $aColsEval = [];
                                    for ($i = 0; $i < count($rc); $i++) {
                                        $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$i]->ecue . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                                        $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$i]->ecue . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
                                        $nDev = DB::select($sqlDev);
                                        $nDev = (int)$nDev[0]->nbre;
                                        $nPart = DB::select($sqlPart);
                                        $nPart = (int)$nPart[0]->nbre;
                                        $aColsEval[$rc[$i]->ecue] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$i]->Ponderation, 'pdev' => $rc[$i]->PoidsDev, 'ppart' => $rc[$i]->PoidsPart);
                                    ?>
                                        <th colspan="<?php echo ($nDev + ($nDev == 0 ? 0 : 1)) + ($nPart + 1) + 1; ?>" style="text-align: center; width: 20%"> <?php echo $rc[$i]->libelleEcu; ?></th>
                                        <th style="text-align: center;"><?php echo ($rc[$i]->Ponderation) * 100; ?>%</th>
                                    <?php
                                    } ?>
                                    <th rowspan=2 style="text-align: center;">MOY.<br> UE </th>
                                </tr>
                                <tr style="background-color: #1a237e; color: white;">
                                    <?php
                                    foreach ($aColsEval as $col) { //var_dump($col);
                                        for ($i = 0; $i < $col['dev']; $i++) { ?><th style="width: 15px;text-align: center;"><?php echo "D" . ($i + 1); ?></th> <?php }
                                                                                                                                                            if ($col['dev'] !== 0) { ?><th style="width: 15px;text-align: center;">MD</th><?php }
                                                                                                                                                                                                                                        for ($i = 0; $i < $col['part']; $i++) { ?><th style="width: 15px;text-align: center;"><?php echo "P" . ($i + 1); ?></th> <?php } ?>
                                        <th style="width: 15px;text-align: center;">MP</th>
                                        <th style="width: 15px;text-align: center;">Rattr</th>
                                        <th style="width: 15px;text-align: center;">M Ecue</th><?php
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
                                $sv=$snv=0;
                                for ($e = 0; $e < count($listeE); $e++) {
                                    extract($listeE[$e]); ?>
                                    <tr>
                                        <td style="width: 60px;height: 20px; vertical-align: middle"> <?php echo  $matricule ?></td>
                                        <td style="width: 180px;height: 20px; vertical-align: middle"> <?php echo "<b style='text-transform: uppercase'>" . ($noms) . "</b> " . ($prenoms) ?></td>

                                        <?php
                                        $moyenne = 0.0;
                                        $tou = 0;

                                        foreach ($aColsEval as $key => $col) { //var_dump($col);
                                            //DEVOIRS
                                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                                            $r = DB::select($listeN);
                                            $tou = count($r);
                                            if ($tou == 0 && $col['dev'] !== 0) {
                                                echo '<td style="text-align: center;" > Abs </td>';
                                            } else {
                                                for ($i = 0; $i < count($r); $i++) { ?><td style="width: 25px;text-align: center;"><?php echo number_format($r[$i]->Note, 2, ',', ' '); ?></td> <?php }
                                            }

                                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                                            $r = DB::select($listeN);
                                            $md = floatval($r[0]->MD);
                                            if ($col['dev'] !== 0) { ?><td style="width: 25px;text-align: center;"><?php echo number_format($md, 2, ',', ' '); ?></td><?php }
                                            //PARTIEL
                                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                                            $r = DB::select($listeN);
                                            $tou = count($r);
                                            if ($tou == 0 && $col['part'] !== 0) {
                                                echo '<td style="width: 25px;text-align: center;" > Abs </td>';
                                            } else {
                                            for ($i = 0; $i < count($r); $i++) { ?><td style="width: 25px;text-align: center;"><?php echo number_format($r[$i]->Note, 2, ',', ' '); ?></td> <?php }
                                            }
                                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                                            $r = DB::select($listeN);
                                            $mp = floatval($r[0]->MD);
                                            $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                                            $r = DB::select($listeN);
                                            $mR = floatval($r[0]->MD);
                                            // echo "$matricule == $md, $key --- $listeN<br>";
                                            ?><td style="width: 25px;text-align: center;"><?php echo number_format($mp, 2, ',', ' '); ?></td>
                                            <td style="width: 25px;text-align: center;"><?php $rattr = $mR;
                                                                                        echo (int)$rattr === 0 ? "-" : number_format($rattr, 2, ',', ' '); ?></td>
                                            <td style="width: 25px;text-align: center;"><?php $mecue = floatval((int)$md === 0 && $col['dev']==0 && $col['dev'] == 0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                                                                        $mecue = max($rattr, $mecue);
                                                                                        echo number_format($mecue, 2, ',', ' '); ?></td><?php
                                                                                                                                        $moyenne += $mecue * $col['ponderation'];
                                                                                                                                    } ?>
                                        <td style="width: 25px;text-align: center"><?php echo number_format($moyenne, 2, ',', ' ');
                                                round($moyenne,3)>=$rc[0]->Seuil_Ue ?  $sv=$sv+1 : $snv=$snv+1;
                                        ?></td>



                                    </tr> <?php
                                }  ?>
                            </tbody>
                        </table>
                        <br> Nombre d'étudiants <b> ayant validé  l'Unité d'Enseignement :</b>:<b> <?php echo $sv; ?></b>  soit un pourcentage de  : <b> <?php if($sv+$snv==0){ echo ($sv/$sv+$snv)*100 .'%';}else{echo '0%';} ?></b>
                        <br> Nombre d'étudiants <b>n'ayant pas validé l'Unité d'Enseignement</b> : <b><?php echo $snv; ?></b>  soit un pourcentage de  : <b> <?php if(($sv+$snv)==0){echo ($snv/$sv+$snv)*100 .'%';}else{echo '0%';} ?></b>
                        <br> Fait à <b><?=$univ[0]->ville?></b>; le <?php echo ' '.date('d/m/Y'); ?>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {
                $tabMarges = array(5, 20, 5, 20);

                $html2pdf = new HTML2PDF('P', 'A4', 'fr', true, 'UTF-8', $tabMarges);
                $html2pdf->pdf->SetDisplayMode('fullpage');
                $html2pdf->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $html2pdf->output('RECAP ' . $rc[0]->libelleUe . '_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->value('ANNEE_ACAD') . '.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }
        if (request()->btn=="Imprimer reprises") {
            # code...
            extract($this->extraireEntrees());
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,attr_ue_classe.Seuil_Ue as Seuil_Ue,libelleEcu,Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"); //echo count($rc);
            $rc=array_map(function ($value) {
                return (array)$value;
            }, $rc);
            $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
            $tete=array_map(function ($value) {
                return (array)$value;
            }, $tete);
            ob_start(); ?>
            <page backtop="45mm" backbottom="12mm" backleft="2mm" backright="2mm" pagegroup="new" style="font-family: freeserif;">
                <page_header>
                    <table style="margin:auto; margin-left: 70px;" cellpadding="0" cellspacing="0" border="0">

                        <tr>
                            <td style="width: 15%;vertical-align: top; text-align: left"><img style="width: 150px; height: 150px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:50%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                FICHE DE NOTES DES ETUDIANTS AYANT
                                UNE REPRISE DANS L'UNITE<br>
                                ----*----*----*---*---<br>
                            </td>
                            <td style="width: 25%;vertical-align: middle; text-align: left"><img style="width: 120px; height: 120px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>

                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <div style="text-align: center; font-size: 10px">
                    <br><br> Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Mention: <b><?php echo $tete[0]['libelleMention'] ?></b> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $rc[0]['ANNEE_ACAD'] ?></b> ---@@@--- Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?></b>
                </div>

                <div>

                        <span style="background-color: RED; color:White; font-size:14px;margin:auto"> ECUE A RATTRAPER </span>
                        <table id="liste" class="display" style="width:100%; margin:auto; " border="1px">

                            <thead class="thead">
                                <tr style="background-color: #0a6aa1;color: white;">
                                    <th rowspan="2" style="text-align: center;width: 150px;vertical-align: middle;"><?php echo $rc[0]["libelleUe"] ?></th>
                                    <?php
                                    $aColsEval = [];
                                    for ($i = 0; $i < count($rc); $i++) {
                                        $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$i]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                                        $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$i]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
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
                                        $aColsEval[$rc[$i]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$i]["Ponderation"], 'pdev' => $rc[$i]["PoidsDev"], 'ppart' => $rc[$i]["PoidsPart"]);
                                    ?>
                                        <th colspan="<?php echo ($nDev + ($nDev == 0 ? 0 : 1)) + ($nPart + 1) + 1; ?>" style="text-align: center; width: 20%;vertical-align: middle;"> <?php echo $rc[$i]["libelleEcu"]; ?></th>
                                        <th style="text-align: center;vertical-align: middle;"><?php echo ($rc[$i]["Ponderation"]) * 100; ?>%</th>
                                    <?php
                                    } ?>
                                    <th rowspan=2 style="text-align: center;vertical-align: middle;">MOY.<br> UE </th>
                                    <th rowspan=2 style="text-align: center;vertical-align: middle;">ETAT<br> UE </th>
                                    <th rowspan=2 style="text-align: center;vertical-align: middle;">Décision </th>
                                </tr>
                                <tr style="background-color: #1a237e; color: white;">
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

                                    <?php
                                    $moyenne1 = 0.0;
                                    foreach ($aColsEval as $key => $col) { //var_dump($col);
                                        //DEVOIRS
                                        $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                                        $r = DB::select($listeN);
                                        $r=array_map(function ($value) {
                                            return (array)$value;
                                        }, $r);
                                        $md = floatval($r[0]['MD']);
                                        //PARTIEL
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
                                        $rattr = $mR;
                                        // echo "$matricule == $md, $key --- $listeN<br>";
                                    ?> <?php $mecue1 = floatval((int)$md === 0 && $col['dev']==0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                        $mecue1 = max($rattr, $mecue1);
                                        ?><?php
                                            $moyenne1 += $mecue1 * $col['ponderation'];
                                        }
                                        if (round($moyenne1, 3) < $rc[0]['Seuil_Ue']) {
                                            ?>

                                        <tr style="font-size:11px">

                                            <td style="width: 150px"><?php echo "<b style='text-transform: uppercase'>$noms</b> $prenoms" ?></td>
                                            <?php
                                            $moyenne = 0.0;
                                            $tou = 0;
                                            foreach ($aColsEval as $key => $col) { //var_dump($col);
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
                                                    for ($i = 0; $i < count($r); $i++) { ?><td style="width: 10px;text-align: center;"><?php echo $r[$i]['Note']; ?></td> <?php }
                                                              }
                                                               $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue';";
                                                               $r = DB::select($listeN);
                                                               $r=array_map(function ($value) {
                                                                return (array)$value;
                                                                }, $r);
                                                               $md = floatval($r[0]['MD']);
                                                                if ($col['dev'] !== 0) { ?><td style="width: 10px !important;text-align: center;"><?php echo number_format($md, 2, ',', ' '); ?></td><?php }
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
                                                ?><td style="width: 10px !important;text-align: center;"><?php echo number_format($mp, 2, ',', ' '); ?></td>
                                                <td style="width: 1px !important;text-align: center;"><?php $rattr = $mR;
                                                echo (int)$rattr === 0 ? "N/A" : number_format($rattr, 2, ',', ' '); ?></td>
                                                <td style="text-align: center;"><?php $mecue = floatval((int)$md === 0 && $col['dev']==0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                                $mecue = max($rattr, $mecue);
                                                echo $mecue < $rc[0]['Seuil_Ecu'] ? '<b style="background-color: RED; color:White; font-size:14px">' . number_format($mecue, 2, ',', ' ') . '</b>' : number_format($mecue, 2, ',', ' '); ?></td><?php
                                                $moyenne += $mecue * $col['ponderation'];
                                                } ?>
                                            <td style="text-align: center"><?php echo number_format($moyenne, 2, ',', ' '); ?></td>
                                            <td style="text-align: center"><?php //echo number_format($moyenne,2,',',' ');
                                            echo round($moyenne, 3) >= $rc[0]['Seuil_Ue'] ? "V" : "NV";
                                            ?></td>
                                        </tr>
                                <?php
                                        }
                                    }

                                ?>
                            </tbody>
                        </table>

                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('L', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('NON_VALIDER_RECAP ' . $rc[0]["libelleUe"] . '_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->value('ANNEE_ACAD') . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
            }
        }
    }

    public function impressionSemestre()
    {
        if (request()->btn=="Session normale" || request()->btn=="Imprimer") {
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            extract($this->extraireEntrees());
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.id_S_Gr_Spe='$semestreEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique';");
            if(empty($UE))
                abort(404);
            $UE=array_map(function ($value) {
                return (array)$value;
            }, $UE);
            $rTaux = DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;");
            $rTaux=array_map(function ($value) {
                return (array)$value;
            }, $rTaux);
            $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
            $tete=array_map(function ($value) {
                return (array)$value;
            }, $tete);
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $anne = $UE[0]['an'];
            $cl =  $UE[0]['clas'];
            // var_dump($UE[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
            <page backtop="10mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                        <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right"> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <table style="width:100%; margin:auto;" cellpadding="0" cellspacing="0" border="">

                    <tr>
                        <td style="width: 15%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:60%; text-align: center; font-size:25px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                            ----*----*----*---*---<br>
                            PROCES VERBAL DE DELIBERATION SEMESTRIELLE (SESSION NORMALE)<br>
                            ----*----*----*---*---<br>
                        </td>
                        <td style="width: 15%;vertical-align: middle; text-align: right"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                    </tr>

                </table>
                <div style="text-align: center;font-size: 25px;">
                    Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Département: <b><?php echo $tete[0]['libelleMention'] ?></b> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> <br> Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?> - Semestre <?=SemestreEtude::find($semestreEtude)->numeroSemestre?></b>
                </div>
                <div >
                    <br />
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px; ">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo '<b>' . $UE[$i]['ID_UE'] . "==>" . $UE[$i]['libelleUe'] . '</b>(' ?>
                    <?php
                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            $recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue);
                                            for ($iecuee = 0; $iecuee < count($recue); $iecuee++) {
                                                echo $recue[$iecuee]['ecue'] . ":" . $recue[$iecuee]['libelleEcu'] . ' ';
                                            }
                                            echo ')';
                                        }    ?>
                    ---@@@--- V = Validé --- NV = Non Validé -- * Rattrapage -- ** Reprise
                    </p>
					<p style="text-align:center; font-size: 20px ">
					Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
					</p>
                    <table id="liste" class="display" style="width:1000%; margin:auto; font-size:26px;" border="1">
                        <thead>
                            <tr style="background-color:#7BA7CE">
                                <th colspan="2" rowspan="2" style="text-align: center; font-size: 25px; vertical-align: middle;"> Semestre:<br> <?php echo $UE[0]['Nom_Classe']; ?></th>
                                <?php
                                $tCredit = 0;
                                for ($i = 0; $i < count($UE); $i++) {
                                   // $unie=$UE[$i]['ID_UE'];
                                  //  $$unie=0;
                                    $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                    $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $recue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $recue);
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
                                    $rcue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$cue_af AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $rcue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $rcue);
                                    for ($iae = 0; $iae < count($rcue); $iae++) {
                                ?>
                                        <th style="text-align: center"><?php echo $rcue[$iae]['ecue'] . '<br>' . $rcue[$iae]['Ponderation'] * 100 . '%'; ?></th> <?php } ?>
                                    <th style="text-align: center;vertical-align: middle;background-color:#7BA7CE">Moy</th>
                                    <th style="text-align: center;vertical-align: middle;background-color:#7BA7CE">Obs</th>
                                <?php

                                } ?>

                            </tr>
                        </thead>
                        <tbody> <?php
                                if(isset($selected_students)){
                                    //dd($selected_students);
                                    $selected_students=implode(',',$selected_students);
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE i.MATRICULE in ($selected_students) and  e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }else{
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }

                                //$sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut='BRS' ORDER BY noms,prenoms ASC ;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
                                for ($e = 0; $e < count($listeE); $e++) {
                                    extract($listeE[$e]); ?>
                                <tr>
                                    <td style="vertical-align: middle"><?php echo $matricule ?></td>
                                    <td style="width: 250px;height: 25px; vertical-align: middle"> <?php echo "<b style='text-transform: uppercase'>" . htmlentities($noms) . "</b>  " . htmlentities($prenoms) ?></td>
                                    <?php $ooo = $tValide = 0;
                                    $nV = 0;
                                    $moyGene = 0;

                                    for ($i = 0; $i < count($UE); $i++) {
                                       //  ${$UE}[$i]['ID_UE']=0;
                                        $rattrapages = 0;
                                        $noteRatr = 0;
                                        $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                                        $$ue_num=0;
                                        $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $rc=array_map(function ($value) {
                                            return (array)$value;
                                        }, $rc);
                                        $ooo = count($rc);
                                        $aColsEval = [];
                                        for ($ii = 0; $ii < count($rc); $ii++) {
                                            $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                                            $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
                                            $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
                                            $nbrRatr = DB::select($sqlNRatr);
                                            $nbrRatr=array_map(function ($value) {
                                                return (array)$value;
                                            }, $nbrRatr);
                                            $nbrRatr = (int)$nbrRatr[0]['nbre'];
                                            $rattrapages += $nbrRatr;
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
                                            $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"], 'slecue' => $rc[$ii]["Seuil_Ecu"]);
                                        }

                                        // calc moyenne
                                        $moyenne = 0.0;
                                        $moyenneReprise = 0.0;
                                        $cpseDev = $cpsePar = 0;
                                        foreach ($aColsEval as $key => $col) { //var_dump($col);
                                            //DEVOIRS
                                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $cpseDev = count($r);
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
                                            $cpsePar = count($r);
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            //  $listeN = "SELECT o.Note MD, o.moment moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = 'RD' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='385' ORDER BY `moment` DESC LIMIT 1;";
                                            // $r = DB::select($listeN);
                                            //  echo  $listeN.'---@@---'.floatval($r[0]['MD']);  exit();
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            $McalEcue = $mecue;
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                    ?>
                                            <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>">
                                                <?php
                                                if(($cpseDev == 0 && $cpsePar == 0) && ($noteRatr == 0 || $mRepr == 0) ){
                                                    echo 'Abs';
                                                }else{
                                                    echo ($moyenneReprise > 0.0 && $moyenneReprise!==0.0 && $moyenneReprise!==0) ? '<b>' . number_format($mRepr, 2, ',', ' ') . '<sup>*</sup>' . '</b>' : number_format($mecue, 2, ',', ' ');
                                                }

                                                ?> </td>
                                        <?php
                                            //echo $mecue<$col['slecue'] ?'#FFFFFF':'#FFFFFF'
                                        }
                                        if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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

                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php echo number_format($moyenne, 2, ',', ' ');
                                                                                                                        $moyGene += $moyenne * $UE[$i]['Credit']; ?></td>
                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php
                                                                                                                        //if($moyenne>$UE[$i]['Seuil_Ue']){echo "V";}else{if($moyenne==$UE[$i]['Seuil_Ue']){echo "V";}else{echo "NV";}}
                                                                                                                        //echo $UE[$i]['Seuil_Ue'];
                                                                                                                        echo '<b>' . $evz = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" . '</b>' : "NV" . '</b>';

                                                                                                                    //    round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ?   ${$UE}[$i]['ID_UE']= ${$UE}[$i]['ID_UE']+1  :   ${$UE}[$i]['ID_UE']=  ${$UE}[$i]['ID_UE']+0; ;

                                                                                                                        $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                                                                                                        $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                                                                                                                        $existee = 0;
                                                                                                                        $Etat_UE = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                                                                                                                        $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$ue_num';";
                                                                                                                        $dater = DB::select($rrrrrr);
                                                                                                                        $dater=array_map(function ($value) {
                                                                                                                            return (array)$value;
                                                                                                                        }, $dater);
                                                                                                                        $existee = count($dater);
                                                                                                                        //$rrr=
                                                                                                                        //mis à jour recap_Ue
                                                                                                                        /*$requeti = DB::insert('REPLACE INTO recap_annuel(`MATRICULE`,
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
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?,?,?,?)' , [$matricule,$anneeEtude,$anneeAcademique,$tValide,$percentUE,$moyG,$nV,$deci,$ttcr,Auth::user()->login,now()] );*/
                                                                                                                                    $login=Auth::user()->login;
                                                                                                                        $requ=DB::insert('REPLACE INTO `recap_ue` (`MATRICULE`,
                                                                                                                                                                   `Code_Annee_Etude`,
                                                                                                                                                                   `Id_Attr_ue_Sem`,
                                                                                                                                                                   `Moyenne_UE`,
                                                                                                                                                                   `Etat_UE`,
                                                                                                                                                                   `Observation`,
                                                                                                                                                                   `Auteur`,
                                                                                                                                                                   `moment`)
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?)' ,[$matricule, $anneeEtude, $ue_num, $moyenne ,$Etat_UE, 'Delib_Sem', $login, now()]);

                                                                                                                        ?></td>
                                    <?php
                                //echo $ue_num.'------@@@@------'.$$ue_num.'----------------';
                              // $UEVAL[] = array( 'NON_UE' => $ue_num, 'NBRE_UE' => $$ue_num+$$ue_num);

                                } ?>
                                
                                    <td style="text-align: center;vertical-align: middle"><?php echo $tValide; ?></td>
                                    <td style="text-align: center;vertical-align: middle"><?php $percentUE = 100 * $nV / count($UE);
                                                                                            echo number_format($percentUE, 2, ',', ' '); ?>%</td>
                                    <td style="text-align: center;vertical-align: middle"><?php $mgs = $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, '.', ' ');
                                                                    echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' '); ?></td>
                                    <td style="vertical-align: middle;text-align:center;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                                                                                                $now=now();
                                                                                                                $requettei = DB::insert("REPLACE INTO recap_sem(`MATRICULE`,`id_S_Gr_Spe`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,`Observation`,`Auteur`,`Moment`) VALUES (?,?,?,?,?,?,?,?,?)", [$matricule,$cl,$anne,$tValide,$percentUE,$mgs,$nV,$login,$now]);
                                                                                                                    ?></td>

                                </tr> <?php


                                }


                                ?>

                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; "> Nombre etudiants<br />ayant validé l'UE </th>
                                    <?php
                                    $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) {

                                        $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                        $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $recue=array_map(function ($value) {
                                            return (array)$value;
                                        }, $recue);
                                        $oo = count($recue);
                                    ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                    } ?>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                </tr>
                        </tbody>

                    </table>

                    <div style=" text-align:left;margin-left:250px;font-size: 25px;">
                        <div style="width: auto;">
                            <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                            <br> Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                            <br> Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                            <br> Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                            <?php $t = $sv + $svr + $snv + $svr_70;
                            echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                            echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / $t, 2, '.', ' ') . '% </b> <br/> ';
                            echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / $t, 2, '.', ' ') . '%  </b> <br/> ';
                            echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / $t, 2, '.', ' ') . '% </b> <br/>';
                            echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / $t, 2, '.', ' ') . '% </b> <br/>';

                            ?>

                        </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                    </div>
                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();

            try {

                $tabMarges = array(0, 0, 0, 0);

                $html2pdf = new HTML2PDF('L', 'A1', 'fr', true, 'UTF-8', $tabMarges);
                $html2pdf->pdf->SetDisplayMode('default');
                $html2pdf->writeHTML($strHtml);
                $html2pdf->output('example04.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }
        if (request()->btn=="Reprises") {
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            extract($this->extraireEntrees());
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.id_S_Gr_Spe='$semestreEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique';");
            if(empty($UE))
                abort(404);
            $UE=array_map(function ($value) {
                return (array)$value;
            }, $UE);
            $rTaux = DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;");
            $rTaux=array_map(function ($value) {
                return (array)$value;
            }, $rTaux);
            $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
            $tete=array_map(function ($value) {
                return (array)$value;
            }, $tete);
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $anne = $UE[0]['an'];
            $cl =  $UE[0]['clas'];
            // var_dump($UE[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
            <page backtop="10mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                        <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right"> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <table style="width:100%; margin:auto;" cellpadding="0" cellspacing="0" border="">

                    <tr>
                        <td style="width: 15%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:60%; text-align: center; font-size:25px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                            ----*----*----*---*---<br>
                            PROCES VERBAL DE DELIBERATION SEMESTRIELLE (REPRISES - SESSION NORMALE)<br>
                            ----*----*----*---*---<br>
                        </td>
                        <td style="width: 15%;vertical-align: middle; text-align: right"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                    </tr>

                </table>
                <div style="text-align: center;font-size: 25px;">
                    Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Département: <b><?php echo $tete[0]['libelleMention'] ?></b> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> <br> Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?> - Semestre <?=SemestreEtude::find($semestreEtude)->numeroSemestre?></b>
                </div>
                <div >
                    <br />
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px; ">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo '<b>' . $UE[$i]['ID_UE'] . "==>" . $UE[$i]['libelleUe'] . '</b>(' ?>
                    <?php
                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            $recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue);
                                            for ($iecuee = 0; $iecuee < count($recue); $iecuee++) {
                                                echo $recue[$iecuee]['ecue'] . ":" . $recue[$iecuee]['libelleEcu'] . ' ';
                                            }
                                            echo ')';
                                        }    ?>
                    ---@@@--- V = Validé --- NV = Non Validé -- * Rattrapage -- ** Reprise
                    </p>
					<p style="text-align:center; font-size: 20px ">
					Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
					</p>
                    <table id="liste" class="display" style="width:220%; margin:auto; font-size:20px;" border="1">
                        <thead>
                            <tr style="background-color:#7BA7CE">
                                <th colspan="2" rowspan="2" style="text-align: center; font-size: 25px; vertical-align: middle;"> Semestre:<br> <?php echo $UE[0]['Nom_Classe']; ?></th>
                                <?php
                                $tCredit = 0;
                                for ($i = 0; $i < count($UE); $i++) {
                                   // $unie=$UE[$i]['ID_UE'];
                                  //  $$unie=0;
                                    $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                    $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $recue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $recue);
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
                                    $rcue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$cue_af AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $rcue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $rcue);
                                    for ($iae = 0; $iae < count($rcue); $iae++) {
                                ?>
                                        <th style="text-align: center"><?php echo $rcue[$iae]['ecue'] . '<br>' . $rcue[$iae]['Ponderation'] * 100 . '%'; ?></th> <?php } ?>
                                    <th style="text-align: center;vertical-align: middle;background-color:#7BA7CE">Moy</th>
                                    <th style="text-align: center;vertical-align: middle;background-color:#7BA7CE">Obs</th>
                                <?php

                                } ?>

                            </tr>
                        </thead>
                        <tbody> <?php
                                if(isset($selected_students)){
                                    //dd($selected_students);
                                    $selected_students=implode(',',$selected_students);
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE i.MATRICULE in ($selected_students) and  e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }else{
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }

                                //$sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut='BRS' ORDER BY noms,prenoms ASC ;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
                                for ($e = 0; $e < count($listeE); $e++) {
                                    extract($listeE[$e]); 
                                    $listeR = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and e.codTypEval='REPR' and numeroInscription = '$matricule' ORDER BY `moment` DESC LIMIT 1;";
                                    $reprises = DB::select($listeR);
                                    $reprises=array_map(function ($value) {
                                        return (array)$value;
                                    }, $reprises);
                                    if(!empty($reprises)) {

                                    ?>
                                    
                                <tr>
                                    <td style="vertical-align: middle"><?php echo $matricule ?></td>
                                    <td style="width: 250px;height: 25px; vertical-align: middle"> <?php echo "<b style='text-transform: uppercase'>" . htmlentities($noms) . "</b>  " . htmlentities($prenoms) ?></td>
                                    <?php $ooo = $tValide = 0;
                                    $nV = 0;
                                    $moyGene = 0;

                                    for ($i = 0; $i < count($UE); $i++) {
                                       //  ${$UE}[$i]['ID_UE']=0;
                                        $rattrapages = 0;
                                        $noteRatr = 0;
                                        $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                                        $$ue_num=0;
                                        $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $rc=array_map(function ($value) {
                                            return (array)$value;
                                        }, $rc);
                                        $ooo = count($rc);
                                        $aColsEval = [];
                                        for ($ii = 0; $ii < count($rc); $ii++) {
                                            $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                                            $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
                                            $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
                                            $nbrRatr = DB::select($sqlNRatr);
                                            $nbrRatr=array_map(function ($value) {
                                                return (array)$value;
                                            }, $nbrRatr);
                                            $nbrRatr = (int)$nbrRatr[0]['nbre'];
                                            $rattrapages += $nbrRatr;
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
                                            $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"], 'slecue' => $rc[$ii]["Seuil_Ecu"]);
                                        }

                                        // calc moyenne
                                        $moyenne = 0.0;
                                        $moyenneReprise = 0.0;
                                        $cpseDev = $cpsePar = 0;
                                        foreach ($aColsEval as $key => $col) { //var_dump($col);
                                            //DEVOIRS
                                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $cpseDev = count($r);
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
                                            $cpsePar = count($r);
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            //  $listeN = "SELECT o.Note MD, o.moment moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = 'RD' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='385' ORDER BY `moment` DESC LIMIT 1;";
                                            // $r = DB::select($listeN);
                                            //  echo  $listeN.'---@@---'.floatval($r[0]['MD']);  exit();
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            $McalEcue = $mecue;
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                    ?>
                                            <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>">
                                                <?php
                                                if(($cpseDev == 0 && $cpsePar == 0) && ($noteRatr == 0 || $mRepr == 0) ){
                                                    echo 'Abs';
                                                }else{
                                                    echo ($moyenneReprise > 0.0 && $moyenneReprise!==0.0 && $moyenneReprise!==0) ? '<b>' . number_format($mRepr, 2, ',', ' ') . '<sup>*</sup>' . '</b>' : number_format($mecue, 2, ',', ' ');
                                                }

                                                ?> </td>
                                        <?php
                                            //echo $mecue<$col['slecue'] ?'#FFFFFF':'#FFFFFF'
                                        }
                                        if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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

                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php echo number_format($moyenne, 2, ',', ' ');
                                                                                                                        $moyGene += $moyenne * $UE[$i]['Credit']; ?></td>
                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php
                                                                                                                        //if($moyenne>$UE[$i]['Seuil_Ue']){echo "V";}else{if($moyenne==$UE[$i]['Seuil_Ue']){echo "V";}else{echo "NV";}}
                                                                                                                        //echo $UE[$i]['Seuil_Ue'];
                                                                                                                        echo '<b>' . $evz = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" . '</b>' : "NV" . '</b>';

                                                                                                                    //    round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ?   ${$UE}[$i]['ID_UE']= ${$UE}[$i]['ID_UE']+1  :   ${$UE}[$i]['ID_UE']=  ${$UE}[$i]['ID_UE']+0; ;

                                                                                                                        $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                                                                                                        $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                                                                                                                        $existee = 0;
                                                                                                                        $Etat_UE = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                                                                                                                        $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$ue_num';";
                                                                                                                        $dater = DB::select($rrrrrr);
                                                                                                                        $dater=array_map(function ($value) {
                                                                                                                            return (array)$value;
                                                                                                                        }, $dater);
                                                                                                                        $existee = count($dater);
                                                                                                                        //$rrr=
                                                                                                                        //mis à jour recap_Ue
                                                                                                                        /*$requeti = DB::insert('REPLACE INTO recap_annuel(`MATRICULE`,
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
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?,?,?,?)' , [$matricule,$anneeEtude,$anneeAcademique,$tValide,$percentUE,$moyG,$nV,$deci,$ttcr,Auth::user()->login,now()] );*/
                                                                                                                                    $login=Auth::user()->login;
                                                                                                                        $requ=DB::insert('REPLACE INTO `recap_ue` (`MATRICULE`,
                                                                                                                                                                   `Code_Annee_Etude`,
                                                                                                                                                                   `Id_Attr_ue_Sem`,
                                                                                                                                                                   `Moyenne_UE`,
                                                                                                                                                                   `Etat_UE`,
                                                                                                                                                                   `Observation`,
                                                                                                                                                                   `Auteur`,
                                                                                                                                                                   `moment`)
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?)' ,[$matricule, $anneeEtude, $ue_num, $moyenne ,$Etat_UE, 'Delib_Sem', $login, now()]);

                                                                                                                        ?></td>
                                    <?php
                                //echo $ue_num.'------@@@@------'.$$ue_num.'----------------';
                              // $UEVAL[] = array( 'NON_UE' => $ue_num, 'NBRE_UE' => $$ue_num+$$ue_num);

                                } ?>
                                
                                    <td style="text-align: center;vertical-align: middle"><?php echo $tValide; ?></td>
                                    <td style="text-align: center;vertical-align: middle"><?php $percentUE = 100 * $nV / count($UE);
                                                                                            echo number_format($percentUE, 2, ',', ' '); ?>%</td>
                                    <td style="text-align: center;vertical-align: middle"><?php $mgs = $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, '.', ' ');
                                                                    echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' '); ?></td>
                                    <td style="vertical-align: middle;text-align:center;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                                                                                                $now=now();
                                                                                                                $requettei = DB::insert("REPLACE INTO recap_sem(`MATRICULE`,`id_S_Gr_Spe`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,`Observation`,`Auteur`,`Moment`) VALUES (?,?,?,?,?,?,?,?,?)", [$matricule,$cl,$anne,$tValide,$percentUE,$mgs,$nV,$login,$now]);
                                                                                                                    ?></td>

                                </tr> <?php


                                }}


                                ?>

                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; "> Nombre etudiants<br />ayant validé l'UE </th>
                                    <?php
                                    $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) {

                                        $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                        $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $recue=array_map(function ($value) {
                                            return (array)$value;
                                        }, $recue);
                                        $oo = count($recue);
                                    ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                    } ?>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                </tr>
                        </tbody>

                    </table>

                    <?php if(!empty($reprises)){?> 
                    <div style=" text-align:left;margin-left:250px;font-size: 25px;">
                        <div style="width: auto;">
                            <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                            <br> Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                            <br> Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                            <br> Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                            <?php $t = $sv + $svr + $snv + $svr_70;
                            echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                            echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / $t, 2, '.', ' ') . '% </b> <br/> ';
                            echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / $t, 2, '.', ' ') . '%  </b> <br/> ';
                            echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / $t, 2, '.', ' ') . '% </b> <br/>';
                            echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / $t, 2, '.', ' ') . '% </b> <br/>';

                            ?>

                        </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                    </div>
                    <?php } ?>
                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();

            try {

                $tabMarges = array(0, 0, 0, 0);

                $html2pdf = new HTML2PDF('L', 'A1', 'fr', true, 'UTF-8', $tabMarges);
                $html2pdf->pdf->SetDisplayMode('default');
                $html2pdf->writeHTML($strHtml);
                $html2pdf->output('example04.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }
        if (request()->btn=="Rattrapages") {
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            extract($this->extraireEntrees());
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.id_S_Gr_Spe='$semestreEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique';");
            if(empty($UE))
                abort(404);
            $UE=array_map(function ($value) {
                return (array)$value;
            }, $UE);
            $rTaux = DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;");
            $rTaux=array_map(function ($value) {
                return (array)$value;
            }, $rTaux);
            $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
            $tete=array_map(function ($value) {
                return (array)$value;
            }, $tete);
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $anne = $UE[0]['an'];
            $cl =  $UE[0]['clas'];
            // var_dump($UE[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
            <page backtop="20mm" backbottom="12mm" backleft="2mm" backright="2mm">
                
                
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right"> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <table style="width:100%; margin:auto;" cellpadding="0" cellspacing="0" border="">

                    <tr>
                        <td style="width: 15%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:60%; text-align: center; font-size:20px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                            ----*----*----*---*---<br>
                            PROCES VERBAL DE DELIBERATION SEMESTRIELLE (APRÈS RATTRAPAGE)<br>
                            ----*----*----*---*---<br>
                        </td>
                        <td style="width: 15%;vertical-align: middle; text-align: right"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                    </tr>

                </table>
                <div style="text-align: center;font-size:20px">
                    Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Département: <b><?php echo $tete[0]['libelleMention'] ?></b> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> <br> Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?> - Semestre <?=SemestreEtude::find($semestreEtude)->numeroSemestre?></b>
                </div>
                <div >
                    <br />
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px; ">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo '<b>' . $UE[$i]['ID_UE'] . "==>" . $UE[$i]['libelleUe'] . '</b>(' ?>
                    <?php
                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            $recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue);
                                            for ($iecuee = 0; $iecuee < count($recue); $iecuee++) {
                                                echo $recue[$iecuee]['ecue'] . ":" . $recue[$iecuee]['libelleEcu'] . ' ';
                                            }
                                            echo ')';
                                        }    ?>
                    ---@@@--- V = Validé --- NV = Non Validé -- * Rattrapage -- ** Reprise
                    </p>
                    <p style="text-align:center; font-size: 20px ">
					    Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
                    </p>
                    <table id="liste" class="display" style="width:250%; margin:auto; font-size:20px;" border="1">
                        <thead>
                            <tr style="background-color:#7BA7CE">
                                <th colspan="2" rowspan="2" style="text-align: center;vertical-align:middle; font-size: 30px"> Semestre:<br> <?php echo $UE[0]['Nom_Classe']; ?></th>
                                <?php
                                $tCredit = 0;
                                for ($i = 0; $i < count($UE); $i++) {
                                   // $unie=$UE[$i]['ID_UE'];
                                  //  $$unie=0;
                                    $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                    $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $recue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $recue);
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
                                    $rcue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$cue_af AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $rcue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $rcue);
                                    for ($iae = 0; $iae < count($rcue); $iae++) {
                                ?>
                                        <th style="text-align: center"><?php echo $rcue[$iae]['ecue'] . '<br>' . $rcue[$iae]['Ponderation'] * 100 . '%'; ?></th> <?php } ?>
                                    <th style="text-align: center;padding: 2px;vertical-align: middle;background-color:#7BA7CE">Moy</th>
                                    <th style="text-align: center;padding: 2px;vertical-align: middle;background-color:#7BA7CE">Obs</th>
                                <?php

                                } ?>

                            </tr>
                        </thead>
                        <tbody> <?php
                                if(isset($selected_students)){
                                    //dd($selected_students);
                                    $selected_students=implode(',',$selected_students);
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE i.MATRICULE in ($selected_students) and  e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }else{
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }

                                //$sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut='BRS' ORDER BY noms,prenoms ASC ;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
                                for ($e = 0; $e < count($listeE); $e++) {
                                    extract($listeE[$e]); ?>
                                <tr>
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
                                        $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $rc=array_map(function ($value) {
                                            return (array)$value;
                                        }, $rc);
                                        $ooo = count($rc);
                                        $aColsEval = [];
                                        for ($ii = 0; $ii < count($rc); $ii++) {
                                            $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                                            $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
                                            $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
                                            $nbrRatr = DB::select($sqlNRatr);
                                            $nbrRatr=array_map(function ($value) {
                                                return (array)$value;
                                            }, $nbrRatr);
                                            $nbrRatr = (int)$nbrRatr[0]['nbre'];
                                            $rattrapages += $nbrRatr;
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
                                            $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"], 'slecue' => $rc[$ii]["Seuil_Ecu"]);
                                        }

                                        // calc moyenne
                                        $moyenne = 0.0;
                                        $moyenneReprise = 0.0;
                                        $cpseDev = $cpsePar = 0;
                                        foreach ($aColsEval as $key => $col) { //var_dump($col);
                                            //DEVOIRS
                                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $cpseDev = count($r);
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
                                            $cpsePar = count($r);
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            //  $listeN = "SELECT o.Note MD, o.moment moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = 'RD' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='385' ORDER BY `moment` DESC LIMIT 1;";
                                            // $r = DB::select($listeN);
                                            //  echo  $listeN.'---@@---'.floatval($r[0]['MD']);  exit();
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            $McalEcue = $mecue;
                                            $mecue = max($mR, $mecue);
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                    ?>
                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>">
                                                <?php
                                                if(($cpseDev == 0 && $cpsePar == 0) && ($noteRatr == 0 || $mRepr == 0) ){
                                                    echo 'Abs';
                                                }else{
                                                    echo ($moyenneReprise > 0.0 && $moyenneReprise!==0.0) ? '<b>' . number_format($mRepr, 2, ',', ' ') . '<sup>*</sup>' . '</b>' : number_format($mecue, 2, ',', ' ');
                                                }

                                                ?> </td>
                                        <?php
                                            //echo $mecue<$col['slecue'] ?'#FFFFFF':'#FFFFFF'
                                        }
                                        if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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

                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php echo number_format($moyenne, 2, ',', ' ');
                                                                                                                        $moyGene += $moyenne * $UE[$i]['Credit']; ?></td>
                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php
                                                                                                                        //if($moyenne>$UE[$i]['Seuil_Ue']){echo "V";}else{if($moyenne==$UE[$i]['Seuil_Ue']){echo "V";}else{echo "NV";}}
                                                                                                                        //echo $UE[$i]['Seuil_Ue'];
                                                                                                                        echo '<b>' . $evz = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" . '</b>' : "NV" . '</b>';

                                                                                                                    //    round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ?   ${$UE}[$i]['ID_UE']= ${$UE}[$i]['ID_UE']+1  :   ${$UE}[$i]['ID_UE']=  ${$UE}[$i]['ID_UE']+0; ;

                                                                                                                        $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                                                                                                        $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                                                                                                                        $existee = 0;
                                                                                                                        $Etat_UE = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                                                                                                                        $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$ue_num';";
                                                                                                                        $dater = DB::select($rrrrrr);
                                                                                                                        $dater=array_map(function ($value) {
                                                                                                                            return (array)$value;
                                                                                                                        }, $dater);
                                                                                                                        $existee = count($dater);
                                                                                                                        //$rrr=
                                                                                                                        //mis à jour recap_Ue
                                                                                                                        /*$requeti = DB::insert('REPLACE INTO recap_annuel(`MATRICULE`,
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
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?,?,?,?)' , [$matricule,$anneeEtude,$anneeAcademique,$tValide,$percentUE,$moyG,$nV,$deci,$ttcr,Auth::user()->login,now()] );*/
                                                                                                                                    $login=Auth::user()->login;
                                                                                                                        $requ=DB::insert('REPLACE INTO `recap_ue` (`MATRICULE`,
                                                                                                                                                                   `Code_Annee_Etude`,
                                                                                                                                                                   `Id_Attr_ue_Sem`,
                                                                                                                                                                   `Moyenne_UE`,
                                                                                                                                                                   `Etat_UE`,
                                                                                                                                                                   `Observation`,
                                                                                                                                                                   `Auteur`,
                                                                                                                                                                   `moment`)
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?)' ,[$matricule, $anneeEtude, $ue_num, $moyenne ,$Etat_UE, 'Delib_Sem', $login, now()]);

                                                                                                                        ?></td>
                                    <?php
                                //echo $ue_num.'------@@@@------'.$$ue_num.'----------------';
                              // $UEVAL[] = array( 'NON_UE' => $ue_num, 'NBRE_UE' => $$ue_num+$$ue_num);

                                } ?>
                                    <td style="text-align: center;vertical-align: middle"><?php echo $tValide; ?></td>
                                    <td style="text-align: center;vertical-align: middle"><?php $percentUE = 100 * $nV / count($UE);
                                                                                            echo number_format($percentUE, 2, ',', ' '); ?>%</td>
                                    <td style="text-align: center;vertical-align: middle;"><?php $mgs = $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, '.', ' ');
                                                                    echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' '); ?></td>
                                    <td style="vertical-align: middle;text-align:center;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                                                                                                $now=now();
                                                                                                                $requettei = DB::insert("REPLACE INTO recap_sem(`MATRICULE`,`id_S_Gr_Spe`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,`Observation`,`Auteur`,`Moment`) VALUES (?,?,?,?,?,?,?,?,?)", [$matricule,$cl,$anne,$tValide,$percentUE,$mgs,$nV,$login,$now]);
                                                                                                                    ?></td>

                                </tr> <?php


                                }


                                ?>

                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; "> Nombre etudiants<br />ayant validé l'UE </th>
                                    <?php
                                    $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) {

                                        $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                        $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $recue=array_map(function ($value) {
                                            return (array)$value;
                                        }, $recue);
                                        $oo = count($recue);
                                    ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                    } ?>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                </tr>
                        </tbody>

                    </table>

                    <div style=" text-align:left;margin-left:250px">
                        <div style="width: 1000px;">
                            <br> Nombre d'étudiants ayant validés le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                            Nombre d'étudiants ayant validés le semestre <b>avec des reprises</b>:<b> <?php echo $svr; ?></b>
                            Nombre d'étudiants <b>n'ayant pas validés</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                            Nombre d'étudiants <b>n'ayant pas validés</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                            <?php $t = $sv + $svr + $snv + $svr_70;
                            echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                            echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / $t, 2, '.', ' ') . '% </b> <br/> ';
                            echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / $t, 2, '.', ' ') . '%  </b> <br/> ';
                            echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / $t, 2, '.', ' ') . '% </b> <br/>';
                            echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / $t, 2, '.', ' ') . '% </b> <br/>';

                            ?>

                        </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                    </div>
                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();

            try {

                $tabMarges = array(0, 0, 0, 0);

                $html2pdf = new HTML2PDF('L', 'A1', 'fr', true, 'UTF-8', $tabMarges);
                $html2pdf->pdf->SetDisplayMode('default');
                $html2pdf->writeHTML($strHtml);
                $html2pdf->output('example04.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }
        if (request()->btn=="Reprises après rattrapage") {
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            extract($this->extraireEntrees());
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.id_S_Gr_Spe='$semestreEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique';");
            if(empty($UE))
                abort(404);
            $UE=array_map(function ($value) {
                return (array)$value;
            }, $UE);
            $rTaux = DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;");
            $rTaux=array_map(function ($value) {
                return (array)$value;
            }, $rTaux);
            $tete = DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'");
            $tete=array_map(function ($value) {
                return (array)$value;
            }, $tete);
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $anne = $UE[0]['an'];
            $cl =  $UE[0]['clas'];
            // var_dump($UE[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
            <page backtop="20mm" backbottom="12mm" backleft="2mm" backright="2mm">
                
                
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right"> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <table style="width:100%; margin:auto;" cellpadding="0" cellspacing="0" border="">

                    <tr>
                        <td style="width: 15%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:60%; text-align: center; font-size:20px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                            ----*----*----*---*---<br>
                            PROCES VERBAL DE DELIBERATION SEMESTRIELLE (APRÈS RATTRAPAGE)<br>
                            ----*----*----*---*---<br>
                        </td>
                        <td style="width: 15%;vertical-align: middle; text-align: right"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                    </tr>

                </table>
                <div style="text-align: center;font-size:20px">
                    Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Département: <b><?php echo $tete[0]['libelleMention'] ?></b> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> <br> Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?> - Semestre <?=SemestreEtude::find($semestreEtude)->numeroSemestre?></b>
                </div>
                <div >
                    <br />
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px; ">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo '<b>' . $UE[$i]['ID_UE'] . "==>" . $UE[$i]['libelleUe'] . '</b>(' ?>
                    <?php
                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            $recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue);
                                            for ($iecuee = 0; $iecuee < count($recue); $iecuee++) {
                                                echo $recue[$iecuee]['ecue'] . ":" . $recue[$iecuee]['libelleEcu'] . ' ';
                                            }
                                            echo ')';
                                        }    ?>
                    ---@@@--- V = Validé --- NV = Non Validé -- * Rattrapage -- ** Reprise
                    </p>
                    <p style="text-align:center; font-size: 20px ">
					    Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
                    </p>
                    <table id="liste" class="display" style="width:220%; margin:auto; font-size:20px;" border="1">
                        <thead>
                            <tr style="background-color:#7BA7CE">
                                <th colspan="2" rowspan="2" style="text-align: center;vertical-align:middle; font-size: 25px"> Semestre:<br> <?php echo $UE[0]['Nom_Classe']; ?></th>
                                <?php
                                $tCredit = 0;
                                for ($i = 0; $i < count($UE); $i++) {
                                   // $unie=$UE[$i]['ID_UE'];
                                  //  $$unie=0;
                                    $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                    $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $recue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $recue);
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
                                    $rcue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$cue_af AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                    $rcue=array_map(function ($value) {
                                        return (array)$value;
                                    }, $rcue);
                                    for ($iae = 0; $iae < count($rcue); $iae++) {
                                ?>
                                        <th style="text-align: center"><?php echo $rcue[$iae]['ecue'] . '<br>' . $rcue[$iae]['Ponderation'] * 100 . '%'; ?></th> <?php } ?>
                                    <th style="text-align: center;padding: 2px;vertical-align: middle;background-color:#7BA7CE">Moy</th>
                                    <th style="text-align: center;padding: 2px;vertical-align: middle;background-color:#7BA7CE">Obs</th>
                                <?php

                                } ?>

                            </tr>
                        </thead>
                        <tbody> <?php
                                if(isset($selected_students)){
                                    //dd($selected_students);
                                    $selected_students=implode(',',$selected_students);
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE i.MATRICULE in ($selected_students) and  e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }else{
                                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude'  ORDER BY noms,prenoms ASC ;";
                                }

                                //$sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut='BRS' ORDER BY noms,prenoms ASC ;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
                                for ($e = 0; $e < count($listeE); $e++) {
                                    extract($listeE[$e]); 
                                    $listeR = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and e.codTypEval='REPR' and numeroInscription = '$matricule' ORDER BY `moment` DESC LIMIT 1;";
                                    $reprises = DB::select($listeR);
                                    $reprises=array_map(function ($value) {
                                        return (array)$value;
                                    }, $reprises);
                                    if(!empty($reprises)) {
                                    ?>
                                <tr>
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
                                        $rc = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart,Seuil_Ecu FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $rc=array_map(function ($value) {
                                            return (array)$value;
                                        }, $rc);
                                        $ooo = count($rc);
                                        $aColsEval = [];
                                        for ($ii = 0; $ii < count($rc); $ii++) {
                                            $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='DEV';";
                                            $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude and evaluation.codTypEval='PART';";
                                            $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
                                            $nbrRatr = DB::select($sqlNRatr);
                                            $nbrRatr=array_map(function ($value) {
                                                return (array)$value;
                                            }, $nbrRatr);
                                            $nbrRatr = (int)$nbrRatr[0]['nbre'];
                                            $rattrapages += $nbrRatr;
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
                                            $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"], 'slecue' => $rc[$ii]["Seuil_Ecu"]);
                                        }

                                        // calc moyenne
                                        $moyenne = 0.0;
                                        $moyenneReprise = 0.0;
                                        $cpseDev = $cpsePar = 0;
                                        foreach ($aColsEval as $key => $col) { //var_dump($col);
                                            //DEVOIRS
                                            $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num';";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $cpseDev = count($r);
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
                                            $cpsePar = count($r);
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            //  $listeN = "SELECT o.Note MD, o.moment moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = 'RD' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='385' ORDER BY `moment` DESC LIMIT 1;";
                                            // $r = DB::select($listeN);
                                            //  echo  $listeN.'---@@---'.floatval($r[0]['MD']);  exit();
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            $McalEcue = $mecue;
                                            $mecue = max($mR, $mecue);
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                    ?>
                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>">
                                                <?php
                                                if(($cpseDev == 0 && $cpsePar == 0) && ($noteRatr == 0 || $mRepr == 0) ){
                                                    echo 'Abs';
                                                }else{
                                                    echo ($moyenneReprise > 0.0 && $moyenneReprise!==0.0) ? '<b>' . number_format($mRepr, 2, ',', ' ') . '<sup>*</sup>' . '</b>' : number_format($mecue, 2, ',', ' ');
                                                }

                                                ?> </td>
                                        <?php
                                            //echo $mecue<$col['slecue'] ?'#FFFFFF':'#FFFFFF'
                                        }
                                        if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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

                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php echo number_format($moyenne, 2, ',', ' ');
                                                                                                                        $moyGene += $moyenne * $UE[$i]['Credit']; ?></td>
                                        <td style="text-align: center;vertical-align: middle;<?php if(round($moyenne, 3) < $UE[$i]['Seuil_Ue']) echo 'background-color:rgb(139, 131, 131);'?>"><?php
                                                                                                                        //if($moyenne>$UE[$i]['Seuil_Ue']){echo "V";}else{if($moyenne==$UE[$i]['Seuil_Ue']){echo "V";}else{echo "NV";}}
                                                                                                                        //echo $UE[$i]['Seuil_Ue'];
                                                                                                                        echo '<b>' . $evz = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" . '</b>' : "NV" . '</b>';

                                                                                                                    //    round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ?   ${$UE}[$i]['ID_UE']= ${$UE}[$i]['ID_UE']+1  :   ${$UE}[$i]['ID_UE']=  ${$UE}[$i]['ID_UE']+0; ;

                                                                                                                        $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                                                                                                        $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;
                                                                                                                        $existee = 0;
                                                                                                                        $Etat_UE = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "NV";
                                                                                                                        $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$ue_num';";
                                                                                                                        $dater = DB::select($rrrrrr);
                                                                                                                        $dater=array_map(function ($value) {
                                                                                                                            return (array)$value;
                                                                                                                        }, $dater);
                                                                                                                        $existee = count($dater);
                                                                                                                        //$rrr=
                                                                                                                        //mis à jour recap_Ue
                                                                                                                        /*$requeti = DB::insert('REPLACE INTO recap_annuel(`MATRICULE`,
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
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?,?,?,?)' , [$matricule,$anneeEtude,$anneeAcademique,$tValide,$percentUE,$moyG,$nV,$deci,$ttcr,Auth::user()->login,now()] );*/
                                                                                                                                    $login=Auth::user()->login;
                                                                                                                        $requ=DB::insert('REPLACE INTO `recap_ue` (`MATRICULE`,
                                                                                                                                                                   `Code_Annee_Etude`,
                                                                                                                                                                   `Id_Attr_ue_Sem`,
                                                                                                                                                                   `Moyenne_UE`,
                                                                                                                                                                   `Etat_UE`,
                                                                                                                                                                   `Observation`,
                                                                                                                                                                   `Auteur`,
                                                                                                                                                                   `moment`)
                                                                                                                                    VALUES(?,?,?,?,?,?,?,?)' ,[$matricule, $anneeEtude, $ue_num, $moyenne ,$Etat_UE, 'Delib_Sem', $login, now()]);

                                                                                                                        ?></td>
                                    <?php
                                //echo $ue_num.'------@@@@------'.$$ue_num.'----------------';
                              // $UEVAL[] = array( 'NON_UE' => $ue_num, 'NBRE_UE' => $$ue_num+$$ue_num);

                                } ?>
                                    <td style="text-align: center;vertical-align: middle"><?php echo $tValide; ?></td>
                                    <td style="text-align: center;vertical-align: middle"><?php $percentUE = 100 * $nV / count($UE);
                                                                                            echo number_format($percentUE, 2, ',', ' '); ?>%</td>
                                    <td style="text-align: center;vertical-align: middle;"><?php $mgs = $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, '.', ' ');
                                                                    echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' '); ?></td>
                                    <td style="vertical-align: middle;text-align:center;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                                                                                                $now=now();
                                                                                                                $requettei = DB::insert("REPLACE INTO recap_sem(`MATRICULE`,`id_S_Gr_Spe`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,`Observation`,`Auteur`,`Moment`) VALUES (?,?,?,?,?,?,?,?,?)", [$matricule,$cl,$anne,$tValide,$percentUE,$mgs,$nV,$login,$now]);
                                                                                                                    ?></td>

                                </tr> <?php


                                }}


                                ?>

                                <?php if(!empty($reprises)){ ?> 
                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; "> Nombre etudiants<br />ayant validé l'UE </th>
                                    <?php
                                    $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) {

                                        $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                        $recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                        $recue=array_map(function ($value) {
                                            return (array)$value;
                                        }, $recue);
                                        $oo = count($recue);
                                    ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                    } ?>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                    <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center">---</th>
                                </tr>
                                <?php } ?>
                        </tbody>

                    </table>

                    <div style=" text-align:left;margin-left:250px">
                        <div style="width: 1000px;">
                            <br> Nombre d'étudiants ayant validés le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                            Nombre d'étudiants ayant validés le semestre <b>avec des reprises</b>:<b> <?php echo $svr; ?></b>
                            Nombre d'étudiants <b>n'ayant pas validés</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                            Nombre d'étudiants <b>n'ayant pas validés</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                            <?php $t = $sv + $svr + $snv + $svr_70;
                            echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                            echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / $t, 2, '.', ' ') . '% </b> <br/> ';
                            echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / $t, 2, '.', ' ') . '%  </b> <br/> ';
                            echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / $t, 2, '.', ' ') . '% </b> <br/>';
                            echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / $t, 2, '.', ' ') . '% </b> <br/>';

                            ?>

                        </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                    </div>
                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();

            try {

                $tabMarges = array(0, 0, 0, 0);

                $html2pdf = new HTML2PDF('L', 'A1', 'fr', true, 'UTF-8', $tabMarges);
                $html2pdf->pdf->SetDisplayMode('default');
                $html2pdf->writeHTML($strHtml);
                $html2pdf->output('example04.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }

    }

    public function impressionAnnuel(){
        if (request()->btn=="Session normale") {
            extract($this->extraireEntrees());
            if(empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;");
            if(empty($UE))
                abort(404);
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
            // return response()->json($UE);
            $classe = $UE[0]['Nom_Classe'];
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $moyNor = $rTaux[0]['Moyenne'];
            $moyauto = $rTaux[0]['MoyenneUE'];
            //  var_dump($rTaux[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
                <page backtop="10mm" backbottom="12mm" backleft="2mm" backright="2mm">
                    <page_footer>
                        <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                            <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                            <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                                <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                            </div>
                            <div style="display: inline; width: 25%; text-align: right"><b style="color:RED">INV = Inscription Non Validée </b> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                        </div>
                    </page_footer>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:40%; text-align: center; font-size:18px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                PROCES VERBAL DE DELIBERATION ANNUELLE (AVANT RATTRAPAGE)<br>
                                ----*----*----*---*---<br>
                            </td>
                            <td style="text-align: right;width:940px"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                    <div style="text-align: center;font-size: 20px;">
                        Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Mention: <b><?php echo $tete[0]['libelleMention'] ?></b> <br> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> ---@@@--- Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?></b>
                    </div>
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo $UE[$i]['ID_UE'] . "===>" . $UE[$i]['libelleUe'] ?> <?php } ?>
                            ---@@@--- V = Validé ---  V+R = Validé avec Reprise --- NV = Non Validé.
                    </p>
					<p style="text-align:center; font-size: 20px ">
					Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
					</P>
                    <div>
                        <table id="liste" class="display" style="width:100%;margin:auto;font-size:25px;page-break-inside: avoid;" border="1" cellspacing="2" cellpadding="2">
                            <thead class="thead">
                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; font-size: 30px; vertical-align: middle;"> Année Etude:<br><?php echo $tete[0]['Libelle_AnEtude']; ?></th>
                                    <?php for ($i = 0; $i < count($UE); $i++) { ?><th colspan="2" style="text-align:center;font-size: 21px;padding: 5px;background-color:
                                    <?php if ($classe == $UE[$i]['Nom_Classe']) {
                                            echo "#80C8F2";
                                        } else {
                                            echo "#667DFD";
                                        } ?>"><?php echo $UE[$i]['ID_UE'] ?></th> <?php } ?>
                                    <th style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br />Crédits<br /> Capitalisés</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br>d'UE<br> Validée(s)</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">%age<br> d'UE</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Moyenne</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Décision</th>

                                </tr>
                                <tr style="background-color:#7BA7CE">
                                    <?php $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) { ?>
                                        <th style="background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                                                        echo "#80C8F2";
                                                                    } else {
                                                                        echo "#667DFD";
                                                                    } ?>;font-size: 21px;padding: 5px;">Credit</th>
                                        <th style="text-align:center;padding: 5px;font-size: 21px;background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                        echo "#80C8F2";
                                        } else {
                                        echo "#667DFD";
                                        } ?>"><?php $tCredit += $UE[$i]['Credit'];
                                        echo $UE[$i]['Credit'] ?></th>
                                    <?php } ?>
                                    <th style="text-align:center;padding: 5px;font-size: 21px;"><?php echo $tCredit; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($typeEtudiant && $typeEtudiant!=='all') $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut = '$typeEtudiant' ORDER BY noms,prenoms ASC;";
                                else if($typeEtudiant=='all' || $typeEtudiant==null) $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
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
                                            //SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            if(AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->pedagogique==1 && $mecue<AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->Seuil_Ecu) $pedagogique=1;
                                            //$mecue = max($mR, $mecue);
                                            // $mecue = $mRepr!=0? max($mRepr, $mR): max($mR, $mecue);
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                            if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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
                                    //var_dump ($moyenneReprise);
                                ?>
                                    <tr>
                                        <td style="vertical-align: middle;font-size: 17px;padding: 2px;"><?php echo $matricule ?></td>
                                        <td style="height: 25px; vertical-align: middle;font-size: 17px;padding: 2px;width: 250px;"><?php echo ("<b style='text-transform: uppercase'> $noms </b> $prenoms") ?></td>
                                        <?php
                                        for ($i = 0; $i < count($moyenne); $i++) { ?>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;<?php if(($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] < $moyauto)) echo 'background-color:grey'?> "> <?php echo (($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] >= $moyauto) && $moyG >= $moyNor) ? '<del>' . number_format($moyenne[$i][1], 2, ',', '') . '</del> <br> <b>' . number_format($moyenne[$i][4], 2, ',', '') . '</b>' : number_format($moyenne[$i][1], 2, ',', ''); ?></td>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                            echo $moyenne[$i][2];
                                            } else {
                                            echo 'INV';
                                            } ?></td>
                                        <?php
                                            $existee = 0;
                                            $KdeUE1 = $moyenne[$i][5];
                                            $KdeUE2 = number_format($moyenne[$i][1], 2, '.', '');
                                            $KdeUE3 = $moyenne[$i][2];
                                            $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$KdeUE1';";
                                            $dater = DB::select($rrrrrr);
                                            $dater=array_map(function ($value) {
                                                return (array)$value;
                                            }, $dater);
                                            $existee = count($dater);
                                            //$rrr=
                                        } ?>

                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo $tValide;
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo '<b>' . $nV . '</b> sur <b>' . count($moyenne) . '</b>';
                                        $ttcr = number_format(count($moyenne), 0, ',', ' ');
                                        } else {
                                        $ttcr = 0;
                                        echo 'INV';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($percentUE, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?>%</td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($moyG, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                        } ?>"><?php if ($etat == "OUI") {
                                        if ($percentUE >= $taux && $percentUE < 100) {
                                        echo "<b>V + R </b>";
                                        $deci = "V + R";
                                        } else {
                                        if ($percentUE < $taux) {
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
                                        //$requeti = mysqli_query($connect->getVarConnexion(), "REPLACE INTO recap_annuel(`MATRICULE`,`Code_Annee_Etude`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,Nbre_UE_Val,DecisionJury,`Observation`,`Auteur`,`Moment`) VALUES('$matricule','$annee_etude',$anneeacad,$tValide,'$percentUE','$moyG',$nV,'$deci','$ttcr','" . $_SESSION['slogin'] . "',CURRENT_TIMESTAMP);");
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
                                    </tr> 
                                    <?php  }  ?>
                                    <tr style="background-color:#7BA7CE">
                                        <th colspan="2" rowspan="2" style="text-align: center; font-size: 25px;padding: 2px;"> Nombre etudiants<br />ayant validé l'UE </th>
                                        <?php
                                        $tCredit = 0;
                                        for ($i = 0; $i < count($UE); $i++) {

                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            //$recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            /*$recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue)*/;
                                            $oo = 0;
                                        ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center;font-size: 21px;padding: 2px;"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                        } ?>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                    </tr>
                            </tbody>
                        </table>
                        <div style=" text-align:left;margin-left:250px">
                            <div style="width: 2000px;font-size: 21px;">
                                <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                                Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                                <?php $t = $sv + $svr + $snv + $svr_70;
                                $tnva = $svr + $svr_70 + $snv;
                                echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                                echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/> ';
                                echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / (($t!==0)?$t:1), 2, '.', ' ') . '%  </b> <br/> ';
                                echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/>';
                                echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/><br/>';
                                echo '<b style="font-size: 20px"> RECAPITULATIF ANNUEL:  ' . $sv . ' etudiants soit un pourcentage de ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% ont validé l\'année contre ' . $tnva . ' etudiant(s) soit un pourcentage de : ' . number_format(100 * ($svr + $svr_70 + $snv) / (($t!==0)?$t:1), 2, '.', ' ') . '% de l\'effectif de la classe. </b> <br/>';
                                ?>
                            </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                        </div>
                    </div>
                </page>
                <?php
                $strHtml = ob_get_clean();
                try {
                    $tabMarges = array(10, 10, 10, 10);
                    $objPDF = new HTML2PDF('L', 'A1', 'fr');
                    $objPDF->pdf->SetDisplayMode('real');
                    $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                    $objPDF->writeHTML($strHtml);
                    $dateNow = new DateTime('now');
                    $dateNow = $dateNow->format('Y.m.d.H.i.s');
                    //'PV_'.$anneeEtude.'_'.$mysql->anne($anneeAcademique).'
                    $objPDF->pdf->SetTitle('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD);
                    $objPDF->Output('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD . '.pdf');
                } catch (Exception $e) {
                    abort(500, 'Erreur lors de la création du document.');
                }
        }
        if (request()->btn=="Rattrapages") {
            extract($this->extraireEntrees());
            if(empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;");
            if(empty($UE))
                abort(404);
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
            // return response()->json($UE);
            $classe = $UE[0]['Nom_Classe'];
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $moyNor = $rTaux[0]['Moyenne'];
            $moyauto = $rTaux[0]['MoyenneUE'];
            //  var_dump($rTaux[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
                <page backtop="10mm" backbottom="12mm" backleft="2mm" backright="2mm">
                    <page_footer>
                        <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                            <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                            <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                                <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                            </div>
                            <div style="display: inline; width: 25%; text-align: right"><b style="color:RED">INV = Inscription Non Validée </b> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                        </div>
                    </page_footer>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:40%; text-align: center; font-size:18px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                PROCES VERBAL DE DELIBERATION ANNUELLE (APRÈS RATTRAPAGE)<br>
                                ----*----*----*---*---<br>
                            </td>
                            <td style="text-align: right;width:30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                    <div style="text-align: center;font-size: 20px;">
                        Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Mention: <b><?php echo $tete[0]['libelleMention'] ?></b> <br> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> ---@@@--- Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?></b>
                    </div>
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo $UE[$i]['ID_UE'] . "===>" . $UE[$i]['libelleUe'] ?> <?php } ?>
                            ---@@@--- V = Validé ---  V+R = Validé avec Reprise --- NV = Non Validé.
                    </p>
					<p style="text-align:center; font-size: 20px ">
					Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
					</P>
                    <div>
                        <table id="liste" class="display" style="width:100%;margin:auto;page-break-inside: avoid;" border="1" cellspacing="2" cellpadding="2">
                            <thead class="thead">
                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; font-size: 30px; vertical-align: middle;"> Année Etude:<br><?php echo $tete[0]['Libelle_AnEtude']; ?></th>
                                    <?php for ($i = 0; $i < count($UE); $i++) { ?><th colspan="2" style="text-align:center;font-size: 21px;padding: 5px;background-color:
                                    <?php if ($classe == $UE[$i]['Nom_Classe']) {
                                            echo "#80C8F2";
                                        } else {
                                            echo "#667DFD";
                                        } ?>"><?php echo $UE[$i]['ID_UE'] ?></th> <?php } ?>
                                    <th style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br />Crédits<br /> Capitalisés</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br>d'UE<br> Validée(s)</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">%age<br> d'UE</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Moyenne</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Décision</th>

                                </tr>
                                <tr style="background-color:#7BA7CE">
                                    <?php $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) { ?>
                                        <th style="background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                                                        echo "#80C8F2";
                                                                    } else {
                                                                        echo "#667DFD";
                                                                    } ?>;font-size: 21px;padding: 5px;">Credit</th>
                                        <th style="text-align:center;padding: 5px;font-size: 21px;background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                        echo "#80C8F2";
                                        } else {
                                        echo "#667DFD";
                                        } ?>"><?php $tCredit += $UE[$i]['Credit'];
                                        echo $UE[$i]['Credit'] ?></th>
                                    <?php } ?>
                                    <th style="text-align:center;padding: 5px;font-size: 21px;"><?php echo $tCredit; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($typeEtudiant && $typeEtudiant!=='all') $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut = '$typeEtudiant' ORDER BY noms,prenoms ASC;";
                                else if($typeEtudiant=='all' || $typeEtudiant==null) $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
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
                                            //SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            if(AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->pedagogique==1 && $mecue<AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->Seuil_Ecu) $pedagogique=1;
                                            $mecue = max($mR, $mecue);
                                            // $mecue = $mRepr!=0? max($mRepr, $mR): max($mR, $mecue);
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                            if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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
                                        <td style="vertical-align: middle;font-size: 17px;padding: 2px;"><?php echo $matricule ?></td>
                                        <td style="height: 25px; vertical-align: middle;font-size: 17px;padding: 2px;width: 250px;"><?php echo ("<b style='text-transform: uppercase'> $noms </b> $prenoms") ?></td>
                                        <?php
                                        for ($i = 0; $i < count($moyenne); $i++) { ?>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;<?php if($moyenne[$i][1] < $moyenne[$i][4]) echo 'background-color:grey'?> "> <?php echo (($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] >= $moyauto) && $moyG >= $moyNor) ? '<del>' . number_format($moyenne[$i][1], 2, ',', '') . '</del> <br> <b>' . number_format($moyenne[$i][4], 2, ',', '') . '</b>' : number_format($moyenne[$i][1], 2, ',', ''); ?></td>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                            echo $moyenne[$i][2];
                                            } else {
                                            echo 'INV';
                                            } ?></td>
                                        <?php
                                            $existee = 0;
                                            $KdeUE1 = $moyenne[$i][5];
                                            $KdeUE2 = number_format($moyenne[$i][1], 2, '.', '');
                                            $KdeUE3 = $moyenne[$i][2];
                                            $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$KdeUE1';";
                                            $dater = DB::select($rrrrrr);
                                            $dater=array_map(function ($value) {
                                                return (array)$value;
                                            }, $dater);
                                            $existee = count($dater);
                                            //$rrr=
                                        } ?>

                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo $tValide;
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo '<b>' . $nV . '</b> sur <b>' . count($moyenne) . '</b>';
                                        $ttcr = number_format(count($moyenne), 0, ',', ' ');
                                        } else {
                                        $ttcr = 0;
                                        echo 'INV';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($percentUE, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?>%</td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($moyG, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                        } ?>"><?php if ($etat == "OUI") {
                                        if ($percentUE >= $taux && $percentUE < 100) {
                                        echo "<b>V + R </b>";
                                        $deci = "V + R";
                                        } else {
                                        if ($percentUE < $taux) {
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
                                        //$requeti = mysqli_query($connect->getVarConnexion(), "REPLACE INTO recap_annuel(`MATRICULE`,`Code_Annee_Etude`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,Nbre_UE_Val,DecisionJury,`Observation`,`Auteur`,`Moment`) VALUES('$matricule','$annee_etude',$anneeacad,$tValide,'$percentUE','$moyG',$nV,'$deci','$ttcr','" . $_SESSION['slogin'] . "',CURRENT_TIMESTAMP);");
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
                                    <tr style="background-color:#7BA7CE">
                                        <th colspan="2" rowspan="2" style="text-align: center; font-size: 25px;padding: 2px;"> Nombre etudiants<br />ayant validé l'UE </th>
                                        <?php
                                        $tCredit = 0;
                                        for ($i = 0; $i < count($UE); $i++) {

                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            //$recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            /*$recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue)*/;
                                            $oo = 0;
                                        ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center;font-size: 21px;padding: 2px;"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                        } ?>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                    </tr>
                            </tbody>
                        </table>
                        <div style=" text-align:left;margin-left:250px">
                            <div style="width: 2000px;font-size: 21px;">
                                <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                                Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                                <?php $t = $sv + $svr + $snv + $svr_70;
                                $tnva = $svr + $svr_70 + $snv;
                                echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                                echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/> ';
                                echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / (($t!==0)?$t:1), 2, '.', ' ') . '%  </b> <br/> ';
                                echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/>';
                                echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/><br/>';
                                echo '<b style="font-size: 20px"> RECAPITULATIF ANNUEL:  ' . $sv . ' etudiants soit un pourcentage de ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% ont validé l\'année contre ' . $tnva . ' etudiant(s) soit un pourcentage de : ' . number_format(100 * ($svr + $svr_70 + $snv) / (($t!==0)?$t:1), 2, '.', ' ') . '% de l\'effectif de la classe. </b> <br/>';
                                ?>
                            </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                        </div>
                    </div>
                </page>
                <?php
                $strHtml = ob_get_clean();
                try {
                    $tabMarges = array(10, 10, 10, 10);
                    $objPDF = new HTML2PDF('L', 'A1', 'fr');
                    $objPDF->pdf->SetDisplayMode('real');
                    $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                    $objPDF->writeHTML($strHtml);
                    $dateNow = new DateTime('now');
                    $dateNow = $dateNow->format('Y.m.d.H.i.s');
                    //'PV_'.$anneeEtude.'_'.$mysql->anne($anneeAcademique).'
                    $objPDF->pdf->SetTitle('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD.'_Rattrapages');
                    $objPDF->Output('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD . '.pdf');
                } catch (Exception $e) {
                    abort(500, 'Erreur lors de la création du document.');
                }
        }
        if (request()->btn=="Reprises après rattrapage") {
            extract($this->extraireEntrees());
            if(empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;");
            if(empty($UE))
                abort(404);
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
            // return response()->json($UE);
            $classe = $UE[0]['Nom_Classe'];
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $moyNor = $rTaux[0]['Moyenne'];
            $moyauto = $rTaux[0]['MoyenneUE'];
            //  var_dump($rTaux[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
                <page backtop="10mm" backbottom="12mm" backleft="2mm" backright="2mm">
                    <page_footer>
                        <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                            <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                            <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                                <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                            </div>
                            <div style="display: inline; width: 25%; text-align: right"><b style="color:RED">INV = Inscription Non Validée </b> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                        </div>
                    </page_footer>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:40%; text-align: center; font-size:18px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                PROCES VERBAL DE DELIBERATION ANNUELLE (REPRISES - APRÈS RATTRAPAGE)<br>
                                ----*----*----*---*---<br>
                            </td>
                            <td style="text-align: right;width:30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                    <div style="text-align: center;font-size: 20px;">
                        Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Mention: <b><?php echo $tete[0]['libelleMention'] ?></b> <br> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> ---@@@--- Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?></b>
                    </div>
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo $UE[$i]['ID_UE'] . "===>" . $UE[$i]['libelleUe'] ?> <?php } ?>
                            ---@@@--- V = Validé ---  V+R = Validé avec Reprise --- NV = Non Validé.
                    </p>
					<p style="text-align:center; font-size: 20px ">
					Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
					</P>
                    <div>
                        <table id="liste" class="display" style="width:100%;margin:auto;page-break-inside: avoid;" border="1" cellspacing="2" cellpadding="2">
                            <thead class="thead">
                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; font-size: 30px; vertical-align: middle;"> Année Etude:<br><?php echo $tete[0]['Libelle_AnEtude']; ?></th>
                                    <?php for ($i = 0; $i < count($UE); $i++) { ?><th colspan="2" style="text-align:center;font-size: 21px;padding: 5px;background-color:
                                    <?php if ($classe == $UE[$i]['Nom_Classe']) {
                                            echo "#80C8F2";
                                        } else {
                                            echo "#667DFD";
                                        } ?>"><?php echo $UE[$i]['ID_UE'] ?></th> <?php } ?>
                                    <th style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br />Crédits<br /> Capitalisés</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br>d'UE<br> Validée(s)</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">%age<br> d'UE</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Moyenne</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Décision</th>

                                </tr>
                                <tr style="background-color:#7BA7CE">
                                    <?php $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) { ?>
                                        <th style="background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                                                        echo "#80C8F2";
                                                                    } else {
                                                                        echo "#667DFD";
                                                                    } ?>;font-size: 21px;padding: 5px;">Credit</th>
                                        <th style="text-align:center;padding: 5px;font-size: 21px;background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                        echo "#80C8F2";
                                        } else {
                                        echo "#667DFD";
                                        } ?>"><?php $tCredit += $UE[$i]['Credit'];
                                        echo $UE[$i]['Credit'] ?></th>
                                    <?php } ?>
                                    <th style="text-align:center;padding: 5px;font-size: 21px;"><?php echo $tCredit; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($typeEtudiant && $typeEtudiant!=='all') $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut = '$typeEtudiant' ORDER BY noms,prenoms ASC;";
                                else if($typeEtudiant=='all' || $typeEtudiant==null) $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
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
                                    $reprise = 0;
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
                                            //SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            if(!empty($r)){
                                                $reprise=1;
                                                $ligne['reprise']=1;
                                            }
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            if(AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->pedagogique==1 && $mecue<AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->Seuil_Ecu) $pedagogique=1;
                                            $mecue = max($mR, $mecue);
                                            // $mecue = $mRepr!=0? max($mRepr, $mR): max($mR, $mecue);
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                            if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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
                                    if(!empty($resultat[$e]['reprise']) && $resultat[$e]['reprise']==1){
                                ?>
                                    <tr>
                                        <td style="vertical-align: middle;font-size: 17px;padding: 2px;"><?php echo $matricule ?></td>
                                        <td style="height: 25px; vertical-align: middle;font-size: 17px;padding: 2px;width: 250px;"><?php echo ("<b style='text-transform: uppercase'> $noms </b> $prenoms") ?></td>
                                        <?php
                                        for ($i = 0; $i < count($moyenne); $i++) { ?>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;<?php if(($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] < $moyauto)) echo 'background-color:grey'?> "> <?php echo (($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] >= $moyauto) && $moyG >= $moyNor) ? '<del>' . number_format($moyenne[$i][1], 2, ',', '') . '</del> <br> <b>' . number_format($moyenne[$i][4], 2, ',', '') . '</b>' : number_format($moyenne[$i][1], 2, ',', ''); ?></td>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                            echo $moyenne[$i][2];
                                            } else {
                                            echo 'INV';
                                            } ?></td>
                                        <?php
                                            $existee = 0;
                                            $KdeUE1 = $moyenne[$i][5];
                                            $KdeUE2 = number_format($moyenne[$i][1], 2, '.', '');
                                            $KdeUE3 = $moyenne[$i][2];
                                            $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$KdeUE1';";
                                            $dater = DB::select($rrrrrr);
                                            $dater=array_map(function ($value) {
                                                return (array)$value;
                                            }, $dater);
                                            $existee = count($dater);
                                            //$rrr=
                                        } ?>

                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo $tValide;
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo '<b>' . $nV . '</b> sur <b>' . count($moyenne) . '</b>';
                                        $ttcr = number_format(count($moyenne), 0, ',', ' ');
                                        } else {
                                        $ttcr = 0;
                                        echo 'INV';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($percentUE, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?>%</td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($moyG, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                        } ?>"><?php if ($etat == "OUI") {
                                        if ($percentUE >= $taux && $percentUE < 100) {
                                        echo "<b>V + R </b>";
                                        $deci = "V + R";
                                        } else {
                                        if ($percentUE < $taux) {
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
                                        //$requeti = mysqli_query($connect->getVarConnexion(), "REPLACE INTO recap_annuel(`MATRICULE`,`Code_Annee_Etude`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,Nbre_UE_Val,DecisionJury,`Observation`,`Auteur`,`Moment`) VALUES('$matricule','$annee_etude',$anneeacad,$tValide,'$percentUE','$moyG',$nV,'$deci','$ttcr','" . $_SESSION['slogin'] . "',CURRENT_TIMESTAMP);");
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
                                    </tr> <?php  }}  ?>
                                    <tr style="background-color:#7BA7CE">
                                        <th colspan="2" rowspan="2" style="text-align: center; font-size: 25px;padding: 2px;"> Nombre etudiants<br />ayant validé l'UE </th>
                                        <?php
                                        $tCredit = 0;
                                        for ($i = 0; $i < count($UE); $i++) {

                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            //$recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            /*$recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue)*/;
                                            $oo = 0;
                                        ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center;font-size: 21px;padding: 2px;"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                        } ?>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                    </tr>
                            </tbody>
                        </table>
                        <div style=" text-align:left;margin-left:250px">
                            <div style="width: 2000px;font-size: 21px;">
                                <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                                Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                                <?php $t = $sv + $svr + $snv + $svr_70;
                                $tnva = $svr + $svr_70 + $snv;
                                echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                                echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/> ';
                                echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / (($t!==0)?$t:1), 2, '.', ' ') . '%  </b> <br/> ';
                                echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/>';
                                echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/><br/>';
                                echo '<b style="font-size: 20px"> RECAPITULATIF ANNUEL:  ' . $sv . ' etudiants soit un pourcentage de ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% ont validé l\'année contre ' . $tnva . ' etudiant(s) soit un pourcentage de : ' . number_format(100 * ($svr + $svr_70 + $snv) / (($t!==0)?$t:1), 2, '.', ' ') . '% de l\'effectif de la classe. </b> <br/>';
                                ?>
                            </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                        </div>
                    </div>
                </page>
                <?php
                $strHtml = ob_get_clean();
                try {
                    $tabMarges = array(10, 10, 10, 10);
                    $objPDF = new HTML2PDF('L', 'A1', 'fr');
                    $objPDF->pdf->SetDisplayMode('real');
                    $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                    $objPDF->writeHTML($strHtml);
                    $dateNow = new DateTime('now');
                    $dateNow = $dateNow->format('Y.m.d.H.i.s');
                    //'PV_'.$anneeEtude.'_'.$mysql->anne($anneeAcademique).'
                    $objPDF->pdf->SetTitle('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD.'_Rattrapages');
                    $objPDF->Output('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD . '.pdf');
                } catch (Exception $e) {
                    abort(500, 'Erreur lors de la création du document.');
                }
        }
        /* if (request()->btn=="Reprises") {
            extract($this->extraireEntrees());
            if(empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;");
            if(empty($UE))
                abort(404);
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
            // return response()->json($UE);
            $classe = $UE[0]['Nom_Classe'];
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $moyNor = $rTaux[0]['Moyenne'];
            $moyauto = $rTaux[0]['MoyenneUE'];
            //  var_dump($rTaux[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
            <page backtop="43mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:40%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                PROCES VERBAL DE DELIBERATION ANNUELLE<br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="text-align: right;width:660px"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right"><b style="color:RED">INV = Inscription Non Validée </b> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>
                <div style="text-align: center  ">
                    Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Mention: <b><?php echo $tete[0]['libelleMention'] ?></b> <br> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> ---@@@--- Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?></b>
                </div>
                <p style="text-align:justify;color: #8a1f11; font-size: 15px">
                    <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo $UE[$i]['ID_UE'] . "===>" . $UE[$i]['libelleUe'] ?> <?php } ?>
                ---@@@--- V = Validé ---  V+R = Validé avec Reprise --- NV = Non Validé.
                </p>
                <div>
                    <br />
                    <table id=" liste" class="display" style="width:100%;margin:auto" border="1" cellspacing="2" cellpadding="2">
                        <thead class="thead">
                            <tr style="background-color:#7BA7CE">
                                <th colspan="2" rowspan="2" style="text-align: center; font-size: 20px"> Année Etude:<br><?php echo $tete[0]['Libelle_AnEtude']; ?></th>
                                <?php for ($i = 0; $i < count($UE); $i++) { ?><th rowspan="2" style="text-align:center;font-size: 14px;vertical-align: middle;padding: 7.5px;background-color:
                                <?php if ($classe == $UE[$i]['Nom_Classe']) {
                                        echo "#80C8F2";
                                    } else {
                                        echo "#667DFD";
                                    } ?>"><?php echo $UE[$i]['ID_UE'] ?></th> <?php } ?>
                                <th style="text-align:center;font-size: 11px">Nombre<br />Crédits<br /> Capitalisés</th>
                                <th rowspan="2" style="text-align:center;">Nombre<br>d'UE<br> Validée(s)</th>
                                <th rowspan="2" style="text-align:center;">Nombre<br>de<br> reprises</th>
                                <th rowspan="2" style="text-align:center;">%age<br> d'UE</th>
                                <th rowspan="2">Moyenne</th>
                                <th rowspan="2">Décision</th>

                            </tr>
                            <tr style="background-color:#7BA7CE">
                                <?php $tCredit = 0;
                                for ($i = 0; $i < count($UE); $i++) { ?>
                                    <?php $tCredit += $UE[$i]['Credit'];?>
                                <?php } ?>
                                <th style="text-align:center;"><?php echo $tCredit; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                            $listeE = DB::select($sql);
                            $listeE=array_map(function ($value) {
                                return (array)$value;
                            }, $listeE);
                            $sv = $svr = $snv = $svr_70 = 0;
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
                                        //SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
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
                                        $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                        $r = DB::select($listeN);
                                        $r=array_map(function ($value) {
                                            return (array)$value;
                                        }, $r);
                                        $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                        $mecue = floatval((int)$md === 0 && $col['dev']==0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
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
                                    $rue = round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? "V" : "R";
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
                                    <td style="width: 130px;height: 25px; vertical-align: middle"><?php echo ("<b style='text-transform: uppercase'> $noms </b> $prenoms") ?></td>
                                    <?php
                                    for ($i = 0; $i < count($moyenne); $i++) { ?>
                                        <td style="text-align:center;vertical-align: middle;<?php if ($moyenne[$i][2]=='R' && $etat=='OUI') echo 'background-color: grey';?>"><?php if ($etat == "OUI") {
                                        echo $moyenne[$i][2];
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                    <?php
                                        $existee = 0;
                                        $KdeUE1 = $moyenne[$i][5];
                                        $KdeUE2 = number_format($moyenne[$i][1], 2, '.', '');
                                        $KdeUE3 = $moyenne[$i][2];
                                        $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$KdeUE1';";
                                        $dater = DB::select($rrrrrr);
                                        $dater=array_map(function ($value) {
                                            return (array)$value;
                                        }, $dater);
                                        $existee = count($dater);
                                        //$rrr=
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
                                    echo '<b>' . count($moyenne)-$nV . '</b>';
                                    } else {
                                    echo 'INV';
                                    } ?></td>
                                    <td style="text-align:center;vertical-align: middle"><?php if ($etat == "OUI") {
                                    echo number_format($percentUE, 2, ',', ' ');
                                    } else {
                                    echo 'INV ';
                                    } ?>%</td>
                                    <td style="text-align:center;vertical-align: middle;"><?php if ($etat == "OUI") {
                                    echo number_format($moyG, 2, ',', ' ');
                                    } else {
                                    echo 'INV ';
                                    } ?></td>
                                    <td style="text-align:center;vertical-align: middle;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                    } ?>"><?php if ($etat == "OUI") {
                                    if ($percentUE >= $taux && $percentUE < 100) {
                                    echo "<b>V + R </b>";
                                    $deci = "V + R";
                                    } else {
                                    if ($percentUE < $taux) {
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
                                    //$requeti = mysqli_query($connect->getVarConnexion(), "REPLACE INTO recap_annuel(`MATRICULE`,`Code_Annee_Etude`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,Nbre_UE_Val,DecisionJury,`Observation`,`Auteur`,`Moment`) VALUES('$matricule','$annee_etude',$anneeacad,$tValide,'$percentUE','$moyG',$nV,'$deci','$ttcr','" . $_SESSION['slogin'] . "',CURRENT_TIMESTAMP);");
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
                    <div style=" text-align:left;margin-left:250px">
                        <div style="width: 2000px;">
                            <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                            Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                            Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                            Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                            <?php $t = $sv + $svr + $snv + $svr_70;
                            $tnva = $svr + $svr_70 + $snv;
                            echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                            echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/> ';
                            echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / (($t!==0)?$t:1), 2, '.', ' ') . '%  </b> <br/> ';
                            echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/>';
                            echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/><br/>';
                            echo '<b style="font-size: 20px"> RECAPITULATIF ANNUEL:  ' . $sv . ' etudiants soit un pourcentage de ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% ont validé l\'année contre ' . $tnva . ' etudiant(s) soit un pourcentage de : ' . number_format(100 * ($svr + $svr_70 + $snv) / (($t!==0)?$t:1), 2, '.', ' ') . '% de l\'effectif de la classe. </b> <br/>';
                            ?>
                        </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                    </div>
                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();
            try {
                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('L', 'A2', 'fr');
                $objPDF->pdf->SetDisplayMode('real');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                //'PV_'.$anneeEtude.'_'.$mysql->anne($anneeAcademique).'
                $objPDF->Output('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->value('ANNEE_ACAD') . '.pdf');
            } catch (Exception $e) {
                abort(500, 'erreur lors de la création du pdf !');
            }
        } */
        if (request()->btn=="Reprises") {
            extract($this->extraireEntrees());
            if(empty(request('anneeEtude')) || empty(request('anneeAcademique')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
            $univ=array_map(function ($value) {
                return (array)$value;
            }, $univ);
            $UE = DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;");
            if(empty($UE))
                abort(404);
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
            // return response()->json($UE);
            $classe = $UE[0]['Nom_Classe'];
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $moyNor = $rTaux[0]['Moyenne'];
            $moyauto = $rTaux[0]['MoyenneUE'];
            //  var_dump($rTaux[0]); exit;
            ob_start();
            ini_set('max_execution_time', 600);
            ?>
                <page backtop="10mm" backbottom="12mm" backleft="2mm" backright="2mm">
                    <page_footer>
                        <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                            <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                            <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                                <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                            </div>
                            <div style="display: inline; width: 25%; text-align: right"><b style="color:RED">INV = Inscription Non Validée </b> Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                        </div>
                    </page_footer>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:40%; text-align: center; font-size:20px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                PROCES VERBAL DE DELIBERATION ANNUELLE (REPRISES - SESSION NORMALE)<br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="text-align: right;width:940px"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                    <div style="text-align: center;font-size: 20px;">
                        Domaine: <b><?php echo $tete[0]['lib_domaine'] ?></b> ---@@@--- Mention: <b><?php echo $tete[0]['libelleMention'] ?></b> <br> Spécialité:<b><?php echo $tete[0]['libelleSpe'] ?></b> ---@@@--- Grade: <b><?php echo $tete[0]['libelleGrade'] ?></b> ---@@@--- Année Académique:<b><?php echo  $UE[0]['ANNEE_ACAD'] ?></b> ---@@@--- Année Etude: <b><?php echo  $tete[0]['Libelle_AnEtude'] ?></b>
                    </div>
                    <p style="text-align:justify;color: #8a1f11; font-size: 20px">
                        <u>Légende:</u> <?php for ($i = 0; $i < count($UE); $i++) { ?><?php echo $UE[$i]['ID_UE'] . "===>" . $UE[$i]['libelleUe'] ?> <?php } ?>
                            ---@@@--- V = Validé ---  V+R = Validé avec Reprise --- NV = Non Validé.
                    </p>
					<p style="text-align:center; font-size: 20px ">
					Procès-verbal de délibération n°_____________________/ENS/UAC/DA/SGE/DLLSS/DSEE/SDLR/SA du__________________20
					</P>
                    <div>
                        <table id="liste" class="display" style="width:100%;margin:auto;page-break-inside: avoid;" border="1" cellspacing="2" cellpadding="2">
                            <thead class="thead">
                                <tr style="background-color:#7BA7CE">
                                    <th colspan="2" rowspan="2" style="text-align: center; font-size: 30px; vertical-align: middle;"> Année Etude:<br><?php echo $tete[0]['Libelle_AnEtude']; ?></th>
                                    <?php for ($i = 0; $i < count($UE); $i++) { ?><th colspan="2" style="text-align:center;font-size: 21px;padding: 5px;background-color:
                                    <?php if ($classe == $UE[$i]['Nom_Classe']) {
                                            echo "#80C8F2";
                                        } else {
                                            echo "#667DFD";
                                        } ?>"><?php echo $UE[$i]['ID_UE'] ?></th> <?php } ?>
                                    <th style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br />Crédits<br /> Capitalisés</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">Nombre<br>d'UE<br> Validée(s)</th>
                                    <th rowspan="2" style="text-align:center;font-size: 21px;padding: 5px;">%age<br> d'UE</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Moyenne</th>
                                    <th rowspan="2" style="font-size: 21px;padding: 5px;">Décision</th>

                                </tr>
                                <tr style="background-color:#7BA7CE">
                                    <?php $tCredit = 0;
                                    for ($i = 0; $i < count($UE); $i++) { ?>
                                        <th style="background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                                                        echo "#80C8F2";
                                                                    } else {
                                                                        echo "#667DFD";
                                                                    } ?>;font-size: 21px;padding: 5px;">Credit</th>
                                        <th style="text-align:center;padding: 5px;font-size: 21px;background-color:<?php if ($classe == $UE[$i]['Nom_Classe']) {
                                        echo "#80C8F2";
                                        } else {
                                        echo "#667DFD";
                                        } ?>"><?php $tCredit += $UE[$i]['Credit'];
                                        echo $UE[$i]['Credit'] ?></th>
                                    <?php } ?>
                                    <th style="text-align:center;padding: 5px;font-size: 21px;"><?php echo $tCredit; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($typeEtudiant && $typeEtudiant!=='all') $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' and i.codeStatut = '$typeEtudiant' ORDER BY noms,prenoms ASC;";
                                else if($typeEtudiant=='all' || $typeEtudiant==null) $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms, i.S_Finance etat FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                                $listeE = DB::select($sql);
                                $listeE=array_map(function ($value) {
                                    return (array)$value;
                                }, $listeE);
                                //dd($listeE);
                                $sv = $svr = $snv = $svr_70 = 0;
                                $etudiantsAyantValides=[];
                                for ($i = 0; $i < count($UE); $i++) {
                                    $etudiantsAyantValides[$UE[$i]['ID_UE']]=0;
                                }
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
                                    $reprise = 0;
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
                                            //SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
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
                                            $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$matricule' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                            $r = DB::select($listeN);
                                            $r=array_map(function ($value) {
                                                return (array)$value;
                                            }, $r);
                                            if(!empty($r)){
                                                $reprise=1;
                                                $ligne['reprise']=1;
                                            }
                                            $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                            $mecue = floatval((int)$md === 0 && $col['dev']==0 ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                            if(AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->pedagogique==1 && $mecue<AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->Seuil_Ecu) $pedagogique=1;
                                            //$mecue = max($mR, $mecue);
                                            // $mecue = $mRepr!=0? max($mRepr, $mR): max($mR, $mecue);
                                            $moyenne += $mecue * $col['ponderation'];
                                            $moyenneReprise += $mRepr * $col['ponderation'];
                                            if(round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) $etudiantsAyantValides[$UE[$i]['ID_UE']]+=1;
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
                                    if(!empty($resultat[$e]['reprise']) && $resultat[$e]['reprise']==1){
                                ?>
                                    <tr>
                                        <td style="vertical-align: middle;font-size: 17px;padding: 2px;"><?php echo $matricule ?></td>
                                        <td style="height: 25px; vertical-align: middle;font-size: 17px;padding: 2px;width: 250px;"><?php echo ("<b style='text-transform: uppercase'> $noms </b> $prenoms") ?></td>
                                        <?php
                                        for ($i = 0; $i < count($moyenne); $i++) { ?>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;<?php if(($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] < $moyauto)) echo 'background-color:grey'?> "> <?php echo (($moyenne[$i][1] < $moyenne[$i][4]) && ($moyenne[$i][1] >= $moyauto) && $moyG >= $moyNor) ? '<del>' . number_format($moyenne[$i][1], 2, ',', '') . '</del> <br> <b>' . number_format($moyenne[$i][4], 2, ',', '') . '</b>' : number_format($moyenne[$i][1], 2, ',', ''); ?></td>
                                            <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                            echo $moyenne[$i][2];
                                            } else {
                                            echo 'INV';
                                            } ?></td>
                                        <?php
                                            $existee = 0;
                                            $KdeUE1 = $moyenne[$i][5];
                                            $KdeUE2 = number_format($moyenne[$i][1], 2, '.', '');
                                            $KdeUE3 = $moyenne[$i][2];
                                            $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$matricule' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='$KdeUE1';";
                                            $dater = DB::select($rrrrrr);
                                            $dater=array_map(function ($value) {
                                                return (array)$value;
                                            }, $dater);
                                            $existee = count($dater);
                                            //$rrr=
                                        } ?>

                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo $tValide;
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo '<b>' . $nV . '</b> sur <b>' . count($moyenne) . '</b>';
                                        $ttcr = number_format(count($moyenne), 0, ',', ' ');
                                        } else {
                                        $ttcr = 0;
                                        echo 'INV';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($percentUE, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?>%</td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;"><?php if ($etat == "OUI") {
                                        echo number_format($moyG, 2, ',', ' ');
                                        } else {
                                        echo 'INV ';
                                        } ?></td>
                                        <td style="text-align:center;vertical-align: middle;font-size: 20px;padding: 2px;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
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
                                        } ?>"><?php if ($etat == "OUI") {
                                        if ($percentUE >= $taux && $percentUE < 100) {
                                        echo "<b>V + R </b>";
                                        $deci = "V + R";
                                        } else {
                                        if ($percentUE < $taux) {
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
                                        //$requeti = mysqli_query($connect->getVarConnexion(), "REPLACE INTO recap_annuel(`MATRICULE`,`Code_Annee_Etude`,`id_Annee_Acad`,`Nbre_Cr_Capi`,`Ptage_Obt`,`MoyenneSem`,Nbre_UE_Val,DecisionJury,`Observation`,`Auteur`,`Moment`) VALUES('$matricule','$annee_etude',$anneeacad,$tValide,'$percentUE','$moyG',$nV,'$deci','$ttcr','" . $_SESSION['slogin'] . "',CURRENT_TIMESTAMP);");
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
                                    </tr> 
                                    <?php  }}  ?>
                                    <tr style="background-color:#7BA7CE">
                                        <th colspan="2" rowspan="2" style="text-align: center; font-size: 25px;padding: 2px;"> Nombre etudiants<br />ayant validé l'UE </th>
                                        <?php
                                        $tCredit = 0;
                                        for ($i = 0; $i < count($UE); $i++) {

                                            $ue_ecue = $UE[$i]['Id_Attr_ue_Sem'];
                                            //$recue = DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_ecue AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC");
                                            /*$recue=array_map(function ($value) {
                                                return (array)$value;
                                            }, $recue)*/;
                                            $oo = 0;
                                        ?><th colspan="<?php echo 2 + $oo; ?>" style="vertical-align: middle;text-align:center;font-size: 21px;padding: 2px;"><?php echo $etudiantsAyantValides[$UE[$i]['ID_UE']] ?></th> <?php
                                        } ?>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                        <th rowspan="2" width="5%" style="vertical-align: middle;text-align: center;font-size: 21px;padding: 2px;">---</th>
                                    </tr>
                            </tbody>
                        </table>
                        <div style=" text-align:left;margin-left:250px">
                            <div style="width: 2000px;font-size: 21px;">
                                <br> Nombre d'étudiants ayant validé le semestre <b>sans reprise</b>:<b> <?php echo $sv; ?></b>
                                Nombre d'étudiants ayant validé le semestre <b>avec des reprise</b>:<b> <?php echo $svr; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et supérieur à 70% </b>:<b> <?php echo $svr_70; ?></b>
                                Nombre d'étudiants <b>n'ayant pas validé</b> le semestre <b> et inférieur à 70% </b>: <b><?php echo $snv; ?></b>
                                <?php $t = $sv + $svr + $snv + $svr_70;
                                $tnva = $svr + $svr_70 + $snv;
                                echo "sur un effectif total de :<b>" . $t . "</b> <br/> ";
                                echo 'Taux des validations sans reprises(100%):<b> ............... ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/> ';
                                echo 'Taux des validations avec reprises (80%):<b> ............... ' . number_format(100 * $svr / (($t!==0)?$t:1), 2, '.', ' ') . '%  </b> <br/> ';
                                echo 'Taux des non validations<b> et supérieur à 70%: </b>..<b>' . number_format(100 * $svr_70 / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/>';
                                echo 'Taux des non validations <b> et inférieur ou égal à 70% </b>:..<b>' . number_format(100 * $snv / (($t!==0)?$t:1), 2, '.', ' ') . '% </b> <br/><br/>';
                                echo '<b style="font-size: 20px"> RECAPITULATIF ANNUEL:  ' . $sv . ' etudiants soit un pourcentage de ' . number_format(100 * $sv / (($t!==0)?$t:1), 2, '.', ' ') . '% ont validé l\'année contre ' . $tnva . ' etudiant(s) soit un pourcentage de : ' . number_format(100 * ($svr + $svr_70 + $snv) / (($t!==0)?$t:1), 2, '.', ' ') . '% de l\'effectif de la classe. </b> <br/>';
                                ?>
                            </div> <br> Fait à <b><?=$univ[0]['ville']?></b>; le <?php echo ' ' . date('d/m/Y'); ?>
                        </div>
                    </div>
                </page>
                <?php
                $strHtml = ob_get_clean();
                try {
                    $tabMarges = array(10, 10, 10, 10);
                    $objPDF = new HTML2PDF('L', 'A1', 'fr');
                    $objPDF->pdf->SetDisplayMode('real');
                    $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                    $objPDF->writeHTML($strHtml);
                    $dateNow = new DateTime('now');
                    $dateNow = $dateNow->format('Y.m.d.H.i.s');
                    //'PV_'.$anneeEtude.'_'.$mysql->anne($anneeAcademique).'
                    $objPDF->pdf->SetTitle('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD);
                    $objPDF->Output('PV_' . $anneeEtude . '_' . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD . '.pdf');
                } catch (Exception $e) {
                    abort(500, 'Erreur lors de la création du document.');
                }
        }
    }

    public function convert_array($var){
        $var=array_map(function ($value) {
            return (array)$value;
        }, $var);
        return $var;
    }

    public function impressionEmargement()
    {
        if (request()->btn=="Liste des étudiants") {
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            $rc = $this->convert_array(DB::select("SELECT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,inscriptions.`Id_Annee` as annee,ANNEE_ACAD,`ID_FILIERE`,LibStatut,`Group_PDGQ`,`frais_paye`,`S_Finance` FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and inscriptions.`codeStatut` =statut.`codeStatut` AND inscriptions.ID_FILIERE='$anneeEtude' AND inscriptions.Id_Annee='$anneeAcademique' ORDER BY LibStatut,nomEtu,prenomsEtu ASC ;"));
            ob_start(); ?>
            <page backtop="35mm" backbottom="10mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 10%"><img style="width: 100; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:75%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                LISTE DES ETUDIANTS ( <?php echo $anneeEtude . '  -  ' . $rc[0]["ANNEE_ACAD"] ?> )<br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                            <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                            <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                                <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                            </div>
                            <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                        </div>
                    </div>
                </page_footer>

                <div style="display: block; width:95%;align-content: center; margin:auto">

                    <table style="width:100%; margin:auto;" border="1px">
                        <thead>
                            <tr style="text-align: center;">
                                <th width="10%" scope="col"> Matricule</th>
                                <th width="30%" scope="col">Nom </th>
                                <th width="30%" scope="col"> Prénoms </th>
                                <th width="10%" scope="col">Statut </th>
                                <th width="10%" scope="col">Inscrit </th>

                            </tr>
                        </thead>

                        <?php $nbr = count($rc);
                        for ($i = 0; $i < count($rc); $i++) { ?>

                            <tr>
                                <td style="font-size: 10px; height: 20px; width: 125px; vertical-align: middle"><?php echo $rc[$i]["Matricule"] ?></td>
                                <td style=" height: 20px; width: 150px; vertical-align: middle"><?php echo $rc[$i]["nomEtu"] ?></td>
                                <td style=" height: 20px; width: 200px; vertical-align: middle"><?php echo UTF8_encode($rc[$i]["prenomsEtu"]) ?></td>
                                <td style="font-size: 10px; height: 20px; width: 50px; vertical-align: middle"><?php echo $rc[$i]["LibStatut"] ?></td>
                                <td style="font-size: 10px; height: 20px; width: 50px; vertical-align: middle"><?php echo $rc[$i]["S_Finance"] ?></td>



                            </tr>
                        <?php   } ?>

                    </table>
                    <div style="padding-top: 10px;">
                        <i> <b style="font-size: 10px;color: red"><u>NB:</u> Au total <?php echo ' ' . $nbr . ' '; ?> etudiants se serait(aient) au sein de ce groupe pédagogique.</b></i><br /><br />
                        <u><b style="text-align: left;">SIGNATURE DE L'ENSEIGNANT:</b></u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                    </div>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

            // require('../html2pdf/html2pdf.class.php');
                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('MonPdf_ ' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'erreur lors de la création du pdf !');
            }
        }
        if (request()->btn=="Inscrits") {
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            $rc = $this->convert_array(DB::select("SELECT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,inscriptions.`Id_Annee` as annee,ANNEE_ACAD,`ID_FILIERE`,LibStatut,`Group_PDGQ`,`frais_paye`,`S_Finance` FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and inscriptions.`codeStatut` =statut.`codeStatut` AND inscriptions.ID_FILIERE='$anneeEtude' AND inscriptions.Id_Annee='$anneeAcademique' AND S_Finance='OUI' ORDER BY nomEtu,prenomsEtu ASC ;"));
            if (empty($rc)) {
                $message='Encore aucun inscrit';
                abort(404);
            }
            ob_start(); ?>
            <page backtop="50mm" backbottom="10mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 10%"><img style="width: 80%;height: 100px;width: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:70%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                LISTE DE PRESENCE DES ETUDIANTS <u style="color: red">(INSCRITS)</u> <br>
                                ----*----*----*---*---<br>
                            </td>
                            <td style="text-align:right; width: 20%">
                            <img style="width: 80%;height: 100px;width: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" />
                            </td>
                        </tr>

                    </table>
                    <div style="text-align: center; ">
                        <u style="line-height: 10px"> <b style="color: red; font-size: 15px">LISTE DE PRESENCE AU COURS DES ETUDIANTS <?php echo "  " . " <br><br> " . 'Année Académique :  ' . $rc[0]["ANNEE_ACAD"] . " " . $rc[0]["Group_PDGQ"] . '  Année Etude :  ' . $anneeEtude ?> </b></u>
                    </div>
                </page_header>
                <page_footer>
                    <i> <b style="font: 10px;"><u>NB:</u> l'enseignant est prié de barrer les cases des absents après l'appel.</b></i><br>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>

                <div style="display: block; width:95%;align-content: center; padding-left: 10px; padding-top: 10px">
                    <u><b>ECUE:</b></u>....................................................................... <br><br>
                    <u><b>NOM ET PRENOMS DE L'ENSEIGNANT:</b></u>.......................................................................<br><br>
                    <table style="width:100%" border="1px">
                        <thead>
                            <tr style="text-align: center;">
                                <th width="10%" scope="col"> Matricule</th>
                                <th width="30%" scope="col">Nom </th>
                                <th width="30%" scope="col"> Prénoms </th>
                                <th width="10%" scope="col">Statut </th>
                                <th width="5%" scope="col">Emargement </th>
                                <th width="5%" scope="col">Observations </th>
                            </tr>
                        </thead>

                        <?php $nbr = count($rc);
                        for ($i = 0; $i < count($rc); $i++) { ?>

                            <tr>
                                <td style="font-size: 10px; height: 20px; width: 125px; vertical-align: middle"><?php echo $rc[$i]["Matricule"] ?></td>
                                <td style=" height: 20px; width: 150px; vertical-align: middle"><?php echo $rc[$i]["nomEtu"] ?></td>
                                <td style=" height: 20px; width: 200px; vertical-align: middle"><?php echo $rc[$i]["prenomsEtu"] ?></td>
                                <td style="font-size: 10px; height: 20px; width: 50px; vertical-align: middle"><?php echo $rc[$i]["LibStatut"] ?></td>
                                <td style=" height: 20px; width: 50px"><?php //echo $rc[$i]["LibStatut"]
                                                                        ?></td>
                                <td style=" height: 20px; width: 50px"><?php //echo $rc[$i]["LibStatut"]
                                                                        ?></td>

                            </tr>
                        <?php   } ?>

                    </table>
                    <div style="padding-top: 20px;">
                        <i> <b style="font-size: 10px;color: red"><u>NB:</u> Au total <?php echo ' ' . $nbr . ' '; ?> etudiants ne se serait(aient) pas encore inscrit(s) au sein de ce groupe pédagogique</b></i><br /><br />
                        <u><b style="text-align: left;">SIGNATURE DE L'ENSEIGNANT:</b></u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <u><b style="text-align: right;">SIGNATURE DU RESPONSABLE D'AMPHI:</b></u>
                    </div>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Liste de présence des inscrits_ ' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
            }
        }
        if (request()->btn=="Non inscrits") {
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            $rc = $this->convert_array(DB::select("SELECT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,inscriptions.`Id_Annee` as annee,ANNEE_ACAD,`ID_FILIERE`,LibStatut,`Group_PDGQ`,`frais_paye`,`S_Finance` FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and inscriptions.`codeStatut` =statut.`codeStatut` AND inscriptions.ID_FILIERE='$anneeEtude' AND inscriptions.Id_Annee='$anneeAcademique' AND S_Finance='NON' ORDER BY nomEtu,prenomsEtu ASC ;"));
            if (empty($rc)) {
                $message='Aucun non inscrits';
                abort(404);
            }
            ob_start(); ?>
            <page backtop="50mm" backbottom="10mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 10%"><img style="width: 80%;height: 100px;width: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:70%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                LISTE DE PRESENCE DES ETUDIANTS <u style="color: red">(NON ENCORE INSCRITS)</u> <br>
                                ----*----*----*---*---<br>
                            </td>
                            <td style="text-align:right; width: 20%">
                            <img style="width: 80%;height: 100px;width: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" />
                            </td>
                        </tr>

                    </table>
                    <div style="text-align: center; ">
                        <u style="line-height: 10px"> <b style="color: red; font-size: 15px">LISTE DE PRESENCE AU COURS DES ETUDIANTS <?php echo "  " . " <br><br> " . 'Année Académique :  ' . $rc[0]["ANNEE_ACAD"] . " " . $rc[0]["Group_PDGQ"] . '  Année Etude :  ' . $anneeEtude ?> </b></u>
                    </div>
                </page_header>
                <page_footer>
                    <i> <b style="font: 10px;"><u>NB:</u> l'enseignant est prié de barrer les cases des absents après l'appel.</b></i><br>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>

                <div style="display: block; width:95%;align-content: center; padding-left: 10px; padding-top: 10px">
                    <u><b>ECUE:</b></u>....................................................................... <br><br>
                    <u><b>NOM ET PRENOMS DE L'ENSEIGNANT:</b></u>.......................................................................<br><br>
                    <table style="width:100%" border="1px">
                        <thead>
                            <tr style="text-align: center;">
                                <th width="10%" scope="col"> Matricule</th>
                                <th width="30%" scope="col">Nom </th>
                                <th width="30%" scope="col"> Prénoms </th>
                                <th width="10%" scope="col">Statut </th>
                                <th width="5%" scope="col">Emargement </th>
                                <th width="5%" scope="col">Observations </th>
                            </tr>
                        </thead>

                        <?php $nbr = count($rc);
                        for ($i = 0; $i < count($rc); $i++) { ?>

                            <tr>
                                <td style="font-size: 10px; height: 20px; width: 125px; vertical-align: middle"><?php echo $rc[$i]["Matricule"] ?></td>
                                <td style=" height: 20px; width: 150px; vertical-align: middle"><?php echo $rc[$i]["nomEtu"] ?></td>
                                <td style=" height: 20px; width: 200px; vertical-align: middle"><?php echo $rc[$i]["prenomsEtu"] ?></td>
                                <td style="font-size: 10px; height: 20px; width: 50px; vertical-align: middle"><?php echo $rc[$i]["LibStatut"] ?></td>
                                <td style=" height: 20px; width: 50px"><?php //echo $rc[$i]["LibStatut"]
                                                                        ?></td>
                                <td style=" height: 20px; width: 50px"><?php //echo $rc[$i]["LibStatut"]
                                                                        ?></td>

                            </tr>
                        <?php   } ?>

                    </table>
                    <div style="padding-top: 20px;">
                        <i> <b style="font-size: 10px;color: red"><u>NB:</u> Au total <?php echo ' ' . $nbr . ' '; ?> etudiants ne se serait(aient) pas encore inscrit(s) au sein de ce groupe pédagogique</b></i><br /><br />
                        <u><b style="text-align: left;">SIGNATURE DE L'ENSEIGNANT:</b></u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <u><b style="text-align: right;">SIGNATURE DU RESPONSABLE D'AMPHI:</b></u>
                    </div>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Liste de présence des non inscrits_ ' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
                }
            }

        }
    public function releveNotes()
    {
        if (request('btn')=='Imprimer') {
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')) || empty(request('etud')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');

            //echo var_dump($_GET);
            $UE = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.id_S_Gr_Spe='$semestreEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique';"));
            if(empty($UE)){$message="Aucune attribution d'UE à ce semestre n'a été trouvée";
            abort(404);}
            $etudiant = $this->convert_array(DB::select("SELECT inscriptions.`MATRICULE` as Matricule,etudiant.photo,nomEtu,prenomsEtu,sexeEtu,inscriptions.`Id_Annee` as annee,ANNEE_ACAD,`ID_FILIERE`,dateNaisEtu,lieuNaisEtu FROM `inscriptions`,etudiant,anneeacademique,annee_etude WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and inscriptions.`MATRICULE`= '$etud'  and `Id_Annee` = $anneeAcademique  and `ID_FILIERE` = '$anneeEtude'  "));
            $se = $this->convert_array(DB::select("SELECT * FROM `classe` where id_S_Gr_Spe='$semestreEtude';"));
            $tete = $this->convert_array(DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'"));
            $rTaux = $this->convert_array(DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;"));
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];

            //var_dump($UE[0]); exit;
            ob_start();  ?>
            <page backtop="15mm" backbottom="2mm" backleft="2mm" backright="2mm">
            <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                           <!--<td style="width: 10%"><img style="width: 100; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>-->
                            <td style="width:50%; text-align: center; font-size:12px; font-weight: bold">
                                <!--REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['Ecol_Ufr']) ?><br>
                                ----*----*----*---*---<br>
                                <b style="color:RED"><?php echo $tete[0]['libelleGrade'] ?> en <?php echo $tete[0]['libelleSpe'] ?></b><br>
                                ----*----*----*---*---<br><br><br>-->
                            </td>
                            <!--<td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>-->

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <!--<div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">
                            <b style="color: red"> Rattr. = Rattrapage - Repr. = Reprise - Pond. = Pondération - Moy. = Moyenne</b><br>
                            Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>-->
                </page_footer>
                <div style="padding-top: 28px; font-size: 12px; text-align: center;">
                    <table style="width:100%;margin:auto" cellpadding="0" cellspacing="0" border="0">

                        <tr style="text-align: center;">
                            <td style="vertical-align: top; text-align:center"><img style="width: 50px; height: 50px" src=<?php
                                                                                                                                    clearstatcache();
                                                                                                                                    $filename = './storage/assets/img/etudiants/'.$etudiant[0]['photo'];
                                                                                                                                    $check =  @fopen($filename, 'r');
                                                                                                                                    if ($etudiant[0]['photo'] && Storage::exists('public/assets/img/etudiants/'.$etudiant[0]['photo'])) {
                                                                                                                                        echo $filename;
                                                                                                                                    } else {
                                                                                                                                        $titre = '';
                                                                                                                                        if ($etudiant[0]['sexeEtu'] == 'F') {
                                                                                                                                            $titre = 'Mlle';
                                                                                                                                        } else {
                                                                                                                                            $titre = 'M';
                                                                                                                                        }
                                                                                                                                        echo './storage/assets/img/etudiants/' . $titre . ".png";
                                                                                                                                    }
                                                                                                                                    ?>> </td>

                            <td style="vertical-align: middle; text-align: center">
                                <?php $titre = '';
                                if ($etudiant[0]['sexeEtu'] == 'F') {
                                    $titre = 'Mme/Mlle';
                                } else {
                                    $titre = 'M.';
                                }
                                echo 'Relevé de notes de ' . $titre . '  <b style=\'text-transform: uppercase;font-size: 14px;\'>' . $etudiant[0]['nomEtu'] . '</b>   <b style=\'font-size: 14px;\'>' . $etudiant[0]['prenomsEtu'] . '  </b>(' . $etudiant[0]['Matricule'] . ') <br>né(e) le ' . $etudiant[0]['dateNaisEtu'] . ' &agrave;  ' . $etudiant[0]['lieuNaisEtu'] . '; inscrit(e) en ' . $etudiant[0]['ID_FILIERE'] . ' <br>(Semestre: <b>' . $se[0]['Nom_Classe'] . '</b>) pour le compte de l\'année :<b> ' . $etudiant[0]['ANNEE_ACAD'] . '</b>' ?>

                            </td>

                        </tr>

                    </table>

                </div>
                <div style="margin-left: 60px; padding-top: 30px">
                    <?php echo 'N°............. /ENS/UAC/SGE/SS'; ?> <br><br>
                    <table width="120%" border="1" cellspacing="0" cellpadding="0" style="font-family:Courier;">
                        <tbody>
                            <tr bgcolor="#6495ed" style="text-align: center;">
                                <th width="5%" scope="col" valign="center">CODE</th>
                                <th width="70%" scope="col" valign="center">ECUE - UE </th>
                                <th width="8%" scope="col" valign="center">Note</th>
                                <th width="8%" scope="col" valign="center">Rattr.</th>
                                <th width="8%" scope="col" valign="center">Repr.</th>
                                <th width="9%" scope="col" valign="center">Pond.</th>
                                <th width="10%" scope="col" valign="center">Moy.</th>
                                <th width="5%" scope="col" valign="center">Crédit</th>
                                <th width="5%" scope="col" valign="center">Resultat</th>
                                <th width="5%" scope="col" valign="center">Appréciation</th>
                            </tr>
                            <?php $tCredit = 0;
                            $moyGene = 0.0;
                            $tValide = 0;
                            $nV = 0;
                            $pedagogique=0;
                            for ($i = 0; $i < count($UE); $i++) {
                                $rattrapages = 0;
                                $noteRatr = 0;
                                $tCredit += $UE[$i]['Credit'];
                                $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                                $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ecue_ue.pedagogique,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,Seuil_Ecu,Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"));
                            ?>


                                <?php $moyenne = 0.0;
                                $mecue = 0.0;
                                $moyenneReprise = 0.0;
                                for ($ii = 0; $ii < count($rc); $ii++) {
                                    $aColsEval = [];

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
                                    $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"]);
                                    // calc moyenne

                                    foreach ($aColsEval as $key => $col) { //var_dump($col);
                                        //DEVOIRS
                                        $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $md = floatval($r[0]['MD']);

                                        //PARTIEL
                                        $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $mp = floatval($r[0]['MD']);
                                        $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $mR = floatval($r[0]['MD']);
                                        $noteRatr += $mR;
                                        $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$etud' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                        $mecue = floatval((int)$md === 0 && $col['dev']==0   ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                        if($rc[$ii]['pedagogique']==1 && $mecue<$rc[$ii]['Seuil_Ecu']) $pedagogique=1;
                                        $mNorm = $mecue;
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
                                ?>
                                    <tr style="text-align: center;">
                                        <td style="font-size: 10px"><?php echo $rc[$ii]['ecue'] ?></td>
                                        <td style="font-size: 10px; width: 150px"><i><?php echo $rc[$ii]['libelleEcu'] ?></i></td>
                                        <td style="text-align: center;width: 5px;font-size: 10px"><?php echo number_format($mNorm, 2, ',', ' ') ?></td>
                                        <td style="text-align: center;width: 20px;font-size: 10px"><?php echo $mR == 0 ? "-" :  number_format($mR, 2, ',', ' ') ?></td>
                                        <td style="text-align: center;width: 20px;font-size: 10px"><?php echo $mRepr == 0 ? "-" :  number_format($mRepr, 2, ',', ' ') ?></td>
                                        <td style="text-align: center;width: 20px;font-size: 10px"><?php echo 100 * $rc[$ii]['Ponderation'] . "%" ?></td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td style="font-size: 10px"><?php if ($mecue >= 18) {
                                                                        echo "Excellent";
                                                                    } elseif ($mecue >= 16 && $mecue < 18) {
                                                                        echo "Très - Bien";
                                                                    } elseif ($mecue >= 14 && $mecue < 16) {
                                                                        echo "Bien";
                                                                    } elseif ($mecue >= 12 && $mecue < 14) {
                                                                        echo " Assez - Bien";
                                                                    } elseif ($mecue >= 10 && $mecue < 12) {
                                                                        echo "Passable";
                                                                    } elseif ($mecue == 10) {
                                                                        echo "Passable";
                                                                    } elseif ($mecue < 10) {
                                                                        echo " Insuffisant";
                                                                    }  ?> </td>
                                    </tr>
                                <?php }
                                $moyGene += $moyenne * $UE[$i]['Credit'];
                                $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;


                                ?>

                                <tr bgcolor="<?php if (round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) {
                                                    echo "#0B792F";
                                                } else {
                                                    echo "red";
                                                } ?>">
                                    <td style="color: #FFFFFF; text-align: center;font-size: 12px"><b><?php echo $UE[$i]['ID_UE'] ?></b></td>
                                    <td colspan="5" style="color: #FFFFFF;font-size: 10px; width: 300px" ><b><?php echo $UE[$i]['libelleUe'] ?></b></td>
                <td style=" color: #FFFFFF; text-align: center"><b><?php echo number_format($moyenne, 2, ',', ' ') ?></b></td>
                                    <td style="color: #FFFFFF; text-align: center" ><b><?php echo $UE[$i]['Credit'] ?></b></td>
                <?php $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$etud' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='" . $ue_num . "';";
                                // echo $rrrrrr;
                                //   exit();
                                $dater = $this->convert_array(DB::select($rrrrrr));
                                $knbr = count($dater);
                                //$valer = $knbr != 0 ? $dater[0]["Date_Val"] : "";
                ?>

                <td style=" color: #FFFFFF; text-align: center"><?php if (round($moyenne,3) >= $UE[$i]['Seuil_Ue']) {
                                                                        echo "<b>V</b>";
                                                                    } else {
                                                                        echo "<b>NV</b>";
                                                                    }  ?></td>
                <td style=" color: #FFFFFF; text-align: center;font-size: 10px"><b>
                                            <?php if (round($moyenne,3) >= 18) {
                                                echo "Excellent";
                                            } elseif (round($moyenne,3) >= 16 && round($moyenne,3) < 18) {
                                                echo "Très - Bien";
                                            } elseif (round($moyenne,3) >= 14 && round($moyenne,3) < 16) {
                                                echo "Bien";
                                            } elseif (round($moyenne,3) >= 12 && round($moyenne,3) < 14) {
                                                echo " Assez - Bien";
                                            } elseif (round($moyenne,3) >= 10 && round($moyenne,3) < 12) {
                                                echo "Passable";
                                            } elseif (round($moyenne,3) < 10) {
                                                echo " Insuffisant";
                                            }  ?>
                                        </b></td>
                                </tr>


                            <?php  } ?>
                        </tbody>
                    </table>

                    <div style="padding-left: 2px; margin: auto; padding-top: 8px;font-size: 9.5px;">
                        <table width="100%" border="1" cellspacing="2" cellpadding="2">
                            <tbody>
                                <tr bgcolor="#6495ed">
                                    <td colspan="6" style="text-align:center;"><b>Recapitulatif du Semestre</b></td>
                                </tr>
                                <tr>
                                    <td>Nombre d'UE Validées</td>
                                    <td style="text-align:center;"><b><?php echo $nV . '  sur  ' . count($UE); ?></b></td>
                                    <td>Crédits Capitalisés</td>
                                    <td style="text-align:center;"><b><?php echo $tValide . '  sur  ' . $tCredit; ?></b></td>
                                    <td>Pourcentage crédits Capitalisé</td>
                                    <td style="text-align:center;"><b><?php if(count($UE)!==0){$percentUE = 100 * $nV / count($UE);
                                                                        echo number_format($percentUE, 2, ',', ' ');}else{abort(500, 'Aucune UE trouvée');} ?>%</b></td>


                                </tr>
                                <tr>
                                    <td>Moyenne du Semestre</td>
                                    <td style="text-align:center;"><b> <?php echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' '); ?></b></td>
                                    <td>Appréciation du Semestre</td>
                                    <td style="text-align:center;"> <b><?php //echo $tCredit===0?0:number_format($moyGene/$tCredit,2,',',' ');
                                                                        if ($tCredit!==0) {
                                                                            if ($moyGene / $tCredit >= 18) {
                                                                                echo "Excellent";
                                                                            } elseif ($moyGene / $tCredit >= 16 && $moyGene / $tCredit < 18) {
                                                                                echo "Très - Bien";
                                                                            } elseif ($moyGene / $tCredit >= 14 && $moyGene / $tCredit < 16) {
                                                                                echo " Bien";
                                                                            } elseif ($moyGene / $tCredit >= 12 && $moyGene / $tCredit < 14) {
                                                                                echo " Assez - Bien";
                                                                            } elseif ($moyGene / $tCredit >= 10 && $moyGene / $tCredit < 12) {
                                                                                echo "Passable";
                                                                            } elseif ($moyGene / $tCredit == 10) {
                                                                                echo "Passable";
                                                                            } elseif ($moyGene / $tCredit < 10) {
                                                                                echo " insuffisant";
                                                                            }
                                                                        }
                                                                        ?></b></td>
                                    <td>Décision du Jury</td>
                                    <td style="width:150px;text-align:center;color: white;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
                                                                                                                echo "#ff4500";
                                                                                                            } else {
                                                                                                                if ($percentUE < $taux) {
                                                                                                                    echo "red";
                                                                                                                } else {
                                                                                                                    echo "#0B792F";
                                                                                                                }
                                                                                                            } ?> "><?php if ($percentUE >= $taux && $percentUE < 100) {
                                                                                                                        echo "<b>Validé avec Reprise </b>";
                                                                                                                        $dejury = 'Validé avec Reprise';
                                                                                                                    } else {
                                                                                                                        if ($percentUE < $taux) {
                                                                                                                            echo "<b>Non Validé</b>";
                                                                                                                            $dejury = 'Non Validé';
                                                                                                                        } else {
                                                                                                                            echo "<b>Validé</b>";
                                                                                                                            $dejury = 'Validé';
                                                                                                                        }
                                                                                                                    } ?></td>

                                </tr>


                            </tbody>
                        </table>

                    </div>
                    <?php
                    $resultat=$this->convert_array(DB::select("SELECT * FROM recap_annuel WHERE MATRICULE='$etud' AND Code_Annee_Etude='$anneeEtude' AND id_Annee_Acad=$anneeAcademique;"));
                    $UE1 = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;"));
                    $tCredit1=0;
                    for ($i = 0; $i < count($UE1); $i++) {
                        $tCredit1+=$UE1[$i]['Credit'];
                    }
                    if(substr(SemestreEtude::find($semestreEtude)->semestre->Lib_Semestre,-1,1)%2==0){?>
                        <div style="padding-top: 10px; padding-left: 130px;font-size: 10px;">
                            <table width="100%" style="margin-left  : auto;" border="1" cellspacing="2" cellpadding="2">
                                <tbody>
                                    <tr bgcolor="#6495ed">
                                        <td colspan="4" style="text-align:center;"><b>Récapitulatif de l'Année Académique</b></td>
                                    </tr>
                                    <tr>
                                        <td>Nombre d'UE Validées</td>
                                        <td style="text-align:center;"><b><?php echo $resultat[0]['Nbre_UE_Val'] . '  sur  ' . count($UE1); ?></b></td>
                                        <td>Crédits Capitalisés</td>
                                        <td style="text-align:center;"><b><?php echo $resultat[0]['Nbre_Cr_Capi'] . '  sur  ' . $tCredit1; ?></b></td>
                                    </tr>
                                    <tr>
                                        <td>Pourcentage crédits Capitalisé</td>
                                        <td style="text-align:center;"><b><?php echo number_format($resultat[0]['Ptage_Obt'], 2, ',', ' '); ?>%</b></td>
                                        <td>Moyenne Annuelle</td>
                                        <td style="text-align:center;"><b> <?php echo number_format($resultat[0]['MoyenneSem'], 2, ',', ' '); ?></b></td>
                                    </tr>

                                    <tr>
                                        <td>Appréciation de l'Année</td>
                                        <td style="text-align:center;"> <b><?php //echo $tCredit===0?0:number_format($moyGene/$tCredit,2,',',' ');
                                                                            if ($resultat[0]['MoyenneSem'] >= 18) {
                                                                                echo "Excellent";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 16 && $resultat[0]['MoyenneSem'] < 18) {
                                                                                echo "Très - Bien";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 14 && $resultat[0]['MoyenneSem'] < 16) {
                                                                                echo " Bien";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 12 && $resultat[0]['MoyenneSem'] < 14) {
                                                                                echo " Assez - Bien";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 10 && $resultat[0]['MoyenneSem'] < 12) {
                                                                                echo "Passable";
                                                                            } elseif ($resultat[0]['MoyenneSem'] == 10) {
                                                                                echo "Passable";
                                                                            } elseif ($resultat[0]['MoyenneSem'] < 10) {
                                                                                echo " Insuffisant";
                                                                            }
                                                                            ?></b></td>
                                        <td>Décision du Jury</td>
                                        <td style="text-align:center;color: white;background-color:<?php if ($resultat[0]['Ptage_Obt'] >= $taux && $resultat[0]['Ptage_Obt'] < 100) {
                                                                                                        echo "#ff4500";
                                                                                                    } else {
                                                                                                        if ($resultat[0]['Ptage_Obt'] < $taux) {
                                                                                                            echo "red";
                                                                                                        } else {
                                                                                                            echo "#0B792F";
                                                                                                        }
                                                                                                    } ?>"><?php if ($resultat[0]['Ptage_Obt'] >= $taux && $resultat[0]['Ptage_Obt'] < 100) {
                                                                                                                echo "<b>Validé avec Reprise </b>";
                                                                                                            } else {
                                                                                                                if ($resultat[0]['Ptage_Obt'] < $taux) {
                                                                                                                    echo "<b>Non Validé</b>";
                                                                                                                } else {
                                                                                                                    echo "<b>Validé</b>";
                                                                                                                }
                                                                                                            } ?></td>

                                    </tr>

                                </tbody>
                            </table>

                        </div>
                    <?php } ?>
                    <div style="display:inherit">
                        <table style="width:80%;" cellpadding="0" cellspacing="0" border="">

                            <tr>
                                <td style="text-align:center; width: 50%; font-size: 12px">
                                    <?php
                                    // $qr = new qrcode();
                                    // $qr = new qrcode('qrcode');
                                    $poutge = 100 * $nV / count($UE);
                                    $titre = '';
                                    if ($etudiant[0]['sexeEtu'] == 'F') {
                                        $titre = 'Mme/Mlle';
                                    } else {
                                        $titre = 'M.';
                                    }
                                    $mot =  'Relevé de notes de ' . $titre . '  ' . $etudiant[0]['nomEtu'] . ' ' . $etudiant[0]['prenomsEtu'] . ' (' . $etudiant[0]['Matricule'] . ') inscrit(e) en ' . $etudiant[0]['ID_FILIERE'] . '(Semestre: ' . $se[0]['Nom_Classe'] . ') pour le compte de l\'année : ' . $etudiant[0]['ANNEE_ACAD'] . '   Nombre d\'UE Validées :' . $nV . '  sur  ' . count($UE) . ' -- Crédits Capitalisés: ' . $tValide . '  sur  ' . $tCredit . '-- Pourcentage crédits Capitalisé: ' . number_format($poutge, 2, ',', ' ') . '%  -- Décision du Jury: ' . $dejury . '  Merci ---@@LMD Web Solutions@@---';
                                   // $qr->text($mot);
                                    // echo "<img src='".$qr->text($mot)."' border='0' />";
                                    ?>
                                </td>
                                <td style="text-align:center; width: 50%; font-size: 12px">
                                    <br>Fait à <?=$univ[0]['ville']?>; le<!--<?php echo ' ' . date('d/m/Y'); ?>--><br><br>
                                    Le Directeur Académique,<br><br><br><br>
                                    <br><br><u><b><?php echo $univ[0]['directeurAdjoint']?> </b></u>
                                </td>
                            </tr>

                        </table>
                    </div>

                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->pdf->SetTitle('Releve_de_note_ ' . $etudiant[0]['Matricule'] . '_' . $se[0]['Nom_Classe'] . '_' . $etudiant[0]['ANNEE_ACAD']);
                $objPDF->Output('Releve_de_note_ ' . $etudiant[0]['Matricule'] . '_' . $se[0]['Nom_Classe'] . '_' . $etudiant[0]['ANNEE_ACAD'] . '.pdf');
                // $objPDF->Output('MonPdf_ '.$dateNow.'.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du document.');
            }
        }
        if (request('btn')=='Imprimer pour tous') {
            ini_set('max_execution_time','0');
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')) || empty(request('etud')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');

            //echo var_dump($_GET);
            $UE = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND attr_ue_classe.id_S_Gr_Spe='$semestreEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique';"));
            if(empty($UE)){$message="Aucune attribution d'UE à ce semestre n'a été trouvée";
            abort(404);}
            $etudiants = $this->convert_array(DB::select("SELECT inscriptions.`MATRICULE` as Matricule,etudiant.photo,nomEtu,prenomsEtu,sexeEtu,inscriptions.`Id_Annee` as annee,ANNEE_ACAD,`ID_FILIERE`,dateNaisEtu,lieuNaisEtu FROM `inscriptions`,etudiant,anneeacademique,annee_etude WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude  and `Id_Annee` = $anneeAcademique  and `ID_FILIERE` = '$anneeEtude'  ORDER BY nomEtu ASC"));
            $se = $this->convert_array(DB::select("SELECT * FROM `classe` where id_S_Gr_Spe='$semestreEtude';"));
            $tete = $this->convert_array(DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'"));
            $rTaux = $this->convert_array(DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;"));
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];

            //var_dump($UE[0]); exit;
            ob_start();  ?>
            <?php foreach($etudiants as $etudiant){?>
            <?php $id_etud=$etudiant['Matricule'];?>
            <page backtop="15mm" backbottom="2mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:100%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                           <!--<td style="width: 10%"><img style="width: 100; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:72%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['Ecol_Ufr']) ?><br>
                                ----*----*----*---*---<br>
                                <b style="color:RED"><?php echo $tete[0]['libelleGrade'] ?> en <?php echo $tete[0]['libelleSpe'] ?></b><br>
                                ----*----*----*---*---<br><br><br>
                            </td>-->
                            <!--<td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>-->

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <!--<div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">
                            <b style="color: red"> Rattr. = Rattrapage - Repr. = Reprise - Pond. = Pondération - Moy. = Moyenne</b><br>
                            Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>-->
                </page_footer>
                <div style="padding-top: 82px; font-size: 12px; text-align: center;">
                    <table style="width:100%;margin:auto" cellpadding="0" cellspacing="0" border="0">

                        <tr style="text-align: center;">
                            <td style="vertical-align: top; text-align:center"><img style="width: 50px; height: 50px" src=<?php
                                                                                                                                    clearstatcache();
                                                                                                                                    $filename = './storage/assets/img/etudiants/'.$etudiant['photo'];
                                                                                                                                    $check =  @fopen($filename, 'r');
                                                                                                                                    if ($etudiant['photo'] && Storage::exists('public/assets/img/etudiants/'.$etudiant['photo'])) {
                                                                                                                                        echo $filename;
                                                                                                                                    } else {
                                                                                                                                        $titre = '';
                                                                                                                                        if ($etudiant['sexeEtu'] == 'F') {
                                                                                                                                            $titre = 'Mlle';
                                                                                                                                        } else {
                                                                                                                                            $titre = 'M';
                                                                                                                                        }
                                                                                                                                        echo './storage/assets/img/etudiants/' . $titre . ".png";
                                                                                                                                    }
                                                                                                                                    ?>> </td>

                            <td style="vertical-align: middle; text-align: center">
                                <?php $titre = '';
                                if ($etudiant['sexeEtu'] == 'F') {
                                    $titre = 'Mme/Mlle';
                                } else {
                                    $titre = 'M.';
                                }
                                echo 'Relevé de notes de ' . $titre . '  <b style=\'text-transform: uppercase;font-size: 14px;\'>' . $etudiant['nomEtu'] . '</b>   <b style=\'font-size: 14px;\'>' . $etudiant['prenomsEtu'] . ' </b>(' . $etudiant['Matricule'] . ') <br>né(e) le ' . $etudiant['dateNaisEtu'] . ' &agrave;  ' . $etudiant['lieuNaisEtu'] . ';inscrit(e) en ' . $etudiant['ID_FILIERE'] . ' <br>(Semestre: <b>' . $se[0]['Nom_Classe'] . '</b>) pour le compte de l\'année :<b> ' . $etudiant['ANNEE_ACAD'] . '</b>' ?>

                            </td>

                        </tr>

                    </table>

                </div>
                <div style="margin-left: 54px; padding-top: 20px">
                    <?php echo 'N°............. /ENS/UAC/SGE/SS'; ?> <br><br>
                    <table width="100%" border="1" cellspacing="0" cellpadding="0" style="font-family:Courier;">
                        <tbody>
                            <tr bgcolor="#6495ed" style="text-align: center;">
                                <th width="5%" scope="col" valign="center">CODE</th>
                                <th width="50%" scope="col" valign="center">ECUE - UE </th>
                                <th width="8%" scope="col" valign="center">Note</th>
                                <th width="8%" scope="col" valign="center">Rattr.</th>
                                <th width="8%" scope="col" valign="center">Repr.</th>
                                <th width="9%" scope="col" valign="center">Pond.</th>
                                <th width="10%" scope="col" valign="center">Moy.</th>
                                <th width="5%" scope="col" valign="center">Crédit</th>
                                <th width="5%" scope="col" valign="center">Resultat</th>
                                <th width="5%" scope="col" valign="center">Appréciation</th>
                            </tr>
                            <?php $tCredit = 0;
                            $moyGene = 0.0;
                            $tValide = 0;
                            $nV = 0;
                            $pedagogique=0;
                            for ($i = 0; $i < count($UE); $i++) {
                                $rattrapages = 0;
                                $noteRatr = 0;
                                $tCredit += $UE[$i]['Credit'];
                                $ue_num = $UE[$i]['Id_Attr_ue_Sem'];
                                $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ecue_ue.pedagogique,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,Seuil_Ecu,Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND attr_ue_classe.id_Annee_Acad=$anneeAcademique AND attr_ue_classe.id_S_Gr_Spe=$semestreEtude ORDER BY libelleEcu ASC"));
                            ?>


                                <?php $moyenne = 0.0;
                                $mecue = 0.0;
                                $moyenneReprise = 0.0;
                                for ($ii = 0; $ii < count($rc); $ii++) {
                                    $aColsEval = [];

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
                                    $aColsEval[$rc[$ii]['ecue']] = array("dev" => $nDev, "part" => $nPart, 'ponderation' => $rc[$ii]["Ponderation"], 'pdev' => $rc[$ii]["PoidsDev"], 'ppart' => $rc[$ii]["PoidsPart"]);
                                    // calc moyenne

                                    foreach ($aColsEval as $key => $col) { //var_dump($col);
                                        //DEVOIRS
                                        $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$id_etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='DEV' and numeroInscription = '$id_etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $md = floatval($r[0]['MD']);

                                        //PARTIEL
                                        $listeN = "SELECT e.codTypEval typeEval, o.numeroInscription matricule, e.numeroEval eval, o.Note  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$id_etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='PART' and numeroInscription = '$id_etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $mp = floatval($r[0]['MD']);
                                        $listeN = "SELECT AVG(o.Note) MD FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='RATTR' and numeroInscription = '$id_etud' and Id_Attr_ue_Sem='$ue_num';";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $mR = floatval($r[0]['MD']);
                                        $noteRatr += $mR;
                                        $listeN = "SELECT o.Note MD, o.created_at moment  FROM `evaluation` e, obtenir o WHERE e.numeroEval=o.numeroEval and `codeEcu` = '$key' and e.codTypEval='REPR' and numeroInscription = '$id_etud' and Id_Attr_ue_Sem='$ue_num' ORDER BY `moment` DESC LIMIT 1;";
                                        $r = $this->convert_array(DB::select($listeN));
                                        $mRepr = count($r) == 0 ? 0 : floatval($r[0]['MD']);
                                        $mecue = floatval((int)$md === 0 && $col['dev']==0   ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                        if($rc[$ii]['pedagogique']==1 && $mecue<$rc[$ii]['Seuil_Ecu']) $pedagogique=1;
                                        $mNorm = $mecue;
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
                                ?>
                                    <tr style="text-align: center;">
                                        <td style="font-size: 10px"><?php echo $rc[$ii]['ecue'] ?></td>
                                        <td style="font-size: 10px; width: 170px"><i><?php echo $rc[$ii]['libelleEcu'] ?></i></td>
                                        <td style="text-align: center;width: 5px;font-size: 10px"><?php echo number_format($mNorm, 2, ',', ' ') ?></td>
                                        <td style="text-align: center;width: 20px;font-size: 10px"><?php echo $mR == 0 ? "-" :  number_format($mR, 2, ',', ' ') ?></td>
                                        <td style="text-align: center;width: 20px;font-size: 10px"><?php echo $mRepr == 0 ? "-" :  number_format($mRepr, 2, ',', ' ') ?></td>
                                        <td style="text-align: center;width: 20px;font-size: 10px"><?php echo 100 * $rc[$ii]['Ponderation'] . "%" ?></td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td style="font-size: 10px"><?php if ($mecue >= 18) {
                                                                        echo "Excellent";
                                                                    } elseif ($mecue >= 16 && $mecue < 18) {
                                                                        echo "Très - Bien";
                                                                    } elseif ($mecue >= 14 && $mecue < 16) {
                                                                        echo "Bien";
                                                                    } elseif ($mecue >= 12 && $mecue < 14) {
                                                                        echo " Assez - Bien";
                                                                    } elseif ($mecue >= 10 && $mecue < 12) {
                                                                        echo "Passable";
                                                                    } elseif ($mecue < 10) {
                                                                        echo " Insuffisant";
                                                                    }  ?> </td>
                                    </tr>
                                <?php }
                                $moyGene += $moyenne * $UE[$i]['Credit'];
                                $tValide += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? $UE[$i]['Credit'] : 0;
                                $nV += round($moyenne, 3) >= $UE[$i]['Seuil_Ue'] ? 1 : 0;


                                ?>

                                <tr bgcolor="<?php if (round($moyenne, 3) >= $UE[$i]['Seuil_Ue']) {
                                                    echo "#0B792F";
                                                } else {
                                                    echo "red";
                                                } ?>">
                                    <td style="color: #FFFFFF; text-align: center;font-size: 12px"><b><?php echo $UE[$i]['ID_UE'] ?></b></td>
                                    <td colspan="5" style="color: #FFFFFF;font-size: 10px; width: 300px" ><b><?php echo $UE[$i]['libelleUe'] ?></b></td>
                <td style=" color: #FFFFFF; text-align: center"><b><?php echo number_format($moyenne, 2, ',', ' ') ?></b></td>
                                    <td style="color: #FFFFFF; text-align: center" ><b><?php echo $UE[$i]['Credit'] ?></b></td>
                <?php $rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE` from recap_ue WHERE `MATRICULE`='$id_etud' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='" . $ue_num . "';";
                                // echo $rrrrrr;
                                //   exit();
                                $dater = $this->convert_array(DB::select($rrrrrr));
                                $knbr = count($dater);
                                //$valer = $knbr != 0 ? $dater[0]["Date_Val"] : "";
                ?>

                <td style=" color: #FFFFFF; text-align: center"><?php if (round($moyenne,3) >= $UE[$i]['Seuil_Ue']) {
                                                                        echo "<b>V</b>";
                                                                    } else {
                                                                        echo "<b>NV</b>";
                                                                    }  ?></td>
                <td style=" color: #FFFFFF; text-align: center;font-size: 10px"><b>
                                            <?php if (round($moyenne,3) >= 18) {
                                                echo "Excellent";
                                            } elseif (round($moyenne,3) >= 16 && round($moyenne,3) < 18) {
                                                echo "Très - Bien";
                                            } elseif (round($moyenne,3) >= 14 && round($moyenne,3) < 16) {
                                                echo "Bien";
                                            } elseif (round($moyenne,3) >= 12 && round($moyenne,3) < 14) {
                                                echo " Assez - Bien";
                                            } elseif (round($moyenne,3) >= 10 && round($moyenne,3) < 12) {
                                                echo "Passable";
                                            } elseif (round($moyenne,3) < 10) {
                                                echo " Insuffisant";
                                            }  ?>
                                        </b></td>
                                </tr>


                            <?php  } ?>
                        </tbody>
                    </table>

                    <div style="padding-left: 2px; margin: auto; padding-top: 8px;font-size: 10px;">
                        <table width="100%" border="1" cellspacing="2" cellpadding="2">
                            <tbody>
                                <tr bgcolor="#6495ed">
                                    <td colspan="6" style="text-align:center;"><b>Recapitulatif du Semestre</b></td>
                                </tr>
                                <tr>
                                    <td>Nombre d'UE Validées</td>
                                    <td style="text-align:center;"><b><?php echo $nV . '  sur  ' . count($UE); ?></b></td>
                                    <td>Crédits Capitalisés</td>
                                    <td style="text-align:center;"><b><?php echo $tValide . '  sur  ' . $tCredit; ?></b></td>
                                    <td>Pourcentage crédits Capitalisé</td>
                                    <td style="text-align:center;"><b><?php if(count($UE)!==0){$percentUE = 100 * $nV / count($UE);
                                                                        echo number_format($percentUE, 2, ',', ' ');}else{abort(500, 'Aucune UE trouvée');} ?>%</b></td>


                                </tr>
                                <tr>
                                    <td>Moyenne du Semestre</td>
                                    <td style="text-align:center;"><b> <?php echo $tCredit === 0 ? 0 : number_format($moyGene / $tCredit, 2, ',', ' '); ?></b></td>
                                    <td>Appréciation du Semestre</td>
                                    <td style="text-align:center;"> <b><?php //echo $tCredit===0?0:number_format($moyGene/$tCredit,2,',',' ');
                                                                        if ($tCredit!==0) {
                                                                            if ($moyGene / $tCredit >= 18) {
                                                                                echo "Excellent";
                                                                            } elseif ($moyGene / $tCredit >= 16 && $moyGene / $tCredit < 18) {
                                                                                echo "Très - Bien";
                                                                            } elseif ($moyGene / $tCredit >= 14 && $moyGene / $tCredit < 16) {
                                                                                echo " Bien";
                                                                            } elseif ($moyGene / $tCredit >= 12 && $moyGene / $tCredit < 14) {
                                                                                echo " Assez - Bien";
                                                                            } elseif ($moyGene / $tCredit >= 10 && $moyGene / $tCredit < 12) {
                                                                                echo "Passable";
                                                                            } elseif ($moyGene / $tCredit == 10) {
                                                                                echo "Passable";
                                                                            } elseif ($moyGene / $tCredit < 10) {
                                                                                echo " insuffisant";
                                                                            }
                                                                        }
                                                                        ?></b></td>
                                    <td>Décision du Jury</td>
                                    <td style="width:150px;text-align:center;color: white;background-color:<?php if ($percentUE >= $taux && $percentUE < 100) {
                                                                                                                echo "#ff4500";
                                                                                                            } else {
                                                                                                                if ($percentUE < $taux) {
                                                                                                                    echo "red";
                                                                                                                } else {
                                                                                                                    echo "#0B792F";
                                                                                                                }
                                                                                                            } ?> "><?php if ($percentUE >= $taux && $percentUE < 100) {
                                                                                                                        echo "<b>Validé avec Reprise </b>";
                                                                                                                        $dejury = 'Validé avec Reprise';
                                                                                                                    } else {
                                                                                                                        if ($percentUE < $taux) {
                                                                                                                            echo "<b>Non Validé</b>";
                                                                                                                            $dejury = 'Non Validé';
                                                                                                                        } else {
                                                                                                                            echo "<b>Validé</b>";
                                                                                                                            $dejury = 'Validé';
                                                                                                                        }
                                                                                                                    } ?></td>

                                </tr>


                            </tbody>
                        </table>

                    </div>
                    <?php
                    $resultat=$this->convert_array(DB::select("SELECT * FROM recap_annuel WHERE MATRICULE='$id_etud' AND Code_Annee_Etude='$anneeEtude' AND id_Annee_Acad=$anneeAcademique;"));
                    $UE1 = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;"));
                    $tCredit1=0;
                    for ($i = 0; $i < count($UE1); $i++) {
                        $tCredit1+=$UE1[$i]['Credit'];
                    }
                    if(substr(SemestreEtude::find($semestreEtude)->semestre->Lib_Semestre,-1,1)%2==0){?>
                        <div style="padding-top: 10px; padding-left: 130px;">
                            <table width="100%" style="margin-left  : auto;" border="1" cellspacing="2" cellpadding="2">
                                <tbody>
                                    <tr bgcolor="#6495ed">
                                        <td colspan="4" style="text-align:center;"><b>Récapitulatif de l'Année Académique</b></td>
                                    </tr>
                                    <tr>
                                        <td>Nombre d'UE Validées</td>
                                        <td style="text-align:center;"><b><?php echo $resultat[0]['Nbre_UE_Val'] . '  sur  ' . count($UE1); ?></b></td>
                                        <td>Crédits Capitalisés</td>
                                        <td style="text-align:center;"><b><?php echo $resultat[0]['Nbre_Cr_Capi'] . '  sur  ' . $tCredit1; ?></b></td>
                                    </tr>

                                    <tr>
                                        <td>Pourcentage crédits Capitalisé</td>
                                        <td style="text-align:center;"><b><?php echo number_format($resultat[0]['Ptage_Obt'], 2, ',', ' '); ?>%</b></td>
                                        <td>Moyenne Annuelle</td>
                                        <td style="text-align:center;"><b> <?php echo number_format($resultat[0]['MoyenneSem'], 2, ',', ' '); ?></b></td>

                                    </tr>

                                    <tr>
                                        <td>Appréciation de l'Année</td>
                                        <td style="text-align:center;"> <b><?php //echo $tCredit===0?0:number_format($moyGene/$tCredit,2,',',' ');
                                                                            if ($resultat[0]['MoyenneSem'] >= 18) {
                                                                                echo "Excellent";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 16 && $resultat[0]['MoyenneSem'] < 18) {
                                                                                echo "Très - Bien";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 14 && $resultat[0]['MoyenneSem'] < 16) {
                                                                                echo " Bien";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 12 && $resultat[0]['MoyenneSem'] < 14) {
                                                                                echo " Assez - Bien";
                                                                            } elseif ($resultat[0]['MoyenneSem'] >= 10 && $resultat[0]['MoyenneSem'] < 12) {
                                                                                echo "Passable";
                                                                            } elseif ($resultat[0]['MoyenneSem'] == 10) {
                                                                                echo "Passable";
                                                                            } elseif ($resultat[0]['MoyenneSem'] < 10) {
                                                                                echo " Insuffisant";
                                                                            }
                                                                            ?></b></td>
                                        <td>Décision du Jury</td>
                                        <td style="text-align:center;color: white;background-color:<?php if ($resultat[0]['Ptage_Obt'] >= $taux && $resultat[0]['Ptage_Obt'] < 100) {
                                                                                                        echo "#ff4500";
                                                                                                    } else {
                                                                                                        if ($resultat[0]['Ptage_Obt'] < $taux) {
                                                                                                            echo "red";
                                                                                                        } else {
                                                                                                            echo "#0B792F";
                                                                                                        }
                                                                                                    } ?>"><?php if ($resultat[0]['Ptage_Obt'] >= $taux && $resultat[0]['Ptage_Obt'] < 100) {
                                                                                                                echo "<b>Validé avec Reprise </b>";
                                                                                                            } else {
                                                                                                                if ($resultat[0]['Ptage_Obt'] < $taux) {
                                                                                                                    echo "<b>Non Validé</b>";
                                                                                                                } else {
                                                                                                                    echo "<b>Validé</b>";
                                                                                                                }
                                                                                                            } ?></td>

                                    </tr>

                                </tbody>
                            </table>

                        </div>
                    <?php } ?>
                    <div style="display:inherit">
                        <table style="width:80%;" cellpadding="0" cellspacing="0" border="">

                            <tr>
                                <td style="text-align:center; width: 50%; font-size: 12px">
                                    <?php
                                    // $qr = new qrcode();
                                    // $qr = new qrcode('qrcode');
                                    $poutge = 100 * $nV / count($UE);
                                    $titre = '';
                                    if ($etudiant['sexeEtu'] == 'F') {
                                        $titre = 'Mme/Mlle';
                                    } else {
                                        $titre = 'M.';
                                    }
                                    $mot =  'Relevé de notes de ' . $titre . '  ' . $etudiant['nomEtu'] . ' ' . $etudiant['prenomsEtu'] . ' (' . $etudiant['Matricule'] . ') inscrit(e) en ' . $etudiant['ID_FILIERE'] . '(Semestre: ' . $se[0]['Nom_Classe'] . ') pour le compte de l\'année : ' . $etudiant['ANNEE_ACAD'] . '   Nombre d\'UE Validées :' . $nV . '  sur  ' . count($UE) . ' -- Crédits Capitalisés: ' . $tValide . '  sur  ' . $tCredit . '-- Pourcentage crédits Capitalisé: ' . number_format($poutge, 2, ',', ' ') . '%  -- Décision du Jury: ' . $dejury . '  Merci ---@@LMD Web Solutions@@---';
                                   // $qr->text($mot);
                                    // echo "<img src='".$qr->text($mot)."' border='0' />";
                                    ?>
                                </td>
                                <td style="text-align:center; width: 50%; font-size: 12px">
                                    <br>Fait à <?=$univ[0]['ville']?>; le <?php echo ' ' . date('d/m/Y'); ?><br><br>
                                    Le Directeur Adjoint,<br><br><br><br>
                                    <br><br><u><b><?php echo $univ[0]['directeurAdjoint']?> </b></u>
                                </td>
                            </tr>

                        </table>
                    </div>

                </div>
            </page>
            <?php }?>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->pdf->SetTitle('Releve_de_note_ ' . $etudiant['Matricule'] . '_' . $se[0]['Nom_Classe'] . '_' . $etudiant['ANNEE_ACAD']);
                $objPDF->Output('Releve_de_note_ ' . $etudiant['Matricule'] . '_' . $se[0]['Nom_Classe'] . '_' . $etudiant['ANNEE_ACAD'] . '.pdf');
                // $objPDF->Output('MonPdf_ '.$dateNow.'.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du document.');
            }
        }
        if (request('btn')=='Imprimer relevé annuel') {
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            if(empty(request('semestreEtude')) || empty(request('anneeEtude')) || empty(request('anneeAcademique')) || empty(request('etud')))
                return back()->with('echec','Renseigner tous les champs s\'il vous plaît !');
            $UE = $this->convert_array(DB::select("SELECT Id_Attr_ue_Sem,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas,annee_etude_semestre.Code_Annee_Etude ,Nom_Classe,attr_ue_classe.`codeUe` as ID_UE ,libelleUe,attr_ue_classe.`Id_Cat_Ue` as cat ,Lib_Cat_Ue,attr_ue_classe.`idImportance` as import ,libelleImp,`Seuil_Ue`,`Credit` FROM `attr_ue_classe`,anneeacademique,classe,ue,categorie_ue,importance,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe` AND attr_ue_classe.`Id_Cat_Ue`=categorie_ue.`Id_Cat_Ue` AND attr_ue_classe.`idImportance`=importance.`idImportance` AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad='$anneeAcademique' ORDER BY Nom_Classe,libelleUe ASC;"));
            $rTaux = $this->convert_array(DB::select("SELECT `ANNEE_ACAD`, `Ptge_Validation` pourcent,Moyenne,MoyenneUE FROM anneeacademique WHERE `id_Annee_Acad`='$anneeAcademique' LIMIT 1;"));
            $tete = $this->convert_array(DB::select("SELECT annee_etude_semestre.id_S_Gr_Spe, Nom_Classe,classe.numeroSemestre as seclas,Lib_Semestre,classe.codeSpe as speclas,libelleSpe,classe.codeGrade as graclas,libelleGrade,specialite.codeMention as cde_Mention,libelleMention,mention.`Id_domaine` as cde_Domaine,lib_domaine,Libelle_AnEtude  FROM  classe ,semestre, grade, specialite,`mention`,domaine,annee_etude_semestre,annee_etude WHERE classe.`numeroSemestre` = semestre.numeroSemestre AND classe.`codeSpe` = specialite.`codeSpe`  AND classe.`codeGrade` = grade.codeGrade AND  specialite.`codeMention`=mention.`codeMention` AND mention.`Id_domaine`=domaine.`Id_domaine` AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'"));
            $etudiant = $this->convert_array(DB::select("SELECT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,photo,sexeEtu,inscriptions.`Id_Annee` as annee,ANNEE_ACAD,`ID_FILIERE`,dateNaisEtu,lieuNaisEtu FROM `inscriptions`,etudiant,anneeacademique,annee_etude WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and inscriptions.`MATRICULE`= '$etud'  and `Id_Annee` = $anneeAcademique  and `ID_FILIERE` = '$anneeEtude'  "));
            $classe = $UE[0]['Nom_Classe'];
            $taux = count($rTaux) === 0 ? 0 : 100 * $rTaux[0]['pourcent'];
            $taux_Moy = count($rTaux) === 0 ? 0 : $rTaux[0]['Moyenne'];
            $taux_MoyUE = count($rTaux) === 0 ? 0 : $rTaux[0]['MoyenneUE'];
            // echo $taux_Moy.'-------'.$taux_MoyUE;

            ob_start();  ?>
            <page backtop="35mm" backbottom="10mm" backleft="2mm" backright="2mm">
            <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <!--<td style="width: 10%"><img style="width: 100; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:75%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['Ecol_Ufr']) ?><br>
                                ----*----*----*---*---<br>
                                <b style="color:RED"><?php echo $tete[0]['libelleGrade'] ?> en <?php echo $tete[0]['libelleSpe'] ?></b><br>
                                ----*----*----*---*---<br><br><br>
                            </td>-->
                            <!--<td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>-->

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <!--<div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">
                            Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>-->
                </page_footer>
                <div style="padding-top: 5px; font-size: 14px; text-align: center">
                    <table cellpadding="0" cellspacing="0" border="0" style="margin: auto;">

                        <tr>
                            <td style="vertical-align: top; text-align:right"><img style="width: 50px; height: 50px" src=<?php
                                                                                                                                    clearstatcache();
                                                                                                                                    $filename = './storage/assets/img/etudiants/'.$etudiant[0]['photo'];
                                                                                                                                    $check =  @fopen($filename, 'r');
                                                                                                                                    if ($etudiant[0]['photo'] && Storage::exists('public/assets/img/etudiants/'.$etudiant[0]['photo'])) {
                                                                                                                                        echo $filename;
                                                                                                                                    } else {
                                                                                                                                        $titre = '';
                                                                                                                                        if ($etudiant[0]['sexeEtu'] == 'F') {
                                                                                                                                            $titre = 'Mlle';
                                                                                                                                        } else {
                                                                                                                                            $titre = 'M';
                                                                                                                                        }
                                                                                                                                        echo './storage/assets/img/etudiants/' . $titre . ".png";
                                                                                                                                    }
                                                                                                                                    ?>> </td>

                            <td style="width: 90%;vertical-align: middle; text-align: center">
                                <?php $titre = '';
                                if ($etudiant[0]['sexeEtu'] == 'F') {
                                    $titre = 'Mme/Mlle';
                                } else {
                                    $titre = 'M.';
                                }
                                echo 'Relevé de notes de ' . $titre . '  <b style=\'text-transform: uppercase;font-size: 14px;\'>' . $etudiant[0]['nomEtu'] . '</b>   <b style=\'font-size: 14px;\'>' . $etudiant[0]['prenomsEtu'] . ' </b>(' . $etudiant[0]['Matricule'] . ') <br>né(e) le ' . Carbon::parse($etudiant[0]['dateNaisEtu'])->format('d/m/Y') . ' &agrave;  ' . $etudiant[0]['lieuNaisEtu'] . ' inscrit(e) en <b>' . $etudiant[0]['ID_FILIERE'] . '</b> pour le compte de l\'année :<b> ' . $etudiant[0]['ANNEE_ACAD'] . '</b>' ?>

                            </td>

                        </tr>

                    </table>
                </div>
                <div style="padding-left: 20px; padding-top: 20px">




                    <?php $tCredit = 0;
                    $pedagogique=0;
                    for ($i = 0; $i < count($UE); $i++) {
                        $tCredit += $UE[$i]['Credit'];
                    }
                    $sql = "SELECT e.MATRICULE matricule, e.nomEtu noms, e.prenomsEtu prenoms FROM `inscriptions` i, etudiant e WHERE e.MATRICULE=i.matricule and i.matricule = '$etud'  and `Id_Annee` = '$anneeAcademique' and `ID_FILIERE` = '$anneeEtude' ORDER BY noms,prenoms ASC;";
                    $listeE = $this->convert_array(DB::select($sql));
                    $sv = $svr = $snv = 0;
                    $resultat = [];
                    for ($e = 0; $e < count($listeE); $e++) {
                        extract($listeE[$e]);
                        $ligne = [];
                        $ligne['matricule'] = $matricule;
                        $ligne['noms'] = $noms;
                        $ligne['prenoms'] = $prenoms;
                        $ligne['tCredit'] = $tCredit;
                        $tValide = 0;
                        $nV = 0;
                        $moyGene = 0;
                        for ($i = 0; $i < count($UE); $i++) {
                            $rattrapages = 0;
                            $noteRatr = 0;
                            $ue_num = $UE[$i]['Id_Attr_ue_Sem'];

                            // $tCredit+=$UE[$i]['Credit'];
                            $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,Seuil_Ecu,pedagogique,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,	Ponderation,PoidsDev,PoidsPart FROM  `attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,annee_etude,annee_etude_semestre WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe and  attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.id_Annee_Acad=$anneeAcademique ORDER BY libelleEcu ASC"));
                            $aColsEval = [];
                            for ($ii = 0; $ii < count($rc); $ii++) {
                                //        SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='".$rc[$ii]['ecue']."' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude=$anneeEtude and evaluation.codTypEval='DEV'
                                $sqlDev = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num  AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='DEV';";
                                $sqlPart = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='PART';";
                                $sqlNRatr = "SELECT COUNT(evaluation.codTypEval) nbre FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND evaluation.codeEcu='" . $rc[$ii]['ecue'] . "' and attr_ecue_ue.Id_Attr_ue_Sem=$ue_num AND annee_etude_semestre.Code_Annee_Etude=annee_etude.Code_Annee_Etude AND annee_etude_semestre.id_S_Gr_Spe=classe.id_S_Gr_Spe AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude'  and evaluation.codTypEval='RATTR';";
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
                                $mecue = floatval((int)$md === 0 && $col['dev']==0  ? $mp : (($md * $col['pdev']) + ($mp * $col['ppart'])));
                                if(AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->pedagogique==1 && $mecue<AttrEcueUe::where('Id_Attr_ue_Sem',$UE[$i]['Id_Attr_ue_Sem'])->where('codeEcu',$key)->first()->Seuil_Ecu) $pedagogique=1;
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
                            $vv = $moyG >= floatval($taux_Moy) && $MoyUE >= floatval($taux_MoyUE) ? 'V' : $oldVV;
                            if ($vv === 'V') {
                                $resultat[$rr]['nV']++;
                                $resultat[$rr]['tValide'] += intval($cred);
                            }
                            $resultat[$rr]['moyenne'][$rrm][2] = $vv;
                            $resultat[$rr]['percentUE'] = 100 * $resultat[$rr]['nV'] / count($UE);
                        }
                    }
                    // var_dump($resultat[0]);
                    // echo $resultat[0]['matricule'];
                    // exit();
                    ?>
                    <?php echo 'N°............. /ENS/UAC/SGE/SS'; ?>
                    <table width="100%" style="margin: auto;" border="1" cellspacing="2" cellpadding="2">
                        <thead>
                            <tr bgcolor="#6495ed">
                                <th width="5%" scope="col">CODE UE </th>
                                <th width="45%" scope="col">LIBELLE UE </th>
                                <th width="10%" scope="col">Moyenne</th>
                                <th width="5%" scope="col">Crédit</th>
                                <th width="5%" scope="col">Resultat</th>
                                <th width="5%" scope="col">Appréciation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            for ($rrm = 0; $rrm < count($resultat[0]['moyenne']); $rrm++) {
                                //  echo ' '.$resultat[0]['moyenne'][$rrm][0]

                            ?>

                                <tr style="background-color:<?php if ($classe == $resultat[0]['moyenne'][$rrm][5]) {
                                                                echo "#DDF2FD";
                                                            } else {
                                                                echo "#07FED5";
                                                            } ?>">
                                    <td width="5%" scope="col"><?php echo $resultat[0]['moyenne'][$rrm][0]; ?> </td>
                                    <td scope="col" style="font-size: 12px; width: 300px"><?php echo $resultat[0]['moyenne'][$rrm][4]; ?></td>
                                    <td width="10%" scope="col" align="center"><?php echo $resultat[0]['moyenne'][$rrm][1] < floatval($resultat[0]['moyenne'][$rrm][6]) && $resultat[0]['moyenne'][$rrm][1] >= floatval($taux_MoyUE) && $moyG >= $taux_Moy ? '<del>' . number_format($resultat[0]['moyenne'][$rrm][1], 2, ',', ' ') . '</del> <br> <b>' . floatval($resultat[0]['moyenne'][$rrm][6]) . '</b>' : number_format($resultat[0]['moyenne'][$rrm][1], 2, ',', ' '); ?></td>
                                    <td width="5%" scope="col" align="center"><?php echo $resultat[0]['moyenne'][$rrm][3]; ?></td>
                                    <td width="5%" scope="col" align="center"><?php echo '<b>' . $resultat[0]['moyenne'][$rrm][2] . '</b>'; ?></td>
                                    <?php //echo '<b>'.$resultat[0]['matricule'].'</b>'.'  ----   '.$anneeEtude.'  ------   ';
                                    ?>

                                    <?php //$rrrrrr = "Select `MATRICULE`, `Code_Annee_Etude`, `Id_Attr_ue_Sem`, `Moyenne_UE`, `Etat_UE`,Date_Val from recap_ue WHERE `MATRICULE`='".$resultat[0]['matricule']."' and `Code_Annee_Etude`='$anneeEtude' and Id_Attr_ue_Sem='".$resultat[0]['UE'][$rrm][0]."';";
                                    //   echo $rrrrrr;
                                    //  exit();
                                    //  $dater = $this->convert_array(DB::select($rrrrrr));
                                    //  $knbr=count($dater);
                                    //  $valer=  $dater[0]["Date_Val"];
                                    ?>
                        <td width=" 5%" scope="col"><?php
                                                    if ($resultat[0]['moyenne'][$rrm][1] >= 18) {
                                                        echo "Excellent";
                                                    } elseif ($resultat[0]['moyenne'][$rrm][1] >= 16 && $resultat[0]['moyenne'][$rrm][1] < 18) {
                                                        echo "Très - Bien";
                                                    } elseif ($resultat[0]['moyenne'][$rrm][1] >= 14 && $resultat[0]['moyenne'][$rrm][1] < 16) {
                                                        echo "Bien";
                                                    } elseif ($resultat[0]['moyenne'][$rrm][1] >= 12 && $resultat[0]['moyenne'][$rrm][1] < 14) {
                                                        echo " Assez - Bien";
                                                    } elseif ($resultat[0]['moyenne'][$rrm][1] >= 10 && $resultat[0]['moyenne'][$rrm][1] < 12) {
                                                        echo "Passable";
                                                    } elseif ($resultat[0]['moyenne'][$rrm][1] == 10) {
                                                        echo "Passable";
                                                    } elseif ($resultat[0]['moyenne'][$rrm][1] < 10) {
                                                        echo " Insuffisant";
                                                    }

                                                    // exit();
                                                    ?></td>
                                </tr>
                            <?php }
                            ?>
                        </tbody>
                    </table>
                    <div style="padding-top: 10px; padding-left: 130px;">
                        <table width="100%" style="margin-left: auto;" border="1" cellspacing="2" cellpadding="2">
                            <tbody>
                                <tr bgcolor="#6495ed">
                                    <td colspan="4" style="text-align:center;"><b>Récapitulatif de l'Année Académique</b></td>
                                </tr>
                                <tr>
                                    <td>Nombre d'UE Validées</td>
                                    <td style="text-align:center;"><b><?php echo $resultat[0]['nV'] . '  sur  ' . count($UE); ?></b></td>
                                    <td>Crédits Capitalisés</td>
                                    <td style="text-align:center;"><b><?php echo $resultat[0]['tValide'] . '  sur  ' . $tCredit; ?></b></td>
                                </tr>

                                <tr>
                                    <td>Pourcentage crédits Capitalisé</td>
                                    <td style="text-align:center;"><b><?php echo number_format($resultat[0]['percentUE'], 2, ',', ' '); ?>%</b></td>
                                    <td>Moyenne Annuelle</td>
                                    <td style="text-align:center;"><b> <?php echo number_format($resultat[0]['moyG'], 2, ',', ' '); ?></b></td>

                                </tr>

                                <tr>
                                    <td>Appréciation de l'Année</td>
                                    <td style="text-align:center;"> <b><?php //echo $tCredit===0?0:number_format($moyGene/$tCredit,2,',',' ');
                                                                        if ($resultat[0]['moyG'] >= 18) {
                                                                            echo "Excellent";
                                                                        } elseif ($resultat[0]['moyG'] >= 16 && $resultat[0]['moyG'] < 18) {
                                                                            echo "Très - Bien";
                                                                        } elseif ($resultat[0]['moyG'] >= 14 && $resultat[0]['moyG'] < 16) {
                                                                            echo " Bien";
                                                                        } elseif ($resultat[0]['moyG'] >= 12 && $resultat[0]['moyG'] < 14) {
                                                                            echo " Assez - Bien";
                                                                        } elseif ($resultat[0]['moyG'] >= 10 && $resultat[0]['moyG'] < 12) {
                                                                            echo "Passable";
                                                                        } elseif ($resultat[0]['moyG'] == 10) {
                                                                            echo "Passable";
                                                                        } elseif ($resultat[0]['moyG'] < 10) {
                                                                            echo " insuffisant";
                                                                        }
                                                                        ?></b></td>
                                    <td>Décision du Jury</td>
                                    <td style="text-align:center;color: white;background-color:<?php if ($resultat[0]['percentUE'] >= $taux && $resultat[0]['percentUE'] < 100) {
                                                                                                    echo "#ff4500";
                                                                                                } else {
                                                                                                    if ($resultat[0]['percentUE'] < $taux) {
                                                                                                        echo "red";
                                                                                                    } else {
                                                                                                        echo "#0B792F";
                                                                                                    }
                                                                                                } ?>"><?php if ($resultat[0]['percentUE'] >= $taux && $resultat[0]['percentUE'] < 100) {
                                                                                                            echo "<b>Validé avec Reprise </b>";
                                                                                                        } else {
                                                                                                            if ($resultat[0]['percentUE'] < $taux) {
                                                                                                                echo "<b>Non Validé</b>";
                                                                                                            } else {
                                                                                                                echo "<b>Validé</b>";
                                                                                                            }
                                                                                                        } ?></td>

                                </tr>

                            </tbody>
                        </table>

                    </div>
                    <div style="padding-top: 40px">
                        <table style="width:80%;" cellpadding="0" cellspacing="0" border="">

                            <tr>
                                <td style="width: 30%"></td>
                                <td style="width:10%;"> </td>
                                <td style="text-align:center; width: 60%; font-size: 14px">
                                    Fait à <?=$univ[0]['ville']?>; le<?php echo ' ' . date('d/m/Y'); ?><br><br>
                                    Le Directeur Académique,<br><br><br>
                                    <u><b><?php echo $univ[0]['directeurAdjoint']?> </b></u>
                                </td>
                            </tr>

                        </table>
                    </div>
                </div>
            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                //.$etudiant[0]['ID_FILIERE'].'</b> pour le compte de l\'année :<b> '.$etudiant[0]['ANNEE_ACAD'].'
                $objPDF->Output('Releve_de_note_ ' . $etudiant[0]['Matricule'] . '_' . $etudiant[0]['ID_FILIERE'] . '_' . $etudiant[0]['ANNEE_ACAD'] . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du document.');
            }
        }
    }
    public function impressionPaiement(){
        $totalFraisGlobal=0;
        $totalPayeGlobal=0;
        $totalResteGlobal=0;
        if (request()->btn=="Imprimer") {
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            ob_start();?>

            <page backtop="45mm" backbottom="20mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 10%"><img style="width: 100; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:75%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                INSTITUT UNIVERSITAIRE SAINTS ADRIEN ET ANSELME<br>
                                ----*----*----*---*---<br>
                                ANNEE:<?php echo AnneeAcademique::find($anneeAcademique)->value('ANNEE_ACAD')?><br>
                                ----*----*----*---*---<br>
                                Récapitulatif total des paiements <?php if (request()->filled(['dateDebut','dateFin'])) {echo("du ".\Carbon\Carbon::parse(request()->dateDebut)->format('d/m/Y') ." au ".\Carbon\Carbon::parse(request()->dateFin)->format('d/m/Y'));}?><br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; ">Fait le <?=now()->format('d/m/Y')?></div>
                            <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['siteWeb'] ?>
                            </div>
                            <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                        </div>
                    </div>
                </page_footer>

                <div style="display: block;align-content: center; margin:auto">

                <?php foreach(AnneeEtude::has('inscriptions')->get() as $anneeEtude) {?>
                    <div style="margin-left: 40px;font-size: 18px;">FILIERE : <?php echo($anneeEtude->Libelle_AnEtude)?></div>
                    <table style="width:100%; margin:auto; font-size: 10px;" border="1px">
                        <thead>
                            <tr style="text-align: center;">
                                <th scope="col"> Matricule</th>
                                <th scope="col">Nom </th>
                                <th scope="col"> Prénoms </th>
                                <th scope="col">Montant à payer </th>
                                <th scope="col">Cumul paiement </th>
                                <th scope="col">Reste à payer </th>

                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $totalFrais=0;
                            $totalPaye=0;
                            $totalReste=0;
                            $inscs=(request()->filled(['dateDebut','dateFin']))
                                    ?Inscription::whereRelation(
                                        'pmts', 'datePaiement', '>=', \Carbon\Carbon::parse(request()->dateDebut)->format('Y-m-d')
                                    )->whereRelation(
                                        'pmts', 'datePaiement', '<=', \Carbon\Carbon::parse(request()->dateFin)->format('Y-m-d')
                                    )
                                    ->where('Id_Annee',request()->anneeAcademique)->where('ID_FILIERE',$anneeEtude->Code_Annee_Etude)->orderBy('ID_FILIERE')->get()
                                    :$anneeEtude->inscriptions->where('Id_Annee',request()->anneeAcademique);

                            foreach ($inscs as $item) {
                                $totalFrais+=$item->frais_paye;
                                $totalPaye+=$item->paiements()->sum('montantPaye');
                                $totalReste+=$item->reste_paye;
                                ?>

                                <tr style="text-align: center;vertical-align: middle;">
                                    <td style="width: 100px;"><?php echo $item->MATRICULE; ?></td>
                                    <td style="width: 100px;"><?php echo $item->etudiant->nomEtu; ?></td>
                                    <td style="width: 100px;"><?php echo $item->etudiant->prenomsEtu; ?></td>
                                    <td style="width: 100px;"><?php echo $item->frais_paye; ?></td>
                                    <td style="width: 100px;"><?php echo $item->paiements()->sum('montantPaye'); ?></td>
                                    <td style="width: 100px;"><?php echo $item->reste_paye; ?></td>



                                </tr>
                                <?php
                                } ?>
                                <?php $totalFraisGlobal+=$totalFrais;
                                $totalPayeGlobal+=$totalPaye;
                                $totalResteGlobal+=$totalReste;?>
                                <tr style="text-align: center;vertical-align: middle;">
                                    <td colspan="3" style="text-align: center;"><span style="font-size: 12px;">TOTAL</span></td>
                                    <td><?php echo $totalFrais?></td>
                                    <td><?php echo $totalPaye?></td>
                                    <td><?php echo $totalReste?></td>
                                </tr>
                                <tr style="text-align: center;vertical-align: middle;">
                                    <td colspan="3" style="text-align: center;"><span style="font-size: 12px;">%</span></td>
                                    <?php
                                        if ($totalFrais!==0) {
                                            $pourcentageTotalPaye=round($totalPaye/$totalFrais*100,2);
                                            $pourcentageTotalReste=round(100-$pourcentageTotalPaye,2);
                                        }
                                    ?>
                                    <td>100</td>
                                    <td><?php echo ($totalFrais!==0)?$pourcentageTotalPaye:'100'?></td>
                                    <td><?php echo ($totalFrais!==0)?$pourcentageTotalReste:'100'?></td>
                                </tr>
                        </tbody>

                    </table>
                    <br>
                    <?php }?>
                    <div style="margin-left: 40px;font-size: 18px;">GLOBAL</div>
                    <table style="width:100%; margin:auto; font-size: 11px;" border="1px">
                        <thead>
                            <tr>
                                <th style="background: black;"></th>
                                <th>Total montant à payer global</th>
                                <th>Total cumul paiement global</th>
                                <th>Total reste à payer global</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="text-align: center;vertical-align: middle;">
                                <td style="width: 155px;">Montant</td>
                                <td style="width: 155px;"><?php echo $totalFraisGlobal?></td>
                                <td style="width: 155px;"><?php echo $totalPayeGlobal?></td>
                                <td style="width: 155px;"><?php echo $totalResteGlobal?></td>
                            </tr>
                            <tr style="text-align: center;vertical-align: middle;">
                                <td>%</td>
                                <?php
                                        if ($totalFrais!==0) {
                                            $pourcentageTotalPaye=round($totalPayeGlobal/$totalFraisGlobal*100,2);
                                            $pourcentageTotalReste=round(100-$pourcentageTotalPaye,2);
                                        }
                                    ?>
                                <td>100</td>
                                <td><?php echo ($totalFrais!==0)?$pourcentageTotalPaye:'100'?></td>
                                <td><?php echo ($totalFrais!==0)?$pourcentageTotalReste:'100'?></td>
                            </tr>
                        </tbody>
                    </table>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Paiements_' . $dateNow . '_'.AnneeAcademique::find($anneeAcademique)->value('ANNEE_ACAD') .'.pdf');
            } catch (Exception $e) {
                abort(500, 'erreur lors de la création du pdf !');
            }
        }
            /* return view('imprimerPaiement',['items'=>Inscription::where('Id_Annee',request()->anneeAcademique)->where('ID_FILIERE',request()->anneeEtude)->get()]); */
    }
    public function impressionPrevision(){
        if (request()->btn=="Imprimer") {
            $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
            extract($this->extraireEntrees());
            if (request()->filled(['dateDebut','dateFin'])) {
                $items=Prevision::whereBetween('echeance',[\Carbon\Carbon::parse(request()->dateDebut)->format('Y-m-d'),\Carbon\Carbon::parse(request()->dateFin)->format('Y-m-d')])->get();
            }else
            $items=Prevision::all();
            ob_start(); ?>
            <page backtop="45mm" backbottom="20mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 10%"><img style="width: 100; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:75%; text-align: center; font-size:12px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                INSTITUT UNIVERSITAIRE SAINTS ADRIEN ET ANSELME<br>
                                ----*----*----*---*---<br>
                                Récapitulatif total des prévisions<br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                            <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                            </div>
                            <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                        </div>
                    </div>
                </page_footer>

                <div style="display: block;align-content: center; margin:auto">

                    <table style="width:100%; margin:auto; font-size: 10px;" border="1px">
                        <thead>
                            <tr style="text-align: center;">
                                <th scope="col">Identité</th>
                                <th scope="col">Classe </th>
                                <th scope="col">Montant à payer </th>
                                <th scope="col">Payé </th>
                                <th scope="col">Echéance </th>
                                <th scope="col">Reste à payer </th>

                            </tr>
                        </thead>

                        <?php
                        $totalPrevisions=0;
                        foreach ($items as $item) {
                            $totalPrevisions+=$item->montant_a_payer;
                            ?>

                            <tr>
                                <td style="width: auto;"><?=$item->MATRICULE; ?> / <?=$item->etudiant->fullname()?></td>
                                <td style="width: auto;"><?= $item->ID_FILIERE.' '.$item->anneeacademique->ANNEE_ACAD ?></td>
                                <td style="width: auto;"><?= $item->montant_a_payer; ?></td>
                                <td style="width: auto;"><?= ($item->paye==1)?'Oui':'Non'; ?></td>
                                <td style="width: auto;"><?= $item->echeance; ?></td>
                                <td style="width: auto;"><?= $item->reste_a_payer; ?></td>



                            </tr>
                        <?php   } ?>

                    </table>
                    <br>
                    <div style="text-align: center;">
                            Total montant des prévisions : <span><?php echo $totalPrevisions?></span><br>
                    </div>

                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('P', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Prévisions_' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'erreur lors de la création du pdf !');
            }
        }
    }
    public function impressionEvaluation()
    {
        $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
        if (request()->btn=="Imprimer") {
            extract($this->extraireEntrees());
            $d1 = \Carbon\Carbon::parse(trim($dateDebut))->format('Y-m-d');
            $d2 = \Carbon\Carbon::parse(trim($dateFin))->format('Y-m-d');
            ob_start(); ?>
            <page backtop="45mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                    <tr>
                        <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:40%; text-align: center; font-size:14px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                            ----*----*----*---*---<br>
                            CALENDRIER DE DEROULEMENT DES COMPOSITIONS<br>
                            ----*----*----*---*---<br><br><br>
                        </td>
                        <td style="width: 10%;vertical-align: middle; text-align: right;"><img style="width: 200px; height: 200px; margin-left: 140px;" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                    </tr>

                </table>
            </page_header>
            <page_footer>
                <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                    <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                    <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                </div>
                <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
            </div>
            </page_footer>
            <!-- j'ai mis la valeur block   -->
            <div style="display: block;width:95%;align-content: center; padding-left: 10px; padding-top: 70px">

            <?php $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,`numeroEval`,evaluation.`dateEval`,`heureDebutEval`,`dureeEval`,`codeGroupe`,evaluation.codTypEval,libTypEval FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND `dateEval` BETWEEN '$d1' AND '$d2' ORDER BY annee_etude.Code_Annee_Etude ASC, `dateEval`,`heureDebutEval` ASC"));
            if (empty($rc)) {
                $message='Aucune évaluation trouvée pour cette période';
                abort(404);
            }
            ?>
            <table style="width:90%" border="1px">
                <thead>
                    <tr style="text-align: center;">
                        <th width="5%" scope="col">Année <br> Académique</th>
                        <th width="5%" scope="col">Année Etude</th>
                        <th width="10%" scope="col"> Semestre</th>
                        <th width="30%" scope="col">UE </th>
                        <th width="30%" scope="col"> ECUE </th>
                        <th width="10%" scope="col">Enseignant <br> Responsable </th>
                        <th width="5%" scope="col">Date et Heure </th>
                        <th width="5%" scope="col">Nature </th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($rc); $i++) { ?>

                        <tr style="vertical-align: middle">
                            <td style=" width: 50px"><?php echo $rc[$i]["ANNEE_ACAD"] ?></td>
                            <td style="font-size: 12px; width: 100px"><?php echo $rc[$i]["Code_Annee_Etude"] . " " . $rc[$i]["codeGroupe"] ?></td>
                            <td style="font-size: 12px; width: 100px"><?php echo $rc[$i]["Nom_Classe"] ?></td>
                            <td style=" width: 200px"><?php echo $rc[$i]["libelleUe"] ?></td>
                            <td style=" width: 200px"><?php echo $rc[$i]["libelleEcu"] ?></td>
                            <td style=" width: 150px"><?php echo $rc[$i]["nomProf"] . " " . $rc[$i]["prenomsProf"] ?></td>
                            <td style=" width: 100px"><?php echo \Carbon\Carbon::parse(trim($rc[$i]["dateEval"]))->format('d/m/Y') . " &agrave; <br>" . $rc[$i]["heureDebutEval"] ?></td>
                            <td style=" width: 70px"><?php echo $rc[$i]["libTypEval"] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>


                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {
                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('L', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Liste des compositions_ ' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
            }
        }
        if (request()->btn=="Imprimer par année d'étude"){
            extract($this->extraireEntrees());
            $d1 = \Carbon\Carbon::parse(trim($dateDebut))->format('Y-m-d');
            $d2 = \Carbon\Carbon::parse(trim($dateFin))->format('Y-m-d');
            $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,`numeroEval`,evaluation.`dateEval`,`heureDebutEval`,`dureeEval`,`codeGroupe`,evaluation.codTypEval,libTypEval FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.`id_Annee_Acad`=$anneeAcademique AND `dateEval` BETWEEN '$d1' AND '$d2' ORDER BY annee_etude.Code_Annee_Etude,Nom_Classe ASC,libelleUe ASC, `dateEval`,`heureDebutEval` ASC"));
            if (empty($rc)) {
                $message='Aucune évaluation trouvée pour cette période';
                abort(404);
            }
            ob_start(); ?>
            <page backtop="45mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:40%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                CALENDRIER DE DEROULEMENT DES COMPOSITIONS<br>
                                ----*----*----*---*---<br>
                                <?=$rc[0]['Nom_Classe'].' '.$rc[0]['ANNEE_ACAD']?><br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 10%;vertical-align: middle; text-align: right"><img style="width: 200px; height: 200px;margin-left: 140px;" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>

                <div style="display: block;  width:100%;align-content: center; padding-left: 20px; padding-top: 70px">


                    <table style="width:100%" border="1px">
                        <thead>
                            <tr style="text-align: center;">
                                <th width="10%" scope="col"> Semestre</th>
                                <th width="30%" scope="col">UE </th>
                                <th width="30%" scope="col"> ECUE </th>
                                <th width="10%" scope="col">Enseignant <br> Responsable </th>
                                <th width="5%" scope="col">Date et Heure </th>
                                <th width="5%" scope="col">Nature </th>
                            </tr>
                        </thead>
                        <tbody style="text-align: center;">
                            <?php for ($i = 0; $i < count($rc); $i++) { ?>

                                <tr style="vertical-align: middle">
                                    <td style="font-size: 12px; width: 100px"><?php echo $rc[$i]["Nom_Classe"] ?></td>
                                    <td style=" width: 200px"><?php echo $rc[$i]["libelleUe"] ?></td>
                                    <td style=" width: 200px"><?php echo $rc[$i]["libelleEcu"] ?></td>
                                    <td style="font-size: 10px;width: 150px"><?php echo $rc[$i]["nomProf"] . " " . $rc[$i]["prenomsProf"] . "  Tel: " . $rc[$i]["telProf"] ?></td>
                                    <td style=" width: 250px"><?php echo \Carbon\Carbon::parse(trim($rc[$i]["dateEval"]))->format('d/m/Y') . " &agrave; <br>" . $rc[$i]["heureDebutEval"] . " (Durée:" . $rc[$i]["dureeEval"] . "h)" ?></td>
                                    <td style=" width: 70px"><?php echo $rc[$i]["libTypEval"] ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>


                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('L', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Impression des évaluations pour la filière_'.$anneeEtude.'_'.$rc[0]['ANNEE_ACAD'].'_' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
            }
        }
    }
    public function impressionCours()
    {
        $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
        if (request()->btn=="Imprimer") {
            extract($this->extraireEntrees());
            $d1 = \Carbon\Carbon::parse(trim($dateDebut))->format('Y-m-d');
            $d2 = \Carbon\Carbon::parse(trim($dateFin))->format('Y-m-d');
            ob_start(); ?>
            <page backtop="45mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 30%"><img style="width: 100px; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:40%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                CALENDRIER DE DEROULEMENT DES COURS<br>
                                ----*----*----*---*---<br><br><br>
                            </td>
                            <td style="width: 32%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>
                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>

                <div style="display: block;  width:95%;align-content: center; padding-left: 10px; padding-top: 10px">

                    <?php
                    $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,`numeroProg`,prog_cours.`datedebut`,prog_cours.`datefin`,prog_cours.`heureDebutCours`,prog_cours.`heureFinCours`,`codeGroupe`,prog_cours.codeSalle as salle,attr_ecue_ue.MasseHoraire as mht,prog_cours.MHoraireProg as mhex FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,prog_cours WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND prog_cours.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND prog_cours.codeEcu=attr_ecue_ue.codeEcu  AND prog_cours.`datedebut` = '$d1' AND  prog_cours.`datefin` = '$d2' ORDER BY annee_etude.Code_Annee_Etude ASC, `datedebut`,`heureDebutCours` ASC"));
                    if (empty($rc)) {
                        $message='Aucun cours trouvé pour cette période';
                        abort(404);
                    }
                    ?>
                    <table style="width:90%" border="1px">
                        <thead>
                            <tr style="text-align: center; background-color: #0a6aa1; color: white">
                                <th width="5%" scope="col">Année <br> Académique</th>
                                <th width="5%" scope="col">Année Etude</th>
                                <th width="10%" scope="col"> Semestre</th>
                                <th width="30%" scope="col">UE </th>
                                <th width="30%" scope="col"> ECUE </th>
                                <th width="10%" scope="col">Enseignant <br> Responsable </th>
                                <th width="5%" scope="col">Période </th>
                                <th width="5%" scope="col">Plage Horaire<br> <b style='font-size: 16px; color: #ffcd70'> Salles</b> </th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $mht = 0;
                            $mhex = 0;
                            $mht = 0;
                            for ($i = 0; $i < count($rc); $i++) { ?>

                                <tr style="vertical-align: middle; font-size: 11px">
                                    <td style=" width: 50px"><?php echo $rc[$i]["ANNEE_ACAD"] ?></td>
                                    <td style="font-size: 10px; width: 50px"><?php echo $rc[$i]["Code_Annee_Etude"] . " " . $rc[$i]["codeGroupe"] ?></td>
                                    <td style="font-size: 12px; width: 100px"><?php echo $rc[$i]["Nom_Classe"] ?></td>
                                    <td style=" width: 200px"><?php echo $rc[$i]["libelleUe"] ?></td>
                                    <td style=" width: 200px"><?php
                                                                //   $mh=DB::select("SELECT SUM(MHoraireProg) as mhp FROM  prog_cours WHERE `Id_Attr_ue_Sem`='".$rc[$i]["nattr"]."' AND `codeEcu`='".$rc[$i]["ecue"]."' ");
                                                                echo $rc[$i]["libelleEcu"] ?></td>
                                    <td style=" width: 150px"><?php echo $rc[$i]["nomProf"] . " " . $rc[$i]["prenomsProf"] ?></td>
                                    <td style=" width: 100px"><?php echo \Carbon\Carbon::parse(trim($rc[$i]["datedebut"]))->format('d/m/Y') . " &agrave; <br>" . \Carbon\Carbon::parse(trim($rc[$i]["datefin"]))->format('d/m/Y') ?></td>
                                    <td style=" width: 70px"><?php echo "de " . $rc[$i]["heureDebutCours"] . " &agrave; <br> " . $rc[$i]["heureFinCours"] . "  en  <b style='font-size: 16px; color: #985f0d'> " . $rc[$i]["salle"] . "</b>" ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>


                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new Html2Pdf('L', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Programmation_cours ' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
            }
        }
        if (request()->btn=="Imprimer par année d'étude"){
            extract($this->extraireEntrees());
            $d1 = \Carbon\Carbon::parse(trim($dateDebut))->format('Y-m-d');
            $d2 = \Carbon\Carbon::parse(trim($dateFin))->format('Y-m-d');
            $rc = $this->convert_array(DB::select("SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,`numeroProg`,prog_cours.`datedebut`,prog_cours.`datefin`,prog_cours.`heureDebutCours`,prog_cours.`heureFinCours`,`codeGroupe`,codeSalle FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,prog_cours WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and  attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND prog_cours.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND prog_cours.codeEcu=attr_ecue_ue.codeEcu  AND prog_cours.`datedebut` = '$d1' AND  prog_cours.`datefin` = '$d2' AND annee_etude_semestre.Code_Annee_Etude='$anneeEtude' AND attr_ue_classe.`id_Annee_Acad`=$anneeAcademique ORDER BY annee_etude.Code_Annee_Etude,Nom_Classe ASC,libelleUe ASC, `datedebut`,`heureDebutCours` ASC"));
            if (empty($rc)) {
                $message='Aucun cours trouvé pour cette période';
                abort(404);
            }
            ob_start(); ?>
            <page backtop="45mm" backbottom="12mm" backleft="2mm" backright="2mm">
                <page_header>
                    <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                        <tr>
                            <td style="width: 17%"><img style="width: 100px; height: 100px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                            <td style="width:70%; text-align: center; font-size:14px; font-weight: bold">
                                REPUBLIQUE DU BENIN<br>
                                -----*-----*-----<br>
                                MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                                -----*-----*-----*-----*----<br>
                                <?php echo strtoupper($univ[0]['nom']) ?><br>
                                -----*-----*-----*-----*----<br>
                                <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                                ----*----*----*---*---<br>
                                CALENDRIER DE DEROULEMENT DES COURS PAR ANNEE ETUDE<br>
                                ----*----*----*---*---<br>
                                Année Académique : <?php echo $rc[0]["ANNEE_ACAD"] ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--@--&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Année Etude : <?php echo $rc[0]["Code_Annee_Etude"] . " " . $rc[0]["codeGroupe"] ?> <br><br>
                            </td>
                            <td style="width: 15%;vertical-align: middle; text-align: right"><img style="width: 100px; height: 100px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                        </tr>

                    </table>

                </page_header>
                <page_footer>
                    <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                        <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                        <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                            <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                        </div>
                        <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                    </div>
                </page_footer>

                <div style="display: block;  width:95%;align-content: center; padding-left: 20px; padding-top: 10px; ">



                    <table style="width:90%;font-family:Courier; " border="0.5px" cellspacing="0">
                        <thead>
                            <tr style="text-align: center;">
                                <th width="10%" scope="col"> Semestre</th>
                                <th width="30%" scope="col">Unité d'Enseignements </th>
                                <th width="30%" scope="col"> Elt. Const. d'Unité d'Enseignement </th>
                                <th width="10%" scope="col">Enseignant <br> Responsable </th>
                                <th width="5%" scope="col">Période </th>
                                <th width="5%" scope="col">Plage Horaire </th>
                                <th width="5%" scope="col">Salle </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < count($rc); $i++) { ?>

                                <tr style="vertical-align: middle;">

                                    <td style="font-size: 12px; width: 100px"><?php echo $rc[$i]["Nom_Classe"] ?></td>
                                    <td style=" width: 200px"><?php echo $rc[$i]["libelleUe"] ?></td>
                                    <td style=" width: 200px"><?php echo $rc[$i]["libelleEcu"] ?></td>
                                    <td style=" width: 150px"><?php echo $rc[$i]["nomProf"] . " " . $rc[$i]["prenomsProf"] ?></td>
                                    <td style=" width: 100px"><?php echo \Carbon\Carbon::parse(trim($rc[$i]["datedebut"]))->format('d/m/Y') . "  &agrave; <br>" . \Carbon\Carbon::parse(trim($rc[$i]["datefin"]))->format('d/m/Y') ?></td>
                                    <td style=" width: 70px"><?php echo  $rc[$i]["heureDebutCours"] . "  &agrave; <br> " . $rc[$i]["heureFinCours"] ?></td>
                                    <td style=" width: 70px"><?php echo  $rc[$i]["codeSalle"]  ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>


                </div>

            </page>
        <?php
            $strHtml = ob_get_clean();
            try {

                $tabMarges = array(10, 10, 10, 10);
                $objPDF = new HTML2PDF('L', 'A4', 'fr');
                $objPDF->pdf->SetDisplayMode('fullpage');
                $objPDF->pdf->SetAuthor('Dean Kong A Siou');
                $objPDF->writeHTML($strHtml);
                $dateNow = new DateTime('now');
                $dateNow = $dateNow->format('Y.m.d.H.i.s');
                $objPDF->Output('Programmation_cours ' . $dateNow . '.pdf');
            } catch (Exception $e) {
                abort(500, 'Erreur lors de la création du pdf !');
            }
        }
    }
    public function impressionRepartir() {
        $univ = $this->convert_array(DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id"));
        extract($this->extraireEntrees());
        $d1 = \Carbon\Carbon::parse(trim($dateDebut))->format('Y-m-d');
        $d2 = \Carbon\Carbon::parse(trim($dateFin))->format('Y-m-d');
        //echo $$anneeAImprimer;
        $l1 = "SELECT DISTINCT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,dateNaisEtu,lieuNaisEtu,sexeEtu,repartir.* FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut,repartir WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE`  and inscriptions.`MATRICULE`=repartir.`Matricule` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and codeSalle='" . $salle . "'  and datedebut='" . $d1 . "'  and datefin='" . $d2 . "'  AND (repartir.annee_etude LIKE 'LP-%-1' OR repartir.annee_etude LIKE '%1%') ORDER BY repartir.annee_etude ASC, nomEtu,prenomsEtu ASC ;";
        $l2 = "SELECT DISTINCT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,dateNaisEtu,lieuNaisEtu,sexeEtu,repartir.* FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut,repartir WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE`  and inscriptions.`MATRICULE`=repartir.`Matricule` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and codeSalle='" . $salle . "'  and datedebut='" . $d1 . "'  and datefin='" . $d2 . "'  AND (repartir.annee_etude LIKE 'LP-%-2'  OR repartir.annee_etude LIKE '%2%') ORDER BY repartir.annee_etude ASC, nomEtu,prenomsEtu ASC ;";
        $l3 = "SELECT DISTINCT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,dateNaisEtu,lieuNaisEtu,sexeEtu,repartir.* FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut,repartir WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE`  and inscriptions.`MATRICULE`=repartir.`Matricule` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and codeSalle='" . $salle . "'  and datedebut='" . $d1 . "'  and datefin='" . $d2 . "'  AND (repartir.annee_etude LIKE 'LP-%-3'  OR repartir.annee_etude LIKE '%3%') ORDER BY repartir.annee_etude ASC, nomEtu,prenomsEtu ASC ;";
        $M1 = "SELECT DISTINCT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,dateNaisEtu,lieuNaisEtu,sexeEtu,repartir.* FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut,repartir WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE`  and inscriptions.`MATRICULE`=repartir.`Matricule` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and codeSalle='" . $salle . "'  and datedebut='" . $d1 . "'  and datefin='" . $d2 . "'  AND (repartir.annee_etude LIKE 'MP-%-1' OR repartir.annee_etude LIKE 'MP1,2-%') ORDER BY repartir.annee_etude ASC, nomEtu,prenomsEtu ASC ;";
        $M2 = "SELECT DISTINCT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,dateNaisEtu,lieuNaisEtu,sexeEtu,repartir.* FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut,repartir WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE`  and inscriptions.`MATRICULE`=repartir.`Matricule` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and codeSalle='" . $salle . "'  and datedebut='" . $d1 . "'  and datefin='" . $d2 . "'  AND repartir.annee_etude LIKE 'MP-%-2' OR repartir.annee_etude LIKE 'CAPES3,4-%') ORDER BY repartir.annee_etude ASC, nomEtu,prenomsEtu ASC ;";
        $PS = "SELECT DISTINCT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,dateNaisEtu,lieuNaisEtu,sexeEtu,repartir.* FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut,repartir WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE`  and inscriptions.`MATRICULE`=repartir.`Matricule` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and codeSalle='" . $salle . "'  and datedebut='" . $d1 . "'  and datefin='" . $d2 . "'  AND repartir.annee_etude LIKE 'PS-%-2' ORDER BY repartir.annee_etude ASC, nomEtu,prenomsEtu ASC ;";
        $MN = "SELECT DISTINCT inscriptions.`MATRICULE` as Matricule, nomEtu,prenomsEtu,dateNaisEtu,lieuNaisEtu,sexeEtu,repartir.* FROM `inscriptions`,etudiant,anneeacademique,annee_etude,statut,repartir WHERE inscriptions.`MATRICULE`=etudiant.`MATRICULE`  and inscriptions.`MATRICULE`=repartir.`Matricule` and inscriptions.`Id_Annee`=anneeacademique.`id_Annee_Acad` and inscriptions.`ID_FILIERE`=annee_etude.Code_Annee_Etude and codeSalle='" . $salle . "'  and datedebut='" . $d1 . "'  and datefin='" . $d2 . "'  AND repartir.annee_etude LIKE 'MN-%-1' ORDER BY repartir.annee_etude ASC, nomEtu,prenomsEtu ASC ;";
        //  echo $$anneeAImprimer;
        //exit();

        $rc = $this->convert_array(DB::select($$anneeAImprimer));
        if (empty($rc)) {
            $message='Aucun évaluation trouvée pour cette période';
            abort(404);
        }
        $nbr = count($rc);
        if ($anneeAImprimer == "l1") {
            $toto = "Licences Premières Années";
        } elseif ($anneeAImprimer == "l2") {
            $toto = "Licences Deuxièmes Années";
        } elseif ($anneeAImprimer == "l3") {
            $toto = "Licences Troisièmes Années";
        } elseif ($anneeAImprimer == "M1") {
            $toto = "Masters Premières Années";
        } elseif ($anneeAImprimer == "M2") {
            $toto = "Masters Deuxièmes Années";
        } elseif ($anneeAImprimer == "PS") {
            $toto = "Programmes Spécials";
        } else {
            $toto = "Mis à Niveau";
        }
        ob_start(); ?>
        <page backtop="45mm" backbottom="12mm" backleft="2mm" backright="2mm">
            <page_header>
                <table style="width:98%;" cellpadding="0" cellspacing="0" border="">

                    <tr>
                        <td style="width: 30%"><img style="width: 200px; height: 200px" src="./storage/assets/img/univ/<?=$univ[0]['logo']?>" /></td>
                        <td style="width:40%; text-align: center; font-size:14px; font-weight: bold">
                            REPUBLIQUE DU BENIN<br>
                            -----*-----*-----<br>
                            MINISTERE DE L’ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE (MESRS)<br>
                            -----*-----*-----*-----*----<br>
                            <?php echo strtoupper($univ[0]['nom']) ?><br>
                            -----*-----*-----*-----*----<br>
                            <?php echo UTF8_encode(strtoupper($univ[0]['Ecol_Ufr'])) ?><br>
                            ----*----*----*---*---<br>
                            LISTE D'EMARGEMENT <br>
                            ----*----*----*---*---<br><br><br>
                        </td>
                        <td style="width: 31%;vertical-align: middle; text-align: right"><img style="width: 200px; height: 200px" src="./storage/assets/img/ufr/<?=$univ[0]['logo_ufr']?>" /></td>

                    </tr>

                </table>
            </page_header>
            <page_footer>
                <div style="width: 100%; margin: 10px 0;font-size: 12px; ">
                    <div style="display: inline; width: 25%; text-align: left; "><?php echo ($univ[0]['Site_web']) ?></div>
                    <div style="display: inline; width: 49%; text-align: center;font-size: 8px">Copyright © <?=now()->format('Y')?> LMD Web Solutions All rights reserved.<br>
                        <?php echo strtoupper($univ[0]['Adres_Post']) ?> Tel: <?php echo strtoupper($univ[0]['Tel']) ?> email: <?php echo ($univ[0]['mail']) . ",    " . $univ[0]['obs'] ?>
                    </div>
                    <div style="display: inline; width: 25%; text-align: right">Page [[page_cu]] sur <b>[[page_nb]]</b></div>
                </div>
            </page_footer>
            <div style="text-align: center; ">
                <u style="line-height: 10px"> <b style="color: red; font-size: 12px">LISTE D'EMARGEMENT DES EVALUATIONS SUR LA PERIODE DU <?php echo "  " . $dateDebut . "  " ?> AU <?php echo "  " . $dateFin . " <br><br> <i style=\" font-size: 20px\">SALLE:  " . $salle . " (" . $toto . ")</i>" ?> </b></u>
            </div>
            <div style="display: block;  width:95%;align-content: center; padding-left: 10px; padding-top: 10px">
                <u><b>Matières:</b></u>....................................................................... <br><br>
                <u><b>NOM ET PRENOMS DES SURVEILLANTS DE SALLE:</b></u>.......................................................................<br><br>
                <table style="width:100%" border="1px">
                    <thead>
                        <tr style="text-align: center;">
                            <th width="10%" scope="col"> Matricule</th>
                            <th width="30%" scope="col">Nom & Prénoms </th>
                            <th width="30%" scope="col"> Date & Lieu de Naissance </th>
                            <th width="10%" scope="col">Sexe </th>
                            <th width="5%" scope="col">Année Etude </th>
                            <th width="5%" scope="col"> Année Académique </th>
                            <th width="5%" scope="col">Emarg. Début </th>
                            <th width="5%" scope="col">Emarg. Fin </th>
                            <th width="5%" scope="col">Obervations </th>
                        </tr>
                    </thead>

                    <?php for ($i = 0; $i < count($rc); $i++) { ?>

                        <tr>
                            <td style="font-size: 10px; height: 20px; width: 80px; vertical-align: middle"><?php echo $rc[$i]["Matricule"] ?></td>
                            <td style=" height: 20px; width: 300px; vertical-align: middle"><?php echo $rc[$i]["nomEtu"] . "   " . $rc[$i]["prenomsEtu"] ?></td>
                            <td style="font-size: 10px; height: 20px; width: 100px; vertical-align: middle"><?php echo \Carbon\Carbon::parse(trim($rc[$i]["dateNaisEtu"]))->format('d/m/Y') . " &agrave; " . $rc[$i]["lieuNaisEtu"] ?></td>
                            <td style=" height: 20px; width: 25px;vertical-align: middle; text-align: center"><?php echo $rc[$i]["sexeEtu"] ?></td>
                            <td style="font-size: 10px; height: 20px; width: 50px; vertical-align: middle; text-align: center"><?php echo $rc[$i]["annee_etude"] . " " . $rc[$i]["grpe_P"] ?></td>
                            <td style=" height: 20px; width: 50px;vertical-align: middle; text-align: center;font-size: 12px"><?php echo $rc[$i]["annee_acad"] ?></td>
                            <td style=" height: 20px; width:10px"></td>
                            <td style=" height: 20px; width:10px"></td>
                            <td style=" height: 20px; width: 50px"></td>

                        </tr>
                    <?php   } ?>

                </table>
                <div style="padding-top: 15px; width: 100%">
                    <i> <b style="font-size: 10px;"><u>Total:</u><?php echo "   " . $nbr . "  "; ?> étutiants répartis dans cette salle .</b></i><br />
                    <i> <b style="font-size: 10px;"><u>NB:</u> le surveillant de salle est prié de barrer les cases des absents après le passage de ladite liste.</b></i><br /><br />
                    <u><b style="text-align: left;">SIGNATURE DES SURVEILLANTS DE SALLE:</b></u>
                    <u><b style="text-align: right;margin-left: 432px;">VISA DU SERVICE DE LA PROGRAMMATION:</b></u>
                </div>

            </div>

        </page>
        <?php
        $strHtml = ob_get_clean();
        try {

            $tabMarges = array(10, 10, 10, 10);
            $objPDF = new HTML2PDF('L', 'A4', 'fr');
            $objPDF->pdf->SetDisplayMode('fullpage');
            $objPDF->pdf->SetAuthor('Dean Kong A Siou');
            $objPDF->writeHTML($strHtml);
            $dateNow = new DateTime('now');
            $dateNow = $dateNow->format('Y.m.d.H.i.s');
            $objPDF->Output('Liste d\'émargement_' . $dateNow . '.pdf');
        } catch (Exception $e) {
            abort(500, 'Erreur lors de la création du pdf !');
        }
    }
}
