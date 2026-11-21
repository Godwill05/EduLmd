<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Paiement en un clic.
 *
 * Permet d'encaisser depuis n'importe quelle page (liste d'étudiants,
 * recherche Ctrl+K, qualité des données) sans passer par l'écran Paiement.
 *
 * La logique métier est identique à PaiementController::store() :
 *   - contrôle du reste à payer
 *   - numéro de tranche incrémental
 *   - mise à jour du reste et du statut financier
 *   - synchronisation des prévisions
 */
class QuickPaymentController extends Controller
{
    /**
     * Inscriptions payables d'un étudiant (JSON).
     * GET /api/paiement-rapide/{matricule}
     */
    public function situation($matricule)
    {
        $inscriptions = Inscription::query()
            ->leftJoin('anneeacademique', 'inscriptions.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->where('inscriptions.MATRICULE', $matricule)
            ->orderByDesc('inscriptions.Id_Annee')
            ->get([
                'inscriptions.MATRICULE',
                'inscriptions.Id_Annee',
                'inscriptions.ID_FILIERE',
                'inscriptions.frais_paye',
                'inscriptions.reste_paye',
                'inscriptions.S_Finance',
                'anneeacademique.ANNEE_ACAD',
                'annee_etude.Libelle_AnEtude',
            ]);

        $lignes = $inscriptions->map(function ($i) use ($matricule) {
            $dejaPaye = (int) Paiement::where('MATRICULE', $matricule)
                ->where('Id_Annee', $i->Id_Annee)
                ->where('ID_FILIERE', $i->ID_FILIERE)
                ->sum('montantPaye');

            $nbTranches = (int) Paiement::where('MATRICULE', $matricule)
                ->where('Id_Annee', $i->Id_Annee)
                ->where('ID_FILIERE', $i->ID_FILIERE)
                ->count();

            return [
                'idAnnee' => (int) $i->Id_Annee,
                'idFiliere' => (string) $i->ID_FILIERE,
                'anneeAcad' => $i->ANNEE_ACAD,
                'anneeEtude' => $i->Libelle_AnEtude ?: $i->ID_FILIERE,
                'frais' => (int) $i->frais_paye,
                'reste' => (int) $i->reste_paye,
                'paye' => $dejaPaye,
                'tranches' => $nbTranches,
                'solde' => (int) $i->reste_paye <= 0,
            ];
        })->values();

        return response()->json([
            'matricule' => $matricule,
            'inscriptions' => $lignes,
            'totalReste' => (int) $lignes->where('solde', false)->sum('reste'),
        ]);
    }

    /**
     * Enregistre un paiement rapide (JSON).
     * POST /api/paiement-rapide
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'matricule' => 'required|string',
            'idAnnee' => 'required',
            'idFiliere' => 'required|string',
            'montant' => 'required|numeric|min:1',
            'mode' => 'nullable|string|max:40',
            'date' => 'nullable|string|max:20',
        ]);

        $matricule = (string) $data['matricule'];
        $idAnnee = $data['idAnnee'];
        $idFiliere = (string) $data['idFiliere'];
        $montant = (int) $data['montant'];

        $inscription = Inscription::where('MATRICULE', $matricule)
            ->where('Id_Annee', $idAnnee)
            ->where('ID_FILIERE', $idFiliere)
            ->first();

        if (!$inscription) {
            return response()->json([
                'ok' => false,
                'message' => "Aucune inscription trouvée pour ce matricule sur cette année.",
            ], 422);
        }

        $reste = (int) $inscription->reste_paye;

        if ($reste <= 0) {
            return response()->json([
                'ok' => false,
                'message' => "Cet étudiant est déjà à jour : aucun reste à payer.",
            ], 422);
        }

        if ($montant > $reste) {
            return response()->json([
                'ok' => false,
                'message' => "Le montant (" . number_format($montant, 0, ',', ' ') . " F) dépasse le reste à payer ("
                    . number_format($reste, 0, ',', ' ') . " F).",
            ], 422);
        }

        // Date : on accepte jj/mm/aaaa ou aaaa-mm-jj, sinon aujourd'hui.
        $datePaiement = now()->format('Y-m-d');
        if (!empty($data['date'])) {
            try {
                $datePaiement = \Carbon\Carbon::createFromFormat('d/m/Y', $data['date'])->format('Y-m-d');
            } catch (\Throwable $e) {
                try {
                    $datePaiement = \Carbon\Carbon::parse($data['date'])->format('Y-m-d');
                } catch (\Throwable $ignored) {
                    $datePaiement = now()->format('Y-m-d');
                }
            }
        }

        $nbTranches = (int) Paiement::where('MATRICULE', $matricule)
            ->where('Id_Annee', $idAnnee)
            ->where('ID_FILIERE', $idFiliere)
            ->count();

        // Mode de paiement par défaut : « Espèces » si non fourni.
        $mode = !empty($data['mode']) ? $data['mode'] : 'Espèces';

        DB::transaction(function () use (
            $matricule, $idAnnee, $idFiliere, $montant, $datePaiement, $nbTranches, $mode, $reste
        ) {
            Paiement::create([
                'MATRICULE' => $matricule,
                'Id_Annee' => $idAnnee,
                'ID_FILIERE' => $idFiliere,
                'montantPaye' => $montant,
                'datePaiement' => $datePaiement,
                'modePaiement' => $mode,
                'numeroTranche' => $nbTranches + 1,
            ]);

            $nouveauReste = max(0, $reste - $montant);

            Inscription::where('MATRICULE', $matricule)
                ->where('Id_Annee', $idAnnee)
                ->where('ID_FILIERE', $idFiliere)
                ->update([
                    'reste_paye' => $nouveauReste,
                    'S_Finance' => $nouveauReste <= 0 ? 'OUI' : 'NON',
                ]);
        });

        // Synchronisation des prévisions (hors transaction : elle gère la sienne).
        try {
            PrevisionController::synchroniserPrevisions($matricule, (int) $idAnnee, $idFiliere);
        } catch (\Throwable $e) {
            // Non bloquant : le paiement est déjà enregistré.
        }

        $nouveauReste = max(0, $reste - $montant);

        return response()->json([
            'ok' => true,
            'message' => "Paiement de " . number_format($montant, 0, ',', ' ') . " F enregistré.",
            'montant' => $montant,
            'reste' => $nouveauReste,
            'solde' => $nouveauReste <= 0,
            'tranche' => $nbTranches + 1,
        ]);
    }
}
