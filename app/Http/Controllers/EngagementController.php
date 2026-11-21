<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Prevision;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spipu\Html2Pdf\Html2Pdf;

class EngagementController extends Controller
{
    /**
     * Page d'accueil / Liste pour la sélection et l'impression des fiches d'engagement.
     */
    public function index(Request $request)
    {
        $annees = AnneeAcademique::orderBy('id_Annee_Acad', 'desc')->get();
        $anneesEtudes = AnneeEtude::orderBy('Libelle_AnEtude')->get();

        $selectedAnnee = $request->get('anneeAcademique', $annees->first()?->id_Annee_Acad);
        $selectedFiliere = $request->get('anneeEtude');
        $search = $request->get('recherche');

        $inscriptions = collect();
        $totalAnnee = 0;

        if ($selectedAnnee) {
            $totalAnnee = Inscription::where('Id_Annee', $selectedAnnee)->count();

            $query = Inscription::with(['etudiant', 'anneeacademique', 'anneeEtude'])
                ->where('Id_Annee', $selectedAnnee);

            if (!empty($selectedFiliere)) {
                $query->where('ID_FILIERE', $selectedFiliere);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('MATRICULE', 'like', "%{$search}%")
                        ->orWhereHas('etudiant', function ($qE) use ($search) {
                            $qE->where('nomEtu', 'like', "%{$search}%")
                                ->orWhere('prenomsEtu', 'like', "%{$search}%");
                        });
                });
            }

            /* La vue comptait les prévisions et sommait les paiements de
               chaque inscription : 2 requêtes par ligne (528 au total).
               Les totaux sont calculés en une requête chacun, via les
               sous-requêtes withCount / withSum. */
            $query->withCount(['previsions as nb_previsions'])
                ->withSum('pmts as cumul_paiements', 'montantPaye');

            $inscriptions = $query->orderBy('ID_FILIERE')->orderBy('MATRICULE')
                ->paginate(50)
                ->withQueryString();
        }

        return view('fichesEngagement', compact('annees', 'anneesEtudes', 'selectedAnnee', 'selectedFiliere', 'search', 'inscriptions', 'totalAnnee'));
    }

    /**
     * Générer le HTML d'une fiche d'engagement pour une inscription.
     * Chaque page A4 contient 2 volets détachables (Établissement & Étudiant)
     * occupant harmonieusement toute la hauteur de la page A4.
     */
    /**
     * Détermine le niveau (1, 2, 3) à partir du code filière.
     * LP1,2-* → 1ère année (3 tranches)
     * LP3,4-* ou LP-3,4-* → 2ème année (4 tranches)
     * LP5,6-* ou LP-5,6-* → 3ème année (3 tranches)
     */
    protected static function detectNiveauFiliere(string $filiere): int
    {
        if (preg_match('/LP[_-]?3[,.]?4/i', $filiere) || preg_match('/LP3,4/i', $filiere) || preg_match('/LP-3,4/i', $filiere)) {
            return 2;
        }
        if (preg_match('/LP[_-]?5[,.]?6/i', $filiere) || preg_match('/LP5,6/i', $filiere) || preg_match('/LP-5,6/i', $filiere)) {
            return 3;
        }
        return 1; // LP1,2-* par défaut
    }

    /**
     * Retourne les tranches par défaut (si aucune prévision en base) selon le niveau et l'année académique.
     * Exemple : pour l'année "2026-2027", startYear = 2026.
     * - 1ère & 3ème année : 3 tranches → inscription / 30 Nov N / 21 Fév N+1
     * - 2ème année       : 4 tranches → 30 Sep N / 30 Nov N / 31 Jan N+1 / 31 Mar N+1
     */
    protected static function getTranchesParDefaut(int $niveau, ?string $anneeAcad): array
    {
        // Extraire l'année de début depuis "2026-2027"
        $startYear = 2026; // fallback
        if ($anneeAcad && preg_match('/(\d{4})-(\d{4})/', $anneeAcad, $m)) {
            $startYear = (int) $m[1];
        }
        $y  = $startYear;
        $y1 = $startYear + 1;

        if ($niveau === 2) {
            // 2ème année : 4 tranches
            return [
                ['label' => 'Tranche 1', 'date' => "30/09/{$y}",  'montant' => null],
                ['label' => 'Tranche 2', 'date' => "30/11/{$y}",  'montant' => null],
                ['label' => 'Tranche 3', 'date' => "31/01/{$y1}", 'montant' => null],
                ['label' => 'Tranche 4', 'date' => "31/03/{$y1}", 'montant' => null],
            ];
        }

        // 1ère et 3ème année : 3 tranches
        return [
            ['label' => 'Tranche 1', 'date' => "30/09/{$y}",  'montant' => null],
            ['label' => 'Tranche 2', 'date' => "30/11/{$y}",  'montant' => null],
            ['label' => 'Tranche 3', 'date' => "21/02/{$y1}", 'montant' => null],
        ];
    }

    public static function buildEngagementPageHtml($inscription, $univ): string
    {
        $matricule = $inscription->MATRICULE;
        $anneeId   = $inscription->Id_Annee;
        $filiere   = $inscription->ID_FILIERE;

        $prevs = Prevision::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->orderBy('echeance')
            ->get();

        $pmts = Paiement::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->orderBy('datePaiement')
            ->get();

        $cumulPaye  = $pmts->sum('montantPaye');
        $fraisTotal = (int) $inscription->frais_paye;

        $anneeAcad      = $inscription->anneeacademique ? $inscription->anneeacademique->ANNEE_ACAD : null;
        $niveau         = self::detectNiveauFiliere($filiere);
        $tranchesDefaut = self::getTranchesParDefaut($niveau, $anneeAcad);

        $nbLignes = $prevs->isNotEmpty() ? $prevs->count() : count($tranchesDefaut);
        if ($nbLignes <= 3) {
            $cellHeight = '11.5mm';
            $tableFontSize = '8.5pt';
            $cellPadding = '4px 3px';
        } elseif ($nbLignes == 4) {
            $cellHeight = '8.5mm';
            $tableFontSize = '8pt';
            $cellPadding = '3px 3px';
        } elseif ($nbLignes <= 6) {
            $cellHeight = '5.8mm';
            $tableFontSize = '7.5pt';
            $cellPadding = '2px 2px';
        } elseif ($nbLignes <= 8) {
            $cellHeight = '4.2mm';
            $tableFontSize = '7pt';
            $cellPadding = '1px 2px';
        } else {
            $cellHeight = '3.5mm';
            $tableFontSize = '6.5pt';
            $cellPadding = '1px 1px';
        }

        ob_start();
        ?>
        <page backtop="3mm" backbottom="3mm" backleft="7mm" backright="7mm">
        <?php
        $renderVolet = function ($titreVolet) use ($univ, $inscription, $prevs, $cumulPaye, $fraisTotal, $anneeAcad, $tranchesDefaut, $cellHeight, $tableFontSize, $cellPadding) {
        ?>
            <!-- ═══ EN-TETE INSTITUTIONNEL ═══ -->
            <table style="width:100%; border-collapse:collapse; margin-bottom:1mm;">
                <tr>
                    <td style="width:12%; text-align:left; vertical-align:middle; padding:0;">
                        <?php if (!empty($univ[0]['logo'])) { ?>
                            <img style="width:44px; height:44px;" src="./storage/assets/img/univ/<?= $univ[0]['logo'] ?>" />
                        <?php } ?>
                    </td>
                    <td style="width:76%; text-align:center; vertical-align:middle; padding:0 4px;">
                        <span style="font-size:7.5pt; font-weight:bold; color:#475569; letter-spacing:0.8px;">R&Eacute;PUBLIQUE DU B&Eacute;NIN &nbsp;&bull;&nbsp; MESRS</span><br>
                        <span style="font-size:10.5pt; font-weight:bold; color:#0f172a;"><?= strtoupper($univ[0]['nom'] ?? 'INSTITUT UNIVERSITAIRE SAINTS ADRIEN ET ANSELME') ?></span><br>
                        <span style="font-size:8.5pt; font-weight:bold; color:#1e40af;">INSTITUT UNIVERSITAIRE SAINTS ADRIEN ET ANSELME (IUSAA)</span>
                    </td>
                    <td style="width:12%; text-align:right; vertical-align:middle; padding:0;">
                        <?php if (!empty($univ[0]['logo_ufr'])) { ?>
                            <img style="width:44px; height:44px;" src="./storage/assets/img/ufr/<?= $univ[0]['logo_ufr'] ?>" />
                        <?php } ?>
                    </td>
                </tr>
            </table>

            <!-- ═══ BADGE TITRE (PLEINE LARGEUR) ═══ -->
            <table style="width:100%; border-collapse:collapse; margin-bottom:1.5mm;">
                <tr>
                    <td style="text-align:center; padding:0;">
                        <span style="font-size:9.5pt; font-weight:bold; background-color:#0f172a; color:#ffffff; padding:2px 18px; border-radius:3px; letter-spacing:0.6px;">
                            FICHE D'ENGAGEMENT FINANCIER &nbsp;&bull;&nbsp; <?= strtoupper($titreVolet) ?>
                        </span>
                    </td>
                </tr>
            </table>

            <!-- ═══ INFOS ÉTUDIANT (CARD BORDS FINS ARRONDIS) ═══ -->
            <div style="border:1px solid #94a3b8; border-radius:6px; background-color:#f8fafc; margin-bottom:1.5mm; padding:0;">
                <table style="width:100%; border-collapse:collapse; font-size:8pt;" cellpadding="3">
                    <tr>
                        <td style="width:24%; border-bottom:1px solid #e2e8f0; padding-left:6px;">
                            <span style="color:#64748b; font-size:7pt;">Matricule :</span>
                            <b style="color:#0f172a; font-size:9pt;"><?= $inscription->MATRICULE ?></b>
                        </td>
                        <td style="width:46%; border-bottom:1px solid #e2e8f0; border-left:1px solid #e2e8f0; padding-left:6px;">
                            <span style="color:#64748b; font-size:7pt;">Nom &amp; Pr&eacute;noms :</span>
                            <b style="color:#0f172a; font-size:8.5pt;"><?= $inscription->etudiant ? $inscription->etudiant->fullname() : 'N/A' ?></b>
                        </td>
                        <td style="width:30%; border-bottom:1px solid #e2e8f0; border-left:1px solid #e2e8f0; text-align:right; padding-right:8px;">
                            <span style="color:#64748b; font-size:7pt;">Ann&eacute;e acad&eacute;mique :</span>
                            <b style="color:#0f172a;"><?= $anneeAcad ?? 'N/A' ?></b>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-left:6px;">
                            <span style="color:#64748b; font-size:7pt;">Fili&egrave;re :</span>
                            <b style="color:#0f172a;"><?= $inscription->anneeEtude ? $inscription->anneeEtude->Libelle_AnEtude : $inscription->ID_FILIERE ?></b>
                            &nbsp;&bull;&nbsp;
                            <span style="color:#64748b; font-size:7pt;">T&eacute;l :</span>
                            <b><?= $inscription->etudiant && !empty($inscription->etudiant->telEtu) ? $inscription->etudiant->telEtu : '—' ?></b>
                        </td>
                        <td style="border-left:1px solid #e2e8f0; text-align:right; padding-right:8px;">
                            <span style="color:#64748b; font-size:7pt;">Scolarit&eacute; :</span>
                            <b style="color:#0f172a; font-size:9pt;"><?= number_format($fraisTotal, 0, ',', ' ') ?> F CFA</b>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #e2e8f0; padding-left:6px;">
                            <span style="color:#64748b; font-size:7pt;">Statut :</span>
                            <b><?= $inscription->codeStatut ?? 'TP' ?></b>
                        </td>
                        <td style="border-top:1px solid #e2e8f0; border-left:1px solid #e2e8f0; text-align:center;">
                            <span style="color:#64748b; font-size:7pt;">Cumul Vers&eacute; :</span>
                            <b style="color:#059669; font-size:8.5pt;"><?= number_format($cumulPaye, 0, ',', ' ') ?> F CFA</b>
                        </td>
                        <td style="border-top:1px solid #e2e8f0; border-left:1px solid #e2e8f0; text-align:right; padding-right:8px;">
                            <span style="color:#64748b; font-size:7pt;">Reste &agrave; payer :</span>
                            <b style="color:#dc2626; font-size:8.5pt;"><?= number_format(max(0, $fraisTotal - $cumulPaye), 0, ',', ' ') ?> F CFA</b>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ═══ TABLEAU TRANCHES & ÉCHÉANCES — CELLULES HAUTES & LIGNES SÉPARATRICES ═══ -->
            <div style="border:1.5px solid #0f172a; border-radius:6px; margin-bottom:1.5mm; padding:0;">
                <table style="width:100%; border-collapse:collapse; font-size:<?= $tableFontSize ?>;">
                    <thead>
                        <tr style="background-color:#0f172a; color:#ffffff; text-align:center; font-weight:bold; font-size:<?= $tableFontSize ?>;">
                            <th style="width:20%; padding:<?= $cellPadding ?>; border-right:1px solid #334155;">Tranche</th>
                            <th style="width:26%; padding:<?= $cellPadding ?>; border-right:1px solid #334155;">Date &Eacute;ch&eacute;ance</th>
                            <th style="width:27%; padding:<?= $cellPadding ?>; border-right:1px solid #334155;">Montant Pr&eacute;vu (F CFA)</th>
                            <th style="width:27%; padding:<?= $cellPadding ?>;">Statut / Observation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($prevs->isNotEmpty()) : ?>
                            <?php foreach ($prevs as $idx => $p) : ?>
                                <tr style="text-align:center; background-color:<?= $idx % 2 === 0 ? '#ffffff' : '#f8fafc' ?>;">
                                    <td style="border-top:1.5px solid #64748b; border-right:1px solid #94a3b8; font-weight:bold; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:middle;">
                                        Tranche <?= $idx + 1 ?>
                                    </td>
                                    <td style="border-top:1.5px solid #64748b; border-right:1px solid #94a3b8; font-weight:bold; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:middle;">
                                        <?= Carbon::parse($p->echeance)->format('d/m/Y') ?>
                                    </td>
                                    <td style="border-top:1.5px solid #64748b; border-right:1px solid #94a3b8; font-weight:bold; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:middle;">
                                        <?= number_format($p->montant_a_payer, 0, ',', ' ') ?>
                                    </td>
                                    <td style="border-top:1.5px solid #64748b; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:middle;">
                                        <?php if ($p->paye) : ?>
                                            <b style="color:#059669;">&#10003; Pay&eacute;</b>
                                        <?php elseif ($p->reste_a_payer < $p->montant_a_payer && $p->reste_a_payer > 0) : ?>
                                            <b style="color:#d97706;">Reste : <?= number_format($p->reste_a_payer, 0, ',', ' ') ?> F</b>
                                        <?php else : ?>
                                            <b style="color:#dc2626;">Non pay&eacute;</b>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php foreach ($tranchesDefaut as $idx => $tr) : ?>
                                <tr style="text-align:center; background-color:<?= $idx % 2 === 0 ? '#ffffff' : '#f8fafc' ?>;">
                                    <td style="border-top:1.5px solid #64748b; border-right:1px solid #94a3b8; font-weight:bold; color:#0f172a; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:middle;">
                                        <?= htmlspecialchars($tr['label']) ?>
                                    </td>
                                    <td style="border-top:1.5px solid #64748b; border-right:1px solid #94a3b8; font-weight:bold; color:#0f172a; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:middle;">
                                        <?= htmlspecialchars($tr['date']) ?>
                                    </td>
                                    <td style="border-top:1.5px solid #64748b; border-right:1px solid #94a3b8; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:bottom; padding-bottom:1.5mm;">
                                        <span style="color:#94a3b8; font-size:7.5pt; letter-spacing:1px;">............................................</span>
                                    </td>
                                    <td style="border-top:1.5px solid #64748b; padding:<?= $cellPadding ?>; height:<?= $cellHeight ?>; vertical-align:bottom; padding-bottom:1.5mm;">
                                        <span style="color:#94a3b8; font-size:7.5pt; letter-spacing:1px;">............................................</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ═══ TEXTE ENGAGEMENT ═══ -->
            <table style="width:100%; border-collapse:collapse; border-left:3px solid #0f172a; background-color:#f1f5f9; margin-bottom:1.5mm;" cellpadding="3">
                <tr>
                    <td style="font-size:6.8pt; line-height:1.3; color:#1e293b; text-align:justify;">
                        <b>ENGAGEMENT FORMEL&nbsp;:</b> Je soussign&eacute;(e), &eacute;tudiant(e) ou parent/tuteur l&eacute;gal, certifie sur l'honneur l'exactitude des renseignements ci-dessus et m'engage formellement et irr&eacute;vocablement &agrave; respecter les dates limites et montants des &eacute;ch&eacute;ances fix&eacute;es pour le r&egrave;glement des frais de scolarit&eacute; de l'ann&eacute;e <b><?= $anneeAcad ?? '' ?></b>. En cas de non-respect, l'&eacute;tudiant(e) sera suspendu(e) des cours et interdit(e) d'examens.
                    </td>
                </tr>
            </table>

            <!-- ═══ BLOC SIGNATURES (DEUX CARDS CÔTE À CÔTE) ═══ -->
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:49%; padding:0;">
                        <div style="border:1px solid #94a3b8; border-radius:6px; padding:4px 8px; height:34px;">
                            <span style="font-size:7.5pt;">Fait le&nbsp;.....................................................</span><br>
                            <b style="font-size:8pt; color:#0f172a;">L'&Eacute;tudiant(e) / Le Parent ou Tuteur l&eacute;gal</b><br>
                            <span style="font-size:6.5pt; color:#64748b;"><i>&laquo;&nbsp;Lu et approuv&eacute;&nbsp;&raquo;</i> (mention manuscrite obligatoire)</span>
                        </div>
                    </td>
                    <td style="width:2%; padding:0;"></td>
                    <td style="width:49%; padding:0;">
                        <div style="border:1px solid #94a3b8; border-radius:6px; padding:4px 8px; height:34px;">
                            <span style="font-size:7.5pt;">Porto-Novo, le&nbsp;............................................</span><br>
                            <b style="font-size:8pt; color:#0f172a;">Pour l'Administration (Visa &amp; Cachet)</b>
                        </div>
                    </td>
                </tr>
            </table>
        <?php
        };

        $renderVolet('Exemplaire Établissement');
        ?>

        <!-- ═══ LIGNE DE DECOUPE CENTRALE ═══ -->
        <table style="width:100%; border-collapse:collapse; margin:2mm 0;">
            <tr>
                <td style="width:28%; border-bottom:1.5px dashed #64748b;"></td>
                <td style="width:44%; text-align:center; font-size:7pt; color:#475569; font-weight:bold; letter-spacing:1px;">
                    - - - - &nbsp;&nbsp; D&Eacute;COUPER SUIVANT LES POINTILL&Eacute;S &nbsp;&nbsp; - - - -
                </td>
                <td style="width:28%; border-bottom:1.5px dashed #64748b;"></td>
            </tr>
        </table>

        <?php
        $renderVolet('Exemplaire Étudiant');
        ?>
        </page>
        <?php
        return ob_get_clean();
    }

    /**
     * Imprimer la fiche d'engagement pour un étudiant spécifique.
     */
    public function imprimer(Request $request, $matricule, $annee, $filiere)
    {
        $univ = (new ImpressionController())->convert_array(DB::select("SELECT * FROM ufr,universites where ufr.Univ=universites.id"));

        $inscription = Inscription::with(['etudiant', 'anneeacademique', 'anneeEtude'])
            ->where('MATRICULE', $matricule)
            ->where('Id_Annee', $annee)
            ->where('ID_FILIERE', $filiere)
            ->firstOrFail();

        $content = self::buildEngagementPageHtml($inscription, $univ);

        try {
            $objPDF = new Html2Pdf('P', 'A4', 'fr');
            $objPDF->pdf->SetDisplayMode('fullpage');
            $objPDF->pdf->SetAuthor('IUSAA LMD');
            $objPDF->writeHTML($content);
            $dateNow = now()->format('Y.m.d.H.i.s');
            $objPDF->Output('Engagement_' . $matricule . '_' . $dateNow . '.pdf');
        } catch (Exception $e) {
            abort(500, 'Erreur lors de la génération du PDF d\'engagement :');
        }
    }

    /**
     * Imprimer en lot les fiches d'engagement pour toute une filière ou TOUS les étudiants de l'année.
     * Ignore totalement la pagination de la vue pour imprimer la totalité des étudiants en base.
     */
    public function imprimerClasse(Request $request)
    {
        $request->validate([
            'anneeAcademique' => 'required',
        ]);

        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $univ = (new ImpressionController())->convert_array(DB::select("SELECT * FROM ufr,universites where ufr.Univ=universites.id"));

        $query = Inscription::with(['etudiant', 'anneeacademique', 'anneeEtude'])
            ->where('Id_Annee', $request->anneeAcademique);

        if ($request->filled('anneeEtude')) {
            $query->where('ID_FILIERE', $request->anneeEtude);
        }

        // Si l'utilisateur a explicitement demandé l'impression de quelques étudiants cochés
        if ($request->input('mode_impression') === 'selection' && $request->filled('selected_students')) {
            $query->whereIn('MATRICULE', $request->selected_students);
        }

        $inscriptions = $query->orderBy('ID_FILIERE')->orderBy('MATRICULE')->get();

        if ($inscriptions->isEmpty()) {
            return back()->with('echec', 'Aucun étudiant trouvé à imprimer.');
        }

        $fullHtml = '';
        foreach ($inscriptions as $ins) {
            $fullHtml .= self::buildEngagementPageHtml($ins, $univ);
        }

        $titrePdf = $request->filled('anneeEtude') ? 'Engagements_' . $request->anneeEtude : 'Engagements_Tous';

        try {
            $objPDF = new Html2Pdf('P', 'A4', 'fr');
            $objPDF->pdf->SetDisplayMode('fullpage');
            $objPDF->pdf->SetAuthor('IUSAA LMD');
            $objPDF->writeHTML($fullHtml);
            $dateNow = now()->format('Y.m.d.H.i.s');
            $objPDF->Output($titrePdf . '_' . $dateNow . '.pdf');
        } catch (Exception $e) {
            abort(500, 'Erreur lors de la génération du lot de fiches d\'engagement :');
        }
    }
}
