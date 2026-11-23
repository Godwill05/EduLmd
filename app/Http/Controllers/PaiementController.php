<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Prevision;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('paiements');
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
            'matricule'=>'required',
            'anneeAcademique'=>'required',
            'anneeEtude'=>'required',
            'montantPaye'=>'required|numeric|min:1',
            'modePaiement'=>'required'
        ]);

        $matricule = (string)$request->matricule;
        $anneeAcademique = $request->anneeAcademique;
        $anneeEtude = (string)$request->anneeEtude;

        $inscriptionQuery = Inscription::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeAcademique)
            ->where('ID_FILIERE', $anneeEtude);

        $inscription = $inscriptionQuery->first();

        if(!$inscription){
            return to_route('inscriptions.index')->with('echec','Inscription introuvable !');
        }

        $restePaye = (int) $inscription->reste_paye;
        $montant = (int) $request->montantPaye;

        if($montant > $restePaye){
            return to_route('inscriptions.index')->with('echec','Le montant payé ('.$montant.' F) dépasse le reste à payer ('.$restePaye.' F) !');
        }

        $nbPmts = Paiement::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeAcademique)
            ->where('ID_FILIERE', $anneeEtude)
            ->count();

        if ($request->filled('datePaiement')) {
            try {
                $datePaiement = \Carbon\Carbon::createFromFormat('d/m/Y', $request->datePaiement)->format('Y-m-d');
            } catch (\Throwable $e) {
                $datePaiement = \Carbon\Carbon::parse($request->datePaiement)->format('Y-m-d');
            }
        } else {
            $datePaiement = now()->format('Y-m-d');
        }

        Paiement::create([
            'MATRICULE'=>$matricule,
            'Id_Annee'=>$anneeAcademique,
            'ID_FILIERE'=>$anneeEtude,
            'montantPaye'=>$montant,
            'datePaiement'=>$datePaiement,
            'modePaiement'=>$request->modePaiement,
            'numeroTranche'=>$nbPmts+1
        ]);

        $nouveauReste = max(0, $restePaye - $montant);
        $statutFinance = $nouveauReste <= 0 ? 'OUI' : 'NON';

        $inscriptionQuery->update([
            'reste_paye' => $nouveauReste,
            'S_Finance' => $statutFinance
        ]);

        PrevisionController::synchroniserPrevisions($matricule, (int)$anneeAcademique, $anneeEtude);

        return to_route('inscriptions.index')->with('succes','Paiement de '.number_format($montant, 0, ',', ' ').' F enregistré ! Reste : '.number_format($nouveauReste, 0, ',', ' ').' F');
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
            'datePaiement'  => 'required',
            'montantPaye'   => 'required|numeric|min:0',
            'modePaiement'  => 'nullable|string',
        ]);

        $paiement = Paiement::find($id);
        if (!$paiement) {
            return to_route('paiements.index')->with('echec', 'Paiement introuvable !');
        }

        $ancienMontant = (int) $paiement->montantPaye;
        $nouveauMontant = (int) $request->montantPaye;
        $diff = $nouveauMontant - $ancienMontant; // positif = on a payé plus, négatif = on a réduit

        // Mise à jour du paiement
        $updateData = [
            'datePaiement' => $request->datePaiement,
            'montantPaye'  => $nouveauMontant,
            'modePaiement' => $request->modePaiement,
        ];
        if ($request->filled('numeroTranche')) {
            $updateData['numeroTranche'] = $request->numeroTranche;
        }
        $paiement->update($updateData);

        // Recalcul du reste_paye sur l'inscription
        if ($diff !== 0) {
            $inscription = Inscription::where('MATRICULE', $paiement->MATRICULE)
                ->where('Id_Annee', $paiement->Id_Annee)
                ->where('ID_FILIERE', $paiement->ID_FILIERE)
                ->first();

            if ($inscription) {
                $nouveauReste = max(0, (int)$inscription->reste_paye - $diff);
                $statutFinance = $nouveauReste <= 0 ? 'OUI' : 'NON';
                $inscription->update(['reste_paye' => $nouveauReste, 'S_Finance' => $statutFinance]);

                // Resync prévisions
                PrevisionController::synchroniserPrevisions(
                    $paiement->MATRICULE,
                    (int) $paiement->Id_Annee,
                    $paiement->ID_FILIERE
                );
            }
        }

        return to_route('paiements.index')->with('succes', 'Paiement modifié avec succès !');
    }

    public function destroy($id)
    {
        $paiement = Paiement::find($id);

        if ($paiement) {
            $matricule = $paiement->MATRICULE;
            $annee = $paiement->Id_Annee;
            $filiere = $paiement->ID_FILIERE;
            $montant = (int) $paiement->montantPaye;

            $paiement->delete();

            $inscriptionQuery = Inscription::where('MATRICULE', $matricule)
                ->where('Id_Annee', $annee)
                ->where('ID_FILIERE', $filiere);

            $inscription = $inscriptionQuery->first();

            if ($inscription) {
                $nouveauReste = (int)$inscription->reste_paye + $montant;
                $statutFinance = $nouveauReste <= 0 ? 'OUI' : 'NON';

                $inscriptionQuery->update([
                    'reste_paye' => $nouveauReste,
                    'S_Finance' => $statutFinance
                ]);

                PrevisionController::synchroniserPrevisions($matricule, (int)$annee, $filiere);
            }
        }

        return to_route('paiements.index')->with('succes','Paiement supprimé avec succès !');
    }

    /**
     * Calculer la situation des paiements à une date d'échéance donnée.
     * Détermine pour chaque étudiant s'il est en règle, en retard ou sans échéancier.
     */
    public static function getSituationEcheanceData($anneeAcademique, $dateEcheance, $codeFiliere = null, $statutFiltre = 'tous'): array
    {
        $annee = AnneeAcademique::find($anneeAcademique);

        $filieresQuery = AnneeEtude::whereHas('inscriptions', function ($q) use ($anneeAcademique) {
            $q->where('Id_Annee', $anneeAcademique);
        });

        if (!empty($codeFiliere)) {
            $filieresQuery->where('Code_Annee_Etude', $codeFiliere);
        }

        $anneesEtude = $filieresQuery->orderBy('Code_Annee_Etude')->get();

        $groupes = [];
        $totauxGlobaux = [
            'total_etudiants' => 0,
            'total_frais' => 0,
            'total_exigible' => 0,
            'total_paye' => 0,
            'total_retard' => 0,
            'nb_en_regle' => 0,
            'nb_en_retard' => 0,
            'nb_sans_echeancier' => 0,
        ];

        foreach ($anneesEtude as $ae) {
            $inscriptions = Inscription::with('etudiant')
                ->where('Id_Annee', $anneeAcademique)
                ->where('ID_FILIERE', $ae->Code_Annee_Etude)
                ->orderBy('MATRICULE')
                ->get();

            $etudiantsFiliere = [];
            $totauxFiliere = [
                'total_frais' => 0,
                'total_exigible' => 0,
                'total_paye' => 0,
                'total_retard' => 0,
                'nb_en_regle' => 0,
                'nb_en_retard' => 0,
                'nb_sans_echeancier' => 0,
            ];

            foreach ($inscriptions as $ins) {
                $matricule = $ins->MATRICULE;
                $fraisTotal = (int) $ins->frais_paye;

                // Prévisions de l'étudiant pour cette année et filière
                $prevs = Prevision::where('MATRICULE', $matricule)
                    ->where('Id_Annee', $anneeAcademique)
                    ->where('ID_FILIERE', $ae->Code_Annee_Etude)
                    ->orderBy('echeance')
                    ->get();

                $aEcheancier = $prevs->isNotEmpty();

                // Montant exigible à la date cible (somme des tranches dont l'échéance <= dateEcheance)
                $exigible = (int) $prevs->where('echeance', '<=', $dateEcheance)->sum('montant_a_payer');

                // Somme des versements réels effectués jusqu'à cette date
                $paye = (int) Paiement::where('MATRICULE', $matricule)
                    ->where('Id_Annee', $anneeAcademique)
                    ->where('ID_FILIERE', $ae->Code_Annee_Etude)
                    ->where('datePaiement', '<=', $dateEcheance)
                    ->sum('montantPaye');

                // Cumul total historique de l'année (pour détecter si déjà entièrement soldé)
                $cumulTotal = (int) $ins->paiements()->sum('montantPaye');
                $isSolde = ($ins->reste_paye <= 0 || ($fraisTotal > 0 && $cumulTotal >= $fraisTotal));

                if ($isSolde) {
                    $statut = 'solde';
                    $statutLibelle = 'Soldé';
                    $statutBadge = 'success';
                    $retard = 0;
                    $statutFiliereKey = 'nb_en_regle';
                } elseif (!$aEcheancier) {
                    $statut = 'sans_echeancier';
                    $statutLibelle = 'Sans échéancier';
                    $statutBadge = 'secondary';
                    $retard = 0;
                    $statutFiliereKey = 'nb_sans_echeancier';
                } elseif ($paye >= $exigible) {
                    $statut = 'en_regle';
                    $statutLibelle = 'En règle';
                    $statutBadge = 'success';
                    $retard = 0;
                    $statutFiliereKey = 'nb_en_regle';
                } else {
                    $statut = 'en_retard';
                    $statutLibelle = 'En retard';
                    $statutBadge = 'danger';
                    $retard = $exigible - $paye;
                    $statutFiliereKey = 'nb_en_retard';
                }

                // Filtrage selon le critère demandé
                if ($statutFiltre === 'en_regle' && !in_array($statut, ['en_regle', 'solde'])) {
                    continue;
                }
                if ($statutFiltre === 'en_retard' && $statut !== 'en_retard') {
                    continue;
                }
                if ($statutFiltre === 'sans_echeancier' && $statut !== 'sans_echeancier') {
                    continue;
                }

                $totauxFiliere[$statutFiliereKey]++;
                $totauxFiliere['total_frais'] += $fraisTotal;
                $totauxFiliere['total_exigible'] += $exigible;
                $totauxFiliere['total_paye'] += $paye;
                $totauxFiliere['total_retard'] += $retard;

                $etudiantsFiliere[] = [
                    'matricule' => $matricule,
                    'nom' => $ins->etudiant ? $ins->etudiant->nomEtu : 'Inconnu',
                    'prenoms' => $ins->etudiant ? $ins->etudiant->prenomsEtu : '',
                    'frais_total' => $fraisTotal,
                    'exigible' => $exigible,
                    'paye' => $paye,
                    'retard' => $retard,
                    'reste_annuel' => max(0, $fraisTotal - $cumulTotal),
                    'statut' => $statut,
                    'statut_libelle' => $statutLibelle,
                    'statut_badge' => $statutBadge,
                    'a_echeancier' => $aEcheancier,
                    'nb_echeances' => $prevs->count(),
                ];
            }

            if (!empty($etudiantsFiliere)) {
                $groupes[] = [
                    'filiere' => $ae,
                    'etudiants' => $etudiantsFiliere,
                    'totaux' => $totauxFiliere,
                ];

                $totauxGlobaux['total_etudiants'] += count($etudiantsFiliere);
                $totauxGlobaux['total_frais'] += $totauxFiliere['total_frais'];
                $totauxGlobaux['total_exigible'] += $totauxFiliere['total_exigible'];
                $totauxGlobaux['total_paye'] += $totauxFiliere['total_paye'];
                $totauxGlobaux['total_retard'] += $totauxFiliere['total_retard'];
                $totauxGlobaux['nb_en_regle'] += $totauxFiliere['nb_en_regle'];
                $totauxGlobaux['nb_en_retard'] += $totauxFiliere['nb_en_retard'];
                $totauxGlobaux['nb_sans_echeancier'] += $totauxFiliere['nb_sans_echeancier'];
            }
        }

        return [
            'annee' => $annee,
            'anneeAcademique' => $anneeAcademique,
            'dateEcheance' => $dateEcheance,
            'statutFiltre' => $statutFiltre,
            'codeFiliere' => $codeFiliere,
            'groupes' => $groupes,
            'totauxGlobaux' => $totauxGlobaux,
        ];
    }

    /**
     * Endpoint AJAX : Afficher la situation des paiements par échéance à l'écran.
     */
    public function situationEcheance(Request $request)
    {
        $request->validate([
            'anneeAcademique' => 'required',
            'dateEcheance' => 'required',
        ]);

        if ($request->filled('dateEcheance')) {
            try {
                $dateEcheance = Carbon::createFromFormat('d/m/Y', $request->dateEcheance)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dateEcheance = Carbon::parse($request->dateEcheance)->format('Y-m-d');
            }
        } else {
            $dateEcheance = now()->format('Y-m-d');
        }

        $data = self::getSituationEcheanceData(
            $request->anneeAcademique,
            $dateEcheance,
            $request->anneeEtude,
            $request->input('statutFiltre', 'tous')
        );

        return view('displaySituationEcheance', $data);
    }

    /**
     * Journal d'encaissement : liste des paiements groupés par date sur une période.
     * Endpoint AJAX → retourne la vue partielle displayJournalEncaissement.
     */
    public function journalEncaissement(Request $request)
    {
        $request->validate([
            'anneeAcademique'    => 'required',
            'dateDebut'          => 'required|date',
            'dateFin'            => 'required|date|after_or_equal:dateDebut',
            'journalEtudiant'    => 'nullable|string',
            'journalModePaiement'=> 'nullable|string',
        ]);

        $anneeAcademique = $request->anneeAcademique;
        $dateDebut       = Carbon::parse($request->dateDebut)->format('Y-m-d');
        $dateFin         = Carbon::parse($request->dateFin)->format('Y-m-d');
        $codeFiliere     = $request->anneeEtude ?: null;
        $filtreEtudiant  = trim($request->journalEtudiant ?? '');
        $filtreMode      = $request->journalModePaiement ?: null;

        $annee = AnneeAcademique::find($anneeAcademique);

        $query = Paiement::where('Id_Annee', $anneeAcademique)
            ->whereBetween('datePaiement', [$dateDebut, $dateFin])
            ->orderBy('datePaiement')
            ->orderBy('MATRICULE');

        if ($codeFiliere) {
            $query->where('ID_FILIERE', $codeFiliere);
        }

        // Filtre par mode de paiement (colonne directe sur Paiement)
        if ($filtreMode) {
            $query->where('modePaiement', $filtreMode);
        }

        // Filtre par étudiant : on récupère les matricules correspondants
        // dans Inscription puis on filtre Paiement sur ces matricules.
        $matriculesAutorises = null;
        if ($filtreEtudiant !== '') {
            $matriculesAutorises = Inscription::where('Id_Annee', $anneeAcademique)
                ->where(function ($q) use ($filtreEtudiant) {
                    $q->where('MATRICULE', 'like', '%' . $filtreEtudiant . '%')
                      ->orWhereHas('etudiant', function ($eq) use ($filtreEtudiant) {
                          $eq->where('nomEtu', 'like', '%' . $filtreEtudiant . '%')
                             ->orWhere('prenomsEtu', 'like', '%' . $filtreEtudiant . '%');
                      });
                })
                ->pluck('MATRICULE');
            $query->whereIn('MATRICULE', $matriculesAutorises);
        }

        $paiements = $query->get();

        // Grouper par date de paiement
        $groupesParDate = [];
        $totalGlobal    = 0;

        foreach ($paiements as $p) {
            $date = $p->datePaiement;
            if (!isset($groupesParDate[$date])) {
                $groupesParDate[$date] = [
                    'date'      => $date,
                    'paiements' => [],
                    'total'     => 0,
                ];
            }

            // Charger infos inscription + étudiant
            $ins = Inscription::with('etudiant')
                ->where('MATRICULE', $p->MATRICULE)
                ->where('Id_Annee', $p->Id_Annee)
                ->where('ID_FILIERE', $p->ID_FILIERE)
                ->first();

            $groupesParDate[$date]['paiements'][] = [
                'id'           => $p->id,
                'matricule'    => $p->MATRICULE,
                'nom'          => $ins && $ins->etudiant ? $ins->etudiant->nomEtu : '—',
                'prenoms'      => $ins && $ins->etudiant ? $ins->etudiant->prenomsEtu : '',
                'filiere'      => $ins ? ($ins->anneeEtude ? $ins->anneeEtude->Libelle_AnEtude : $p->ID_FILIERE) : $p->ID_FILIERE,
                'montant'      => (int) $p->montantPaye,
                'mode'         => $p->modePaiement ?? 'Espèces',
                'tranche'      => $p->numeroTranche,
                'reste_apres'  => $ins ? (int)$ins->reste_paye : null,
            ];
            $groupesParDate[$date]['total'] += (int) $p->montantPaye;
            $totalGlobal += (int) $p->montantPaye;
        }

        // Totaux par mode de paiement
        $totalParMode = [];
        foreach ($paiements as $p) {
            $mode = $p->modePaiement ?: 'Espèces';
            $totalParMode[$mode] = ($totalParMode[$mode] ?? 0) + (int) $p->montantPaye;
        }

        return view('displayJournalEncaissement', [
            'annee'         => $annee,
            'dateDebut'     => $dateDebut,
            'dateFin'       => $dateFin,
            'groupesParDate'=> $groupesParDate,
            'totalGlobal'   => $totalGlobal,
            'totalParMode'  => $totalParMode,
            'nbPaiements'   => $paiements->count(),
        ]);
    }
}
