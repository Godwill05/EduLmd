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
        $a=explode('-',AnneeAcademique::find($annee)->ANNEE_ACAD);
        $a[1]+=1;
        $a[0]+=1;
        $a=implode('-',$a);
        return AnneeAcademique::where('ANNEE_ACAD',$a)->first()->id_Annee_Acad ?? '';
    }
    public function anneeAcademiquePrevious($annee) {
        $a=explode('-',AnneeAcademique::find($annee)->ANNEE_ACAD);
        $a[0]-=1;
        $a[1]-=1;
        $a=implode('-',$a);
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
        extract(request()->except('_token'));
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
                    <th colspan="2" style="text-align: center;"><?php echo FiliereCpl::find($filiere)->nom.'<br>'.' PROMOTION' . "<br/>" . AnneeAcademique::find($anneeAcademique)->ANNEE_ACAD; ?></th>
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
                        $totalCredit+=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Nbre_Cr_Capi;
                        $ptge+=number_format(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Ptage_Obt,2);
                        $moy+=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->MoyenneSem;
                        if ($anac==$anneeAcademique) {?>
                            <?php if(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury!=='INV'){?>
                                <td class="text-center"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Nbre_Cr_Capi ?? '-'?></td>
                                <td class="text-center"><?=number_format(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Ptage_Obt ?? 0.0,2)?></td>
                                <td class="text-center"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->MoyenneSem ?? '-'?></td>
                            <?php }else{ ?>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                            <?php }?>
                            <td class="text-center"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury ?? '-'?></td>
                        <?php }else{ ?>
                            <?php if(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury!=='INV'){?>
                                <td class="text-center"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Nbre_Cr_Capi ?? '-'?></td>
                                <td class="text-center"><?=number_format(RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->Ptage_Obt ?? 0.0,2)?></td>
                                <td class="text-center"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->MoyenneSem ?? '-'?></td>
                            <?php }else{ ?>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                                <td class="text-center">?</td>
                            <?php }?>
                            <td class="text-center"><?=RecapAnnuel::where('Code_Annee_Etude',$as->Code_Annee_Etude)->where('id_Annee_Acad',$anac)->where('MATRICULE',$inscription->MATRICULE)->first()->DecisionJury ?? '-'?></td>
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
}
