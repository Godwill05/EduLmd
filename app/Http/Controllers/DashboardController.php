<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\Ecue;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Ue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord analytique.
 *
 * Toutes les agrégations sont calculées côté serveur en SQL (GROUP BY),
 * jamais en chargeant les collections en mémoire : le dashboard reste
 * rapide même avec des dizaines de milliers d'inscriptions.
 */
class DashboardController extends Controller
{
    /**
     * Tableau de bord complet (KPI + graphiques + tableaux triables).
     */
    public function index(Request $request)
    {
        // ── Filtres (validés) ────────────────────────────────────────────
        $anneeAcad = $request->filled('annee') ? (int) $request->input('annee') : null;
        $anneeEtude = $request->filled('annee_etude') ? $request->input('annee_etude') : null;
        $statut = $request->filled('statut') ? $request->input('statut') : null;

        $annees = AnneeAcademique::orderByDesc('id_Annee_Acad')
            ->get(['id_Annee_Acad', 'ANNEE_ACAD']);

        // Année par défaut : la plus récente
        if ($anneeAcad === null && $annees->isNotEmpty()) {
            $anneeAcad = $annees->first()->id_Annee_Acad;
        }

        // ── Requête de base des inscriptions (une seule source de vérité) ─
        $inscriptions = fn() => Inscription::query()
            ->when($anneeAcad, fn($q) => $q->where('Id_Annee', $anneeAcad))
            ->when($anneeEtude, fn($q) => $q->where('ID_FILIERE', $anneeEtude))
            ->when($statut, fn($q) => $q->where('codeStatut', $statut));

        // ── KPI ─────────────────────────────────────────────────────────
        $nbInscrits = (int) $inscriptions()->count();

        $finances = $inscriptions()->selectRaw(
            'COALESCE(SUM(frais_paye), 0) as attendu,
             COALESCE(SUM(reste_paye), 0) as reste'
        )->first();

        $attendu = (int) ($finances->attendu ?? 0);
        $reste = (int) ($finances->reste ?? 0);
        $encaisse = max(0, $attendu - $reste);
        $tauxRecouvrement = $attendu > 0 ? round($encaisse / $attendu * 100, 1) : 0;

        $nbPaiements = (int) Paiement::query()
            ->when($anneeAcad, fn($q) => $q->where('Id_Annee', $anneeAcad))
            ->when($anneeEtude, fn($q) => $q->where('ID_FILIERE', $anneeEtude))
            ->count();

        // Étudiants sans aucun paiement enregistré (à relancer).
        // distinct() évite de construire une sous-requête volumineuse :
        // on ne veut que la liste des matricules ayant payé au moins une fois.
        $avecPaiement = Paiement::query()
            ->when($anneeAcad, fn($q) => $q->where('paiements.Id_Annee', $anneeAcad))
            ->when($anneeEtude, fn($q) => $q->where('paiements.ID_FILIERE', $anneeEtude))
            ->select('paiements.MATRICULE')
            ->distinct();
        $nbSansPaiement = (int) $inscriptions()
            ->whereNotIn('inscriptions.MATRICULE', $avecPaiement)
            ->count();

        // Étudiants ayant encore un reste à payer
        $nbEnRetard = (int) $inscriptions()->where('reste_paye', '>', 0)->count();

        $nbEtudiantsCatalogue = (int) Etudiant::count();
        $nbEnseignants = (int) Enseignant::count();
        $nbUe = (int) Ue::count();
        $nbEcue = (int) Ecue::count();

        // Note moyenne du périmètre.
        // Attention : dans `obtenir`, numeroInscription contient le MATRICULE
        // (voir NoteController / AnneeEtudeController), pas un identifiant.
        $moyenne = DB::table('obtenir')
            ->when($anneeAcad, function ($q) use ($anneeAcad) {
                $q->whereIn('obtenir.numeroInscription', function ($sub) use ($anneeAcad) {
                    $sub->select('MATRICULE')->from('inscriptions')
                        ->where('Id_Annee', $anneeAcad);
                });
            })
            ->when($anneeEtude, function ($q) use ($anneeEtude) {
                $q->whereIn('obtenir.numeroInscription', function ($sub) use ($anneeEtude) {
                    $sub->select('MATRICULE')->from('inscriptions')
                        ->where('ID_FILIERE', $anneeEtude);
                });
            })
            ->whereNotNull('Note')
            ->avg('Note');

        // ── Graphiques ──────────────────────────────────────────────────

        // 1. Inscriptions par année académique (histogramme)
        $parAnnee = Inscription::query()
            ->join('anneeacademique', 'inscriptions.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->select('anneeacademique.ANNEE_ACAD as label', DB::raw('COUNT(*) as total'))
            ->groupBy('anneeacademique.ANNEE_ACAD')
            ->orderBy('anneeacademique.ANNEE_ACAD')
            ->get();

        // 2. Inscriptions par année d'étude (histogramme)
        $parAnneeEtude = Inscription::query()
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->when($anneeAcad, fn($q) => $q->where('inscriptions.Id_Annee', $anneeAcad))
            ->select(
                DB::raw('COALESCE(annee_etude.Libelle_AnEtude, inscriptions.ID_FILIERE) as label'),
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(inscriptions.reste_paye), 0) as reste')
            )
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        // 3. Répartition par sexe (camembert)
        $parSexe = Inscription::query()
            ->join('etudiant', 'inscriptions.MATRICULE', '=', 'etudiant.MATRICULE')
            ->when($anneeAcad, fn($q) => $q->where('inscriptions.Id_Annee', $anneeAcad))
            ->when($anneeEtude, fn($q) => $q->where('inscriptions.ID_FILIERE', $anneeEtude))
            ->select(
                DB::raw("CASE WHEN etudiant.sexeEtu = 'F' THEN 'Filles'
                              WHEN etudiant.sexeEtu = 'M' THEN 'Garçons'
                              ELSE 'Non renseigné' END as label"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('label')
            ->get();

        // 4. Encaissements mensuels (courbe) — sur l'année sélectionnée
        $parMois = Paiement::query()
            ->when($anneeAcad, fn($q) => $q->where('Id_Annee', $anneeAcad))
            ->when($anneeEtude, fn($q) => $q->where('ID_FILIERE', $anneeEtude))
            ->whereNotNull('datePaiement')
            ->select(
                DB::raw("DATE_FORMAT(datePaiement, '%Y-%m') as mois"),
                DB::raw('SUM(montantPaye) as total'),
                DB::raw('COUNT(*) as nb')
            )
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // 5. Modes de paiement (anneau)
        $parMode = Paiement::query()
            ->when($anneeAcad, fn($q) => $q->where('Id_Annee', $anneeAcad))
            ->when($anneeEtude, fn($q) => $q->where('ID_FILIERE', $anneeEtude))
            ->select(
                DB::raw("COALESCE(NULLIF(modePaiement, ''), 'Non précisé') as label"),
                DB::raw('SUM(montantPaye) as total'),
                DB::raw('COUNT(*) as nb')
            )
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        // 6. Recouvrement par année d'étude (barres empilées : encaissé / reste)
        $recouvrement = Inscription::query()
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->when($anneeAcad, fn($q) => $q->where('inscriptions.Id_Annee', $anneeAcad))
            ->select(
                DB::raw('COALESCE(annee_etude.Libelle_AnEtude, inscriptions.ID_FILIERE) as label'),
                DB::raw('COALESCE(SUM(inscriptions.frais_paye), 0) as attendu'),
                DB::raw('COALESCE(SUM(inscriptions.reste_paye), 0) as reste')
            )
            ->groupBy('label')
            ->orderByDesc('attendu')
            ->get()
            ->map(function ($r) {
                $r->encaisse = max(0, (int) $r->attendu - (int) $r->reste);
                return $r;
            });

        // ── Tableaux triables ───────────────────────────────────────────

        // Top classes par effectif + recouvrement
        $tableFilieres = $parAnneeEtude->map(function ($r) {
            $att = (int) ($r->total ?? 0);
            return (object) [
                'label' => $r->label,
                'effectif' => (int) $r->total,
                'reste' => (int) $r->reste,
            ];
        });

        // Derniers paiements enregistrés.
        //
        // NOTE — ne PAS utiliser ->with(['inscription.etudiant']) ici :
        // Paiement::inscription() n'est pas une relation Eloquent (la méthode
        // retourne directement un modèle via ->first()), et ->with() exige
        // une Relation, d'où l'erreur « Call to undefined method ».
        // On joint explicitement : une seule requête, pas de N+1.
        $derniersPaiements = Paiement::query()
            ->leftJoin('etudiant', 'paiements.MATRICULE', '=', 'etudiant.MATRICULE')
            ->when($anneeAcad, fn($q) => $q->where('paiements.Id_Annee', $anneeAcad))
            ->when($anneeEtude, fn($q) => $q->where('paiements.ID_FILIERE', $anneeEtude))
            ->orderByDesc('paiements.datePaiement')
            ->orderByDesc('paiements.id')
            ->limit(15)
            ->get([
                'paiements.id',
                'paiements.MATRICULE',
                'paiements.montantPaye',
                'paiements.datePaiement',
                'paiements.modePaiement',
                'etudiant.nomEtu',
                'etudiant.prenomsEtu',
            ]);

        return view('dashboard', compact(
            'annees', 'anneeAcad', 'anneeEtude', 'statut',
            'nbInscrits', 'nbPaiements', 'nbSansPaiement', 'nbEnRetard',
            'nbEtudiantsCatalogue', 'nbEnseignants', 'nbUe', 'nbEcue',
            'attendu', 'encaisse', 'reste', 'tauxRecouvrement', 'moyenne',
            'parAnnee', 'parAnneeEtude', 'parSexe', 'parMois', 'parMode',
            'recouvrement', 'tableFilieres', 'derniersPaiements'
        ));
    }
}
