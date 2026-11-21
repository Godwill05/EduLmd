<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\Etudiant;
use App\Models\Inscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spipu\Html2Pdf\Html2Pdf;

class AttestationDiplomeController extends Controller
{
    /* ------------------------------------------------------------------
     * Charte graphique (conforme aux modèles officiels Word / PDF)
     * ------------------------------------------------------------------ */
    private const COULEUR_CADRE      = '#2e7d53'; // Bordure du cadre attestation : VERT (#2E7D53)
    private const COULEUR_TITRE      = '#6c0000'; // Titre attestation + libellés (#6C0000)
    private const COULEUR_TITRE_CERT = '#7a1f1f'; // Titre certificat d'authenticité (#7A1F1F)
    private const COULEUR_DOMAINE    = '#2e7d53'; // Domaine dans l'en-tête (#2E7D53)
    private const COULEUR_SIGN       = '#e6af00'; // Intitulés des signataires (#E6AF00)
    private const COULEUR_NOM_SIGN   = '#666666'; // Noms des signataires (#666666)
    private const COULEUR_REF        = '#666666'; // Référence / mentions grises (#666666)
    private const COULEUR_LIEN       = '#0d6efd'; // E-mail / site web

    /* Largeur du cadre de l'attestation, calée sur la page.
       Page A4 paysage = 297 mm, marges de 8,5 mm de chaque côté, soit 280 mm
       de zone utile. Le cadre démarre à 8,5 mm : pour être CENTRÉ il doit
       mesurer 253 mm (bord droit à 288,2 mm, centre à 148,4 mm).
       Mesuré sur le PDF : 259 mm donnait un bord droit à 294 mm, donc un
       cadre décentré de 2,75 mm vers la droite (le padding s'ajoute à la
       largeur déclarée). Au-delà de 262 mm la bordure sort de la page. */
    private const LARGEUR_CADRE = '253mm';

    /**
     * Page principale : sélection de l'année, de la filière et personnalisation des champs.
     */
    public function index(Request $request)
    {
        $annees = AnneeAcademique::orderBy('id_Annee_Acad', 'desc')->get();
        $selectedAnnee = $request->get('anneeAcademique', $annees->first()?->id_Annee_Acad);

        // Filières de 3ème année (LP5,6 ou terminant par 3)
        $filieresL3 = AnneeEtude::where(function ($q) {
            $q->where('Code_Annee_Etude', 'LIKE', '%5,6%')
              ->orWhere('Code_Annee_Etude', 'LIKE', '%3')
              ->orWhere('Libelle_AnEtude', 'LIKE', '%3%');
        })->orderBy('Libelle_AnEtude')->get();

        $selectedFiliere = $request->get('anneeEtude', $filieresL3->first()?->Code_Annee_Etude);

        // Informations de l'établissement
        $univ = (new ImpressionController())->convert_array(DB::select("SELECT * FROM ufr,universites WHERE ufr.Univ=universites.id"));

        // Données dynamiques de la filière sélectionnée
        $filiereDetails = self::detectFiliereDetails($selectedFiliere);

        // Année académique sélectionnée
        $anneeModel = AnneeAcademique::find($selectedAnnee);
        $anneeLibelle = $anneeModel ? $anneeModel->ANNEE_ACAD : '2026-2027';
        $anneeCourante = explode('-', $anneeLibelle)[0] ?? date('Y');
        $anneeFin = explode('-', $anneeLibelle)[1] ?? (date('Y') + 1);

        // Liste des étudiants inscrits dans cette classe pour l'année
        $inscriptions = collect();
        if ($selectedAnnee && $selectedFiliere) {
            $inscriptions = Inscription::with('etudiant')
                ->where('Id_Annee', $selectedAnnee)
                ->where('ID_FILIERE', $selectedFiliere)
                ->orderBy('MATRICULE')
                ->get();
        }

        return view('attestationsDiplomes', compact(
            'annees',
            'selectedAnnee',
            'filieresL3',
            'selectedFiliere',
            'filiereDetails',
            'anneeLibelle',
            'anneeFin',
            'univ',
            'inscriptions'
        ));
    }

    /**
     * Détection automatique des intitulés pédagogiques (Grade, Domaine, Spécialité) selon le code filière.
     */
    public static function detectFiliereDetails(?string $codeFiliere): array
    {
        $details = [
            'arrete_ligne1' => 'Arrêté Ministériel N° 2021-0281/CNE/P/CQR/SE du 9 Novembre 2021',
            'arrete_ligne2' => 'N° 2334/MERS/DC/SGM/DGES/SA du 22 Novembre 2021',
            'domaine_arrete' => 'Bâtiments et Travaux Publics',
            'cycle' => 'Licence Professionnelle en Bâtiments et Travaux Publics (BTP)',
            'specialite' => 'Génie Civil',
            'decision_soutenance' => 'Conformément à la décision d’Autorisation des soutenances N° 508-26/MERS/DGES/DOSES/SEPES/DSPI/SA du 06 Juin 2026.',
            'ref_prefixe' => '/IUSAA/PDG/DA/DE/DEA/2026',
            'session' => 'Juin 2026',
            'mention_defaut' => 'Honorable',
            'date_delivrance' => '19 Août 2026',
            'lieu_delivrance' => 'Porto-Novo',
        ];

        if (!$codeFiliere) {
            return $details;
        }

        if (str_contains($codeFiliere, 'GC')) {
            $details['domaine_arrete'] = 'Bâtiments et Travaux Publics';
            $details['cycle'] = 'Licence Professionnelle en Bâtiments et Travaux Publics (BTP)';
            $details['specialite'] = 'Génie Civil';
        } elseif (str_contains($codeFiliere, 'GT')) {
            $details['domaine_arrete'] = 'Bâtiments et Travaux Publics';
            $details['cycle'] = 'Licence Professionnelle en Bâtiments et Travaux Publics (BTP)';
            $details['specialite'] = 'Géomètre Topographe';
        } elseif (str_contains($codeFiliere, 'GEER')) {
            $details['domaine_arrete'] = 'Génie Électrique et Énergétique';
            $details['cycle'] = 'Licence Professionnelle en Énergie et Environnement';
            $details['specialite'] = 'Génie Énergétique et Énergies Renouvelables';
        }

        return $details;
    }

    /**
     * Imprimer une ou toutes les Attestations de diplôme (A4 Paysage).
     */
    public function imprimerAttestation(Request $request)
    {
        $univ = (new ImpressionController())->convert_array(DB::select("SELECT * FROM ufr,universites WHERE ufr.Univ=universites.id"));
        $studentsData = self::preparerDonneesEtudiants($request);

        if (empty($studentsData)) {
            return back()->with('echec', 'Aucun étudiant sélectionné pour l\'impression.');
        }

        $htmlPages = [];
        foreach ($studentsData as $data) {
            $htmlPages[] = self::buildAttestationPageHtml($data, $univ);
        }

        $filename = 'Attestation_Diplome_' . ($request->anneeEtude ?? 'L3') . '_' . date('Ymd_His') . '.pdf';

        return self::genererPdf(implode("\n", $htmlPages), 'L', [8.5, 8.5, 8.5, 8.5], $filename);
    }

    /**
     * Imprimer un ou tous les Certificats d'authenticité (A4 Portrait).
     */
    public function imprimerCertificat(Request $request)
    {
        $univ = (new ImpressionController())->convert_array(DB::select("SELECT * FROM ufr,universites WHERE ufr.Univ=universites.id"));
        $studentsData = self::preparerDonneesEtudiants($request);

        if (empty($studentsData)) {
            return back()->with('echec', 'Aucun étudiant sélectionné pour l\'impression.');
        }

        $htmlPages = [];
        foreach ($studentsData as $data) {
            $htmlPages[] = self::buildCertificatPageHtml($data, $univ);
        }

        $filename = 'Certificat_Authenticite_' . ($request->anneeEtude ?? 'L3') . '_' . date('Ymd_His') . '.pdf';

        return self::genererPdf(implode("\n", $htmlPages), 'P', [14, 12, 14, 10], $filename);
    }

    /**
     * Imprimer le Pack Complet (Attestation Paysage + Certificat Portrait pour chaque étudiant).
     */
    public function imprimerPack(Request $request)
    {
        $univ = (new ImpressionController())->convert_array(DB::select("SELECT * FROM ufr,universites WHERE ufr.Univ=universites.id"));
        $studentsData = self::preparerDonneesEtudiants($request);

        if (empty($studentsData)) {
            return back()->with('echec', 'Aucun étudiant sélectionné pour l\'impression.');
        }

        $htmlPages = [];
        foreach ($studentsData as $data) {
            // Page 1 : Attestation Paysage
            $htmlPages[] = self::buildAttestationPageHtml($data, $univ);
            // Page 2 : Certificat Portrait
            $htmlPages[] = self::buildCertificatPageHtml($data, $univ);
        }

        $filename = 'Pack_Diplome_Authenticite_' . ($request->anneeEtude ?? 'L3') . '_' . date('Ymd_His') . '.pdf';

        // Html2Pdf gère les orientations mixtes via l'attribut orientation de chaque <page>
        return self::genererPdf(implode("\n", $htmlPages), 'L', [8.5, 8.5, 8.5, 8.5], $filename);
    }

    /**
     * Génère le PDF et le renvoie proprement comme réponse HTTP Laravel
     * (évite l'envoi direct de headers par Html2Pdf::Output en mode inline).
     */
    protected static function genererPdf(string $html, string $orientation, array $marges, string $filename)
    {
        try {
            $pdf = new Html2Pdf($orientation, 'A4', 'fr', true, 'UTF-8', $marges);
            $pdf->setTestTdInOnePage(false);

            /* Pas de <!DOCTYPE> ni de <body> autour du contenu : Html2Pdf
               ouvre alors une page blanche pour chaque enveloppe ajoutée
               (vérifié : 3 pages au lieu de 1). Le contenu est donc passé
               tel quel, Html2Pdf l'enveloppe lui-même. */
            $pdf->writeHTML($html);
            $contenu = $pdf->Output($filename, 'S');

            return response($contenu, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            return back()->with('echec', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    /**
     * Prépare la liste des données d'étudiants à imprimer à partir de la requête.
     */
    protected static function preparerDonneesEtudiants(Request $request): array
    {
        $matriculeCible = $request->get('matricule_seul');
        $annee = $request->anneeAcademique;
        $filiere = $request->anneeEtude;

        $query = Inscription::with('etudiant')
            ->where('Id_Annee', $annee)
            ->where('ID_FILIERE', $filiere);

        if ($matriculeCible) {
            $query->where('MATRICULE', $matriculeCible);
        }

        $inscriptions = $query->orderBy('MATRICULE')->get();
        $students = [];

        // Paramètres généraux du formulaire
        $arrete1       = $request->get('arrete_ligne1', 'Arrêté Ministériel N° 2021-0281/CNE/P/CQR/SE du 9 Novembre 2021');
        $arrete2       = $request->get('arrete_ligne2', 'N° 2334/MERS/DC/SGM/DGES/SA du 22 Novembre 2021');
        $domaineArrete = $request->get('domaine_arrete', 'Bâtiments et Travaux Publics');
        $cycle         = $request->get('cycle', 'Licence Professionnelle en Bâtiments et Travaux Publics (BTP)');
        $specialite    = $request->get('specialite', 'Génie Civil');
        $decision      = $request->get('decision_soutenance', 'Conformément à la décision d’Autorisation des soutenances N° 508-26/MERS/DGES/DOSES/SEPES/DSPI/SA du 06 Juin 2026.');
        $refPrefixe    = $request->get('ref_prefixe', '/IUSAA/PDG/DA/DE/DEA/2026');
        $session       = $request->get('session', 'Juin 2026');
        $dateDeliv     = $request->get('date_delivrance', '19 Août 2026');
        $lieuDeliv     = $request->get('lieu_delivrance', 'Porto-Novo');
        $mentionsPost  = $request->get('mentions', []);
        $refsPost      = $request->get('numeros_ref', []);
        $startNum      = (int) $request->get('start_num', 1);

        $moisFr = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        foreach ($inscriptions as $idx => $ins) {
            $mat = $ins->MATRICULE;
            $etu = $ins->etudiant;

            // Date de naissance formatée en français
            $dateNaisFr = '—';
            if ($etu && $etu->dateNaisEtu) {
                try {
                    $dt = Carbon::parse($etu->dateNaisEtu);
                    $dateNaisFr = $dt->format('d') . ' ' . ($moisFr[(int) $dt->format('m')] ?? '') . ' ' . $dt->format('Y');
                } catch (\Throwable $e) {
                    $dateNaisFr = $etu->dateNaisEtu;
                }
            }

            // Référence personnalisée, incrémentée, ou vierge (à remplir à la main)
            $numAuto = str_pad((string) ($startNum + $idx), 3, '0', STR_PAD_LEFT);
            $customRefNum = trim((string) ($refsPost[$mat] ?? ''));
            if ($customRefNum !== '') {
                // Si l'utilisateur a saisi la référence complète, on la garde ; sinon on la compose
                $refComplet = str_contains($customRefNum, 'IUSAA')
                    ? $customRefNum
                    : 'N° ' . $customRefNum . ' ' . $refPrefixe;
            } elseif ($request->filled('start_num')) {
                $refComplet = 'N° ' . $numAuto . ' ' . $refPrefixe;
            } else {
                $refComplet = 'N° _______ ' . $refPrefixe;
            }

            // Mention de l'étudiant
            $mentionEtu = $mentionsPost[$mat] ?? ($request->mention_defaut ?: 'Honorable');

            $students[] = [
                'matricule'        => $mat,
                'civilite'         => ($etu && $etu->sexeEtu === 'F') ? 'Mme' : 'M.',
                'nom_complet'      => $etu ? ($etu->nomEtu . ' ' . $etu->prenomsEtu) : $mat,
                'date_nais'        => $dateNaisFr,
                'lieu_nais'        => $etu && $etu->lieuNaisEtu ? $etu->lieuNaisEtu : 'Porto-Novo',
                'arrete_ligne1'    => $arrete1,
                'arrete_ligne2'    => $arrete2,
                'domaine_arrete'   => $domaineArrete,
                'cycle'            => $cycle,
                'specialite'       => $specialite,
                'decision'         => $decision,
                'reference'        => $refComplet,
                'session'          => $session,
                'mention'          => $mentionEtu,
                'date_delivrance'  => $dateDeliv,
                'lieu_delivrance'  => $lieuDeliv,
            ];
        }

        return $students;
    }

    /**
     * Retourne les infos de l'établissement avec valeurs par défaut.
     */
    protected static function infosEtablissement(array $univ): array
    {
        $u = $univ[0] ?? [];

        return [
            'logoUniv' => !empty($u['logo']) ? './storage/assets/img/univ/' . $u['logo'] : './storage/assets/img/univ/iusaa.png',
            'logoUfr'  => !empty($u['logo_ufr']) ? './storage/assets/img/ufr/' . $u['logo_ufr'] : './storage/assets/img/ufr/institutuniversitairesaintsadrienetanselme.png',
            'dirAdj'   => !empty($u['directeurAdjoint']) ? $u['directeurAdjoint'] : 'Adm-RH Natani Joël DASSI Angelo',
            'pdg'      => !empty($u['directeur']) ? $u['directeur'] : (!empty($u['recteur']) ? $u['recteur'] : 'Dr Erasme A. A. ARINLOYE Alphonse'),
            'tel'      => !empty($u['Tel']) ? $u['Tel'] : '(+229) 01 96 57 33 03 / 01 97 59 43 99',
            'email'    => !empty($u['mail']) ? $u['mail'] : 'iusaa@gmail.com',
            'siteWeb'  => !empty($u['Site_web']) ? $u['Site_web'] : 'www.iusaa.com',
        ];
    }

    /**
     * Génère le code HTML d'une Attestation de Diplôme (A4 Paysage).
     */
    public static function buildAttestationPageHtml(array $etu, array $univ): string
    {
        $i = self::infosEtablissement($univ);

        $logoUniv = $i['logoUniv'];
        $logoUfr  = $i['logoUfr'];
        $nomDirecteurAdjoint = $i['dirAdj'];
        $nomPDG   = $i['pdg'];
        $tel      = $i['tel'];
        $email    = $i['email'];
        $siteWeb  = $i['siteWeb'];

        $cCadre   = self::COULEUR_CADRE;
        $cTitre   = self::COULEUR_TITRE;
        $cDomaine = self::COULEUR_DOMAINE;
        $cSign    = self::COULEUR_SIGN;
        $cNomSign = self::COULEUR_NOM_SIGN;
        $cRef     = self::COULEUR_REF;
        $cLien    = self::COULEUR_LIEN;

        ob_start();
        ?>
        <page orientation="paysage" format="A4" backtop="4mm" backbottom="0mm">
            <!-- MISE EN PAGE — mesurée sur le PDF produit, ne pas improviser.
                 Cadre : 253 mm de large. Page 297 mm, marges 8,5 mm. À 259 mm
                 le bord droit tombait à 294 mm et le cadre était décentré de
                 2,6 mm ; au-delà de 262 mm la bordure sort de la page.
                 Pas de hauteur fixe : le cadre se referme sur son contenu.
                 Pied de page : bloc de largeur EXPLICITE, voir plus bas. -->
            <div style="border: 2.5px solid <?= $cCadre ?>; width: <?= self::LARGEUR_CADRE ?>; padding: 4mm 6mm; font-family: freeserif;">

                <!-- EN-TETE — tailles relevées de 2 pt par rapport au modèle d'origine -->
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 14%; text-align: left; vertical-align: middle;">
                            <img src="<?= $logoUniv ?>" style="width: 78px; height: 78px;" alt="Logo Univ">
                        </td>
                        <td style="width: 72%; text-align: center; vertical-align: middle; line-height: 1.3;">
                            <span style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px;">RÉPUBLIQUE DU BÉNIN</span><br>
                            <span style="font-size: 12pt; font-weight: bold; letter-spacing: 2px;">*****</span><br>
                            <span style="font-size: 11.5pt; font-weight: bold;">Ministère de l’Enseignement Supérieur et de la Recherche Scientifique</span><br>
                            <span style="font-size: 13pt; font-weight: bold; color: <?= $cDomaine ?>; margin-top: 1px;">Institut Universitaire Saints Adrien et Anselme</span><br>
                            <span style="font-size: 11pt; font-weight: bold; color: #111;"><?= htmlspecialchars($etu['arrete_ligne1']) ?></span><br>
                            <span style="font-size: 11pt; font-weight: bold; color: #111;"><?= htmlspecialchars($etu['arrete_ligne2']) ?></span><br>
                            <span style="font-size: 11.5pt; font-weight: bold; font-style: italic; text-decoration: underline; color: <?= $cDomaine ?>;"><?= htmlspecialchars($etu['domaine_arrete']) ?></span>
                        </td>
                        <td style="width: 14%; text-align: right; vertical-align: middle;">
                            <img src="<?= $logoUfr ?>" style="width: 78px; height: 78px;" alt="Logo UFR">
                        </td>
                    </tr>
                </table>

                <!-- REFERENCE (les « ––– » ont été retirés : ils faisaient tache à gauche) -->
                <table style="width: 100%; border-collapse: collapse; margin-top: 2mm;">
                    <tr>
                        <td style="text-align: right; font-size: 11.5pt; font-weight: bold; color: <?= $cRef ?>;">
                            <?= htmlspecialchars($etu['reference']) ?>
                        </td>
                    </tr>
                </table>

                <!-- TITRE — centré au milieu de la page, aucun trait au-dessus -->
                <div style="text-align: center; margin-top: 6mm; margin-bottom: 3mm;">
                    <span style="font-size: 21pt; font-weight: bold; color: <?= $cTitre ?>; letter-spacing: 1.5px;">
                        ATTESTATION DE DIPLÔME
                    </span>
                </div>

                <!-- CORPS — police relevée à 11 pt (9 pt auparavant) -->
                <table style="width: 100%; border-collapse: collapse; font-size: 11pt; line-height: 1.5;">
                    <tr>
                        <!-- GAUCHE -->
                        <td style="width: 48%; vertical-align: top; padding-right: 12px; text-align: justify;">
                            Le Président Directeur Général de l’Institut Universitaire Saints Adrien et Anselme (IUSAA), soussigné, atteste que :<br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">M./Mme : </span><b><?= htmlspecialchars($etu['nom_complet']) ?></b><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Né(e) le : </span><b><?= htmlspecialchars($etu['date_nais']) ?></b><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">À : </span><b><?= htmlspecialchars($etu['lieu_nais']) ?></b><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Inscrit(e) sous le numéro / Matricule : </span><b><?= htmlspecialchars($etu['matricule']) ?></b><br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Fait à : </span><b><?= htmlspecialchars($etu['lieu_delivrance']) ?></b><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Le : </span><b><?= htmlspecialchars($etu['date_delivrance']) ?></b>
                        </td>
                        <!-- DROITE -->
                        <td style="width: 52%; vertical-align: top; padding-left: 12px; text-align: justify;">
                            <?= htmlspecialchars($etu['decision']) ?><br><br>
                            A participé avec succès aux activités pédagogiques du cycle <b><?= htmlspecialchars($etu['cycle']) ?></b> et est déclaré(e) admis(e) avec la mention <b><?= htmlspecialchars($etu['mention']) ?></b> dans la Spécialité/Option : <b><?= htmlspecialchars($etu['specialite']) ?></b>.<br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Session : </span><b><?= htmlspecialchars($etu['session']) ?></b><br><br>
                            <div style="border-left: 3px solid <?= $cCadre ?>; padding: 2mm 0 2mm 4mm; margin-top: 1mm;">
                                <span style="font-family: freeserif; font-style: italic; font-size: 11.5pt; color: #1a1a1a; line-height: 1.45;">
                                    En foi de quoi, la présente attestation de diplôme lui est délivrée pour servir et valoir ce que de droit.
                                </span>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- SIGNATURES -->
                <table style="width: 100%; border-collapse: collapse; margin-top: 3mm;">
                    <tr>
                        <td style="width: 50%; text-align: center; vertical-align: top;">
                            <span style="color: <?= $cSign ?>; font-weight: bold; font-size: 11.5pt;">
                                Pour le Directeur Académique des Etudes,<br>
                                Le Directeur Adjoint,
                            </span>
                            <br><br><br><br>
                            <span style="color: <?= $cNomSign ?>; font-weight: bold; font-style: italic; font-size: 11pt;">
                                <?= htmlspecialchars($nomDirecteurAdjoint) ?>
                            </span>
                        </td>
                        <td style="width: 50%; text-align: center; vertical-align: top;">
                            <span style="color: <?= $cSign ?>; font-weight: bold; font-size: 11.5pt;">
                                Le Président Directeur Général,
                            </span>
                            <br><br><br><br>
                            <span style="color: <?= $cNomSign ?>; font-weight: bold; font-style: italic; font-size: 11pt;">
                                <?= htmlspecialchars($nomPDG) ?>
                            </span>
                        </td>
                    </tr>
                </table>


                <!-- PIED DE PAGE — DANS le cadre, séparé par un filet.
                     p align=center est le seul conteneur que html2pdf centre
                     correctement à cet endroit : mesuré à 148,3 mm sur une page
                     de 297. Un div à largeur explicite (même 280 mm) part à
                     gauche (66 à 94 mm) et un tableau tombe à 31 mm. -->
                <p align="center" style="text-align: center; font-family: freeserif; font-size: 10.5pt; color: #111; line-height: 1.25; margin: 3mm 0 0 0; border-top: 1px solid <?= $cCadre ?>; padding-top: 1.8mm;">
                    <b>Tél : <?= htmlspecialchars($tel) ?></b> &nbsp; E-mail : <span style="color: <?= $cLien ?>;"><?= htmlspecialchars($email) ?></span> / Site web : <span style="color: <?= $cLien ?>;"><?= htmlspecialchars($siteWeb) ?></span>
                </p>

            </div>
        </page>
        <?php
        return ob_get_clean();
    }

    /**
     * Génère le code HTML d'un Certificat d'Authenticité (A4 Portrait).
     */
    public static function buildCertificatPageHtml(array $etu, array $univ): string
    {
        $i = self::infosEtablissement($univ);

        $logoUniv = $i['logoUniv'];
        $logoUfr  = $i['logoUfr'];
        $nomDirecteurAdjoint = $i['dirAdj'];
        $nomPDG   = $i['pdg'];
        $tel      = $i['tel'];
        $email    = $i['email'];
        $siteWeb  = $i['siteWeb'];

        $cTitreCert = self::COULEUR_TITRE_CERT;
        $cTitre     = self::COULEUR_TITRE;
        $cDomaine   = self::COULEUR_DOMAINE;
        $cSign      = self::COULEUR_SIGN;
        $cNomSign   = self::COULEUR_NOM_SIGN;
        $cRef       = self::COULEUR_REF;
        $cLien      = self::COULEUR_LIEN;

        // Dans le corps du certificat, la référence s'affiche sans le préfixe "N°" (conforme au modèle)
        $referenceCorps = trim(preg_replace('/^N°\s*/u', '', $etu['reference']));

        ob_start();
        ?>
        <page orientation="portrait" format="A4" backtop="6mm" backbottom="0mm">
            <div style="font-family: freeserif;">
                
                <!-- EN-TETE -->
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 14%; text-align: left; vertical-align: middle;">
                            <img src="<?= $logoUniv ?>" style="width: 75px; height: 75px;" alt="Logo Univ">
                        </td>
                        <td style="width: 72%; text-align: center; vertical-align: middle; line-height: 1.25;">
                            <span style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px;">RÉPUBLIQUE DU BÉNIN</span><br>
                            <span style="font-size: 12pt; font-weight: bold; letter-spacing: 2px;">*****</span><br>
                            <span style="font-size: 11.5pt; font-weight: bold; line-height: 1.25;">Ministère de l’Enseignement Supérieur et de la Recherche Scientifique</span><br>
                            <span style="font-size: 13pt; font-weight: bold; color: <?= $cDomaine ?>; margin-top: 1mm;">Institut Universitaire Saints Adrien et Anselme</span><br>
                            <span style="font-size: 10.5pt; font-weight: bold; color: #111; margin-top: 1mm;"><?= htmlspecialchars($etu['arrete_ligne1']) ?></span><br>
                            <span style="font-size: 10.5pt; font-weight: bold; color: #111;"><?= htmlspecialchars($etu['arrete_ligne2']) ?></span><br>
                            <span style="font-size: 11.5pt; font-weight: bold; font-style: italic; text-decoration: underline; color: <?= $cDomaine ?>; margin-top: 1mm;"><?= htmlspecialchars($etu['domaine_arrete']) ?></span>
                        </td>
                        <td style="width: 14%; text-align: right; vertical-align: middle;">
                            <img src="<?= $logoUfr ?>" style="width: 75px; height: 75px;" alt="Logo UFR">
                        </td>
                    </tr>
                </table>

                <!-- REFERENCE -->
                <table style="width: 100%; border-collapse: collapse; margin-top: 3mm;">
                    <tr>
                        <td style="text-align: right; font-size: 11.5pt; font-weight: bold; color: <?= $cRef ?>;">
                            <?= htmlspecialchars($etu['reference']) ?>
                        </td>
                    </tr>
                </table>

                <!-- TITRE CERTIFICAT D'AUTHENTICITE -->
                <div style="text-align: center; margin-top: 10mm; margin-bottom: 7mm;">
                    <span style="font-size: 21pt; font-weight: bold; color: <?= $cTitreCert ?>; letter-spacing: 1.5px;">
                        CERTIFICAT D'AUTHENTICITÉ
                    </span>
                </div>

                <!-- PHRASE INTRODUCTIVE -->
                <div style="text-align: justify; font-size: 11.5pt; line-height: 1.5; margin-bottom: 5mm;">
                    Nous, soussigné(e) <b><i><?= htmlspecialchars($nomPDG) ?></i></b>, Président Directeur Général de l’Institut Universitaire Saints Adrien et Anselme (IUSAA), certifions par la présente que le document ci-dessous référencé est authentique et correspond aux registres officiels de notre établissement :
                </div>

                <!-- TABLEAU DETAILS EN 2 COLONNES -->
                <table style="width: 100%; border-collapse: collapse; font-size: 11pt; line-height: 1.6; margin-bottom: 5mm;">
                    <tr>
                        <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Type de document : </span><b>Attestation de diplôme</b><br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Numéro de référence du document : </span><b><?= htmlspecialchars($referenceCorps) ?></b><br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Délivré le : </span><b><?= htmlspecialchars($etu['date_delivrance']) ?></b>
                        </td>
                        <td style="width: 52%; vertical-align: top; padding-left: 10px;">
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Nom et Prénoms du titulaire : </span><b><?= htmlspecialchars($etu['nom_complet']) ?></b><br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Né(e) le : </span><b><?= htmlspecialchars($etu['date_nais']) ?></b><br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Matricule : </span><b><?= htmlspecialchars($etu['matricule']) ?></b><br><br>
                            <span style="color: <?= $cTitre ?>; font-weight: bold;">Cycle / Mention / Spécialité : </span><b><?= htmlspecialchars($etu['cycle']) ?> / <?= htmlspecialchars($etu['mention']) ?> / <?= htmlspecialchars($etu['specialite']) ?></b>
                        </td>
                    </tr>
                </table>

                <!-- TEXTE LEGAL -->
                <div style="text-align: justify; font-size: 11pt; color: #333; font-style: italic; line-height: 1.5; margin-bottom: 5mm;">
                    En foi de quoi, le présent certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit, notamment pour toute vérification auprès de tiers (employeur, établissement d'accueil, autorité administrative).
                </div>

                <!-- DATE ET LIEU -->
                <div style="font-size: 11.5pt; line-height: 1.6; margin-bottom: 6mm;">
                    <span style="color: <?= $cTitre ?>; font-weight: bold;">Fait à : </span><b><?= htmlspecialchars($etu['lieu_delivrance']) ?></b><br>
                    <span style="color: <?= $cTitre ?>; font-weight: bold;">Le : </span><b><?= htmlspecialchars($etu['date_delivrance']) ?></b>
                </div>

                <!-- SIGNATURES -->
                <table style="width: 100%; border-collapse: collapse; margin-top: 8mm; margin-bottom: 12mm;">
                    <tr>
                        <td style="width: 50%; text-align: center; vertical-align: top;">
                            <span style="color: <?= $cSign ?>; font-weight: bold; font-size: 12pt;">
                                Pour le Directeur Académique des Etudes,<br>
                                Le Directeur Adjoint,
                            </span>
                            <br><br><br><br>
                            <span style="color: <?= $cNomSign ?>; font-weight: bold; font-style: italic; font-size: 11.5pt;">
                                <?= htmlspecialchars($nomDirecteurAdjoint) ?>
                            </span>
                        </td>
                        <td style="width: 50%; text-align: center; vertical-align: top;">
                            <span style="color: <?= $cSign ?>; font-weight: bold; font-size: 12pt;">
                                Le Président Directeur Général,
                            </span>
                            <br><br><br><br><br>
                            <span style="color: <?= $cNomSign ?>; font-weight: bold; font-style: italic; font-size: 11.5pt;">
                                <?= htmlspecialchars($nomPDG) ?>
                            </span>
                        </td>
                    </tr>
                </table>

            </div>
        
            <!-- PIED DE PAGE du certificat — largeur explicite 193 mm =
                 210 - 2 x 8,5 de marges : même mécanisme que l'attestation. -->
            <div style="width: 193mm; text-align: center; font-family: freeserif; font-size: 10.5pt; color: #111; line-height: 1.35; margin-top: 6mm;">
                <b>Tél : <?= htmlspecialchars($tel) ?></b> &nbsp; E-mail : <span style="color: <?= $cLien ?>;"><?= htmlspecialchars($email) ?></span> / Site web : <span style="color: <?= $cLien ?>;"><?= htmlspecialchars($siteWeb) ?></span>
            </div>
</page>
        <?php
        return ob_get_clean();
    }
}