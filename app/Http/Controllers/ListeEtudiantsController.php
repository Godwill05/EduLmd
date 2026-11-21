<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spipu\Html2Pdf\Html2Pdf;

class ListeEtudiantsController extends Controller
{
    /**
     * Affiche la liste des étudiants par filière avec filtres.
     */
    public function index(Request $request)
    {
        $anneesAcademiques = AnneeAcademique::orderBy('id_Annee_Acad', 'desc')->get();
        $anneeAcademiqueId = $request->anneeAcademique ?? ($anneesAcademiques->first()->id_Annee_Acad ?? null);

        $filieres = AnneeEtude::whereHas('inscriptions', function ($q) use ($anneeAcademiqueId) {
            if ($anneeAcademiqueId) {
                $q->where('Id_Annee', $anneeAcademiqueId);
            }
        })->orderBy('Libelle_AnEtude')->get();

        // Si aucune filière avec inscription trouvée, on charge toutes les filières
        if ($filieres->isEmpty()) {
            $filieres = AnneeEtude::orderBy('Libelle_AnEtude')->get();
        }

        $codeFiliere = $request->anneeEtude ?? null;
        $inscriptions = collect();
        $selectedFiliere = null;
        $selectedAnnee = AnneeAcademique::find($anneeAcademiqueId);

        $nbTotal = 0;
        $nbHommes = 0;
        $nbFemmes = 0;

        if ($anneeAcademiqueId && $codeFiliere) {
            $selectedFiliere = AnneeEtude::find($codeFiliere);

            $inscriptions = Inscription::where('Id_Annee', $anneeAcademiqueId)
                ->where('ID_FILIERE', $codeFiliere)
                ->with(['etudiant.nationalite', 'statut', 'anneeEtude', 'anneeacademique'])
                ->get()
                ->sortBy(function ($i) {
                    $nom = trim($i->etudiant->nomEtu ?? '');
                    $prenoms = trim($i->etudiant->prenomsEtu ?? '');
                    return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($nom . ' ' . $prenoms));
                })
                ->values();

            $nbTotal = $inscriptions->count();
            $nbHommes = $inscriptions->filter(function ($i) {
                return strtoupper(trim($i->etudiant->sexeEtu ?? '')) === 'M';
            })->count();
            $nbFemmes = $inscriptions->filter(function ($i) {
                return strtoupper(trim($i->etudiant->sexeEtu ?? '')) === 'F';
            })->count();
        }

        return view('listeEtudiantsFiliere', compact(
            'anneesAcademiques',
            'anneeAcademiqueId',
            'filieres',
            'codeFiliere',
            'inscriptions',
            'selectedFiliere',
            'selectedAnnee',
            'nbTotal',
            'nbHommes',
            'nbFemmes'
        ));
    }

    /**
     * Génère l'impression PDF de la liste des étudiants par filière.
     */
    public function imprimerPdf(Request $request)
    {
        $anneeAcademiqueId = $request->anneeAcademique;
        $codeFiliere = $request->anneeEtude;

        if (!$anneeAcademiqueId || !$codeFiliere) {
            return redirect()->back()->with('echec', 'Veuillez sélectionner une année académique et une filière.');
        }

        $anneeModel = AnneeAcademique::find($anneeAcademiqueId);
        $libelleAnneeAcad = $anneeModel ? $anneeModel->ANNEE_ACAD : $anneeAcademiqueId;

        $filiere = AnneeEtude::find($codeFiliere);
        if (!$filiere) {
            return redirect()->back()->with('echec', 'Filière introuvable.');
        }

        $inscriptions = Inscription::where('Id_Annee', $anneeAcademiqueId)
            ->where('ID_FILIERE', $codeFiliere)
            ->with(['etudiant.nationalite', 'statut', 'anneeEtude', 'anneeacademique'])
            ->get()
            ->sortBy(function ($i) {
                $nom = trim($i->etudiant->nomEtu ?? '');
                $prenoms = trim($i->etudiant->prenomsEtu ?? '');
                return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($nom . ' ' . $prenoms));
            })
            ->values();

        if ($inscriptions->isEmpty()) {
            return redirect()->back()->with('echec', 'Aucun étudiant trouvé pour cette filière.');
        }

        $nbTotal = $inscriptions->count();
        $nbHommes = $inscriptions->filter(fn($i) => strtoupper(trim($i->etudiant->sexeEtu ?? '')) === 'M')->count();
        $nbFemmes = $inscriptions->filter(fn($i) => strtoupper(trim($i->etudiant->sexeEtu ?? '')) === 'F')->count();

        $univ = DB::select("SELECT * FROM `ufr`,universites where ufr.Univ=universites.id");
        $univ = json_decode(json_encode($univ), true);
        $logoUniv = './storage/assets/img/univ/' . ($univ[0]['logo'] ?? 'iusaa.png');
        $logoUfr  = './storage/assets/img/ufr/'  . ($univ[0]['logo_ufr'] ?? 'institutuniversitairesaintsadrienetanselme.png');

        try {
            $objPDF = new Html2Pdf('P', 'A4', 'fr', true, 'UTF-8', [8, 8, 8, 8]);
            $objPDF->setTestTdInOnePage(false);

            ob_start(); ?>
            <page backtop="42mm" backbottom="18mm" style="font-family: times;">
                <page_header>
                    <table style="width:100%; border-collapse: collapse; font-family: times;">
                        <tr>
                            <td style="width:12%; text-align: left; vertical-align: middle;">
                                <?php if (file_exists($logoUniv)): ?>
                                    <img src="<?= $logoUniv ?>" style="width:75px; height:75px;" alt="Logo Univ">
                                <?php endif; ?>
                            </td>
                            <td style="width:76%; text-align: center; vertical-align: middle; line-height: 1.25;">
                                <span style="font-size: 10.5pt; font-weight: bold;">RÉPUBLIQUE DU BÉNIN</span><br>
                                <span style="font-size: 9pt; font-weight: bold;">*****</span><br>
                                <span style="font-size: 9pt; font-weight: bold;">Ministère de l’Enseignement Supérieur et de la Recherche Scientifique</span><br>
                                <span style="font-size: 10.5pt; font-weight: bold; color: #2e7d53;">Institut Universitaire Saints Adrien et Anselme (IUSAA)</span><br>
                                <span style="font-size: 10pt; font-weight: bold; color: #111; margin-top: 1mm;">LISTE ALPHABÉTIQUE DES ÉTUDIANTS</span><br>
                                <span style="font-size: 8.5pt; color: #444;">
                                    Année académique : <b><?= htmlspecialchars($libelleAnneeAcad) ?></b> — Filière : <b><?= htmlspecialchars($filiere->Libelle_AnEtude) ?></b>
                                </span>
                            </td>
                            <td style="width:12%; text-align: right; vertical-align: middle;">
                                <?php if (file_exists($logoUfr)): ?>
                                    <img src="<?= $logoUfr ?>" style="width:75px; height:75px;" alt="Logo UFR">
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <hr style="border: none; border-top: 1.5px solid #2e7d53; margin: 2mm 0 0 0;">
                </page_header>

                <page_footer>
                    <hr style="border: none; border-top: 1px solid #ccc; margin: 0 0 2mm 0;">
                    <table style="width:100%; font-size: 8pt; color: #555;">
                        <tr>
                            <td style="width:33%; text-align: left;">Imprimé le <?= date('d/m/Y à H:i') ?></td>
                            <td style="width:34%; text-align: center;">IUSAA — Tél: <?= htmlspecialchars($univ[0]['Tel'] ?? '') ?></td>
                            <td style="width:33%; text-align: right;">Page [[page_cu]] / [[page_nb]]</td>
                        </tr>
                    </table>
                </page_footer>

                <!-- Bandeau récapitulatif de la filière -->
                <table style="width: 100%; border-collapse: collapse; margin-top: 3mm; margin-bottom: 2mm; background-color: #2e7d53;">
                    <tr>
                        <td style="width: 60%; color: #fff; padding: 4px 6px; font-weight: bold; font-size: 9pt;">
                            FILIÈRE : <?= htmlspecialchars($filiere->Libelle_AnEtude) ?>
                        </td>
                        <td style="width: 40%; color: #fff; padding: 4px 6px; text-align: right; font-size: 8.5pt;">
                            Total : <b><?= $nbTotal ?></b> étudiant<?= $nbTotal > 1 ? 's' : '' ?>
                            (<?= $nbHommes ?> M / <?= $nbFemmes ?> F)
                        </td>
                    </tr>
                </table>

                <!-- Tableau alphabétique des étudiants -->
                <table style="width: 100%; border-collapse: collapse; font-size: 8pt;" border="1" bordercolor="#bbb" cellpadding="2">
                    <thead>
                        <tr style="background-color: #f2f2f2; font-weight: bold; text-align: center;">
                            <th style="width: 4%;">N°</th>
                            <th style="width: 12%;">Matricule</th>
                            <th style="width: 32%; text-align: left;">Nom &amp; Prénoms</th>
                            <th style="width: 5%;">Sexe</th>
                            <th style="width: 23%; text-align: left;">Date &amp; Lieu de Naissance</th>
                            <th style="width: 12%;">Nationalité</th>
                            <th style="width: 12%;">Téléphone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $idx = 1;
                        foreach ($inscriptions as $ins):
                            $etu = $ins->etudiant;
                            $nom = $etu ? ($etu->nomEtu . ' ' . $etu->prenomsEtu) : $ins->MATRICULE;
                            $sexe = strtoupper(trim($etu->sexeEtu ?? ''));
                            
                            $dateNais = !empty($etu->dateNaisEtu) ? date('d/m/Y', strtotime($etu->dateNaisEtu)) : '';
                            $lieuNais = trim($etu->lieuNaisEtu ?? '');
                            $naissStr = $dateNais;
                            if ($lieuNais !== '') {
                                $naissStr .= ($naissStr ? ' à ' : '') . $lieuNais;
                            }
                            if ($naissStr === '') {
                                $naissStr = '-';
                            }

                            $nat = $etu?->nationalite?->libelleNationalite ?? $etu?->codeNationalite ?? '-';
                            $tel = trim($etu->telEtu ?? '-') ?: '-';
                        ?>
                        <tr>
                            <td style="width: 4%; text-align: center;"><?= $idx++ ?></td>
                            <td style="width: 12%; text-align: center; font-weight: bold;"><?= htmlspecialchars($ins->MATRICULE) ?></td>
                            <td style="width: 32%; text-align: left;"><?= htmlspecialchars($nom) ?></td>
                            <td style="width: 5%; text-align: center; font-weight: bold; color: <?= $sexe === 'F' ? '#d63384' : '#0d6efd' ?>;">
                                <?= htmlspecialchars($sexe ?: '-') ?>
                            </td>
                            <td style="width: 23%; text-align: left; font-size: 7.5pt;"><?= htmlspecialchars($naissStr) ?></td>
                            <td style="width: 12%; text-align: center; font-size: 7.5pt;"><?= htmlspecialchars($nat) ?></td>
                            <td style="width: 12%; text-align: center;"><?= htmlspecialchars($tel) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Bloc Statistiques Globales en bas -->
                <div style="margin-top: 5mm; margin-bottom: 2mm;">
                    <table style="width: 60%; border-collapse: collapse; font-size: 8pt;" border="1" bordercolor="#ccc" cellpadding="3">
                        <tr style="background-color: #f8f9fa; font-weight: bold;">
                            <td style="width: 40%;">Effectif Total Inscrits</td>
                            <td style="width: 20%; text-align: center;"><?= $nbTotal ?></td>
                            <td style="width: 40%; text-align: center;">100 %</td>
                        </tr>
                        <tr>
                            <td>Garçons (Masculin)</td>
                            <td style="text-align: center; font-weight: bold; color: #0d6efd;"><?= $nbHommes ?></td>
                            <td style="text-align: center;"><?= $nbTotal > 0 ? round(($nbHommes / $nbTotal) * 100, 1) : 0 ?> %</td>
                        </tr>
                        <tr>
                            <td>Filles (Féminin)</td>
                            <td style="text-align: center; font-weight: bold; color: #d63384;"><?= $nbFemmes ?></td>
                            <td style="text-align: center;"><?= $nbTotal > 0 ? round(($nbFemmes / $nbTotal) * 100, 1) : 0 ?> %</td>
                        </tr>
                    </table>
                </div>

            </page>
            <?php
            $strHtml = ob_get_clean();
            $objPDF->writeHTML($strHtml);
            $filename = 'Liste_Etudiants_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filiere->Code_Annee_Etude) . '_' . date('Ymd_His') . '.pdf';
            $content = $objPDF->Output($filename, 'S');

            return response($content, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('echec', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }
}
