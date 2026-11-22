<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Prevision;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrevisionController extends Controller
{
    /**
     * Détecte le niveau d'une filière :
     * 1 = 1ère année (LP1,2-*) -> 3 tranches
     * 2 = 2ème année (LP3,4-*) -> 4 tranches
     * 3 = 3ème année (LP5,6-*) -> 3 tranches
     */
    public static function detectNiveauFiliere(string $filiere): int
    {
        if (preg_match('/LP[_-]?3[,.]?4/i', $filiere) || preg_match('/LP3,4/i', $filiere) || preg_match('/LP-3,4/i', $filiere)) {
            return 2;
        }
        if (preg_match('/LP[_-]?5[,.]?6/i', $filiere) || preg_match('/LP5,6/i', $filiere) || preg_match('/LP-5,6/i', $filiere)) {
            return 3;
        }
        return 1;
    }

    /**
     * Calcule les tranches par défaut pour un montant total donné (Option 1 : parts égales).
     */
    public static function getTranchesParDefaut(int $niveau, ?string $anneeAcad, int $total): array
    {
        $startYear = 2026;
        if ($anneeAcad && preg_match('/(\d{4})-(\d{4})/', $anneeAcad, $m)) {
            $startYear = (int) $m[1];
        }
        $y  = $startYear;
        $y1 = $startYear + 1;

        if ($niveau === 2) {
            // 2ème année : 4 tranches
            $dates = ["{$y}-09-30", "{$y}-11-30", "{$y1}-01-31", "{$y1}-03-31"];
            $labels = ['Tranche 1', 'Tranche 2', 'Tranche 3', 'Tranche 4'];
            $part = (int) round($total / 4);
            $m1 = $part;
            $m2 = $part;
            $m3 = $part;
            $m4 = max(0, $total - ($m1 + $m2 + $m3));
            $montants = [$m1, $m2, $m3, $m4];
        } else {
            // 1ère et 3ème année : 3 tranches
            $dates = ["{$y}-09-30", "{$y}-11-30", "{$y1}-02-21"];
            $labels = ['Tranche 1', 'Tranche 2', 'Tranche 3'];
            $part = (int) round($total / 3);
            $m1 = $part;
            $m2 = $part;
            $m3 = max(0, $total - ($m1 + $m2));
            $montants = [$m1, $m2, $m3];
        }

        $res = [];
        foreach ($dates as $i => $d) {
            $res[] = [
                'numero' => $i + 1,
                'label' => $labels[$i],
                'date' => $d,
                'montant' => $montants[$i]
            ];
        }
        return $res;
    }

    /**
     * Synchronise les statuts `paye` et `reste_a_payer` des prévisions d'un étudiant avec ses paiements réels.
     */
    public static function synchroniserPrevisions(string $matricule, int $anneeId, string $filiere): void
    {
        $prevs = Prevision::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->orderBy('echeance')
            ->orderBy('id')
            ->get();

        if ($prevs->isEmpty()) {
            return;
        }

        $cumul = (int) Paiement::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->sum('montantPaye');

        foreach ($prevs as $p) {
            $montantTranche = (int) $p->montant_a_payer;
            if ($cumul >= $montantTranche) {
                $p->paye = 1;
                $p->reste_a_payer = 0;
                $cumul -= $montantTranche;
            } elseif ($cumul > 0) {
                $p->paye = 0;
                $p->reste_a_payer = max(0, $montantTranche - $cumul);
                $cumul = 0;
            } else {
                $p->paye = 0;
                $p->reste_a_payer = $montantTranche;
            }
            $p->save();
        }
    }

    /**
     * Affichage de la page de gestion des prévisions (3 onglets).
     */
    public function index(Request $request)
    {
        $annees = AnneeAcademique::orderBy('id_Annee_Acad', 'desc')->get();
        $filieres = AnneeEtude::orderBy('Libelle_AnEtude')->get();

        $anneeFiltre = $request->get('annee_filtre', $annees->first()->id_Annee_Acad ?? null);
        $filiereFiltre = $request->get('filiere_filtre', null);

        $query = Prevision::with(['etudiant', 'anneeacademique']);
        if (!empty($anneeFiltre)) {
            $query->where('Id_Annee', $anneeFiltre);
        }
        if (!empty($filiereFiltre)) {
            $query->where('ID_FILIERE', $filiereFiltre);
        }
        $previsions = $query->orderBy('Id_Annee', 'desc')->orderBy('ID_FILIERE')->orderBy('MATRICULE')->orderBy('echeance')->paginate(25);

        return view('previsions', compact('annees', 'filieres', 'previsions', 'anneeFiltre', 'filiereFiltre'));
    }

    /**
     * API AJAX : Retourne l'échéancier actuel (ou suggéré) d'un étudiant.
     */
    public function getEcheancierEtudiant(Request $request)
    {
        $matricule = $request->matricule;
        $anneeId = $request->anneeAcademique;
        $filiere = $request->anneeEtude;

        $inscription = Inscription::with(['etudiant', 'anneeacademique', 'anneeEtude'])
            ->where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->first();

        if (!$inscription) {
            return response()->json(['error' => 'Inscription introuvable pour cet étudiant.'], 404);
        }

        $fraisTotal = (int) $inscription->frais_paye;
        $cumulPaye = (int) Paiement::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->sum('montantPaye');
        $resteTotal = max(0, $fraisTotal - $cumulPaye);

        $anneeAcad = $inscription->anneeacademique ? $inscription->anneeacademique->ANNEE_ACAD : null;
        $niveau = self::detectNiveauFiliere($filiere);

        // Prévisions existantes en base
        $prevs = Prevision::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->orderBy('echeance')
            ->orderBy('id')
            ->get();

        $existeEnBase = $prevs->isNotEmpty();
        $tranches = [];

        if ($existeEnBase) {
            foreach ($prevs as $idx => $p) {
                $tranches[] = [
                    'numero' => $idx + 1,
                    'label' => 'Tranche ' . ($idx + 1),
                    'date' => Carbon::parse($p->echeance)->format('Y-m-d'),
                    'montant' => (int) $p->montant_a_payer,
                    'reste' => (int) $p->reste_a_payer,
                    'paye' => (bool) $p->paye,
                ];
            }
        } else {
            // Suggérer les tranches par défaut (parts égales)
            $tranches = self::getTranchesParDefaut($niveau, $anneeAcad, $fraisTotal);
        }

        return response()->json([
            'matricule' => $matricule,
            'nom_complet' => $inscription->etudiant ? $inscription->etudiant->fullname() : $matricule,
            'filiere' => $filiere,
            'filiere_libelle' => $inscription->anneeEtude ? $inscription->anneeEtude->Libelle_AnEtude : $filiere,
            'annee_acad' => $anneeAcad,
            'frais_total' => $fraisTotal,
            'cumul_paye' => $cumulPaye,
            'reste_total' => $resteTotal,
            'niveau' => $niveau,
            'existe_en_base' => $existeEnBase,
            'tranches' => $tranches,
        ]);
    }

    /**
     * Enregistrer un échéancier personnalisé pour un étudiant.
     */
    public function enregistrerEcheancierEtudiant(Request $request)
    {
        $request->validate([
            'matricule' => 'required',
            'anneeAcademique' => 'required',
            'anneeEtude' => 'required',
            'tranches' => 'required|array|min:1',
            'tranches.*.date' => 'required|date',
            'tranches.*.montant' => 'required|numeric|min:0',
        ]);

        $matricule = $request->matricule;
        $anneeId = $request->anneeAcademique;
        $filiere = $request->anneeEtude;

        $inscription = Inscription::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeId)
            ->where('ID_FILIERE', $filiere)
            ->first();

        if (!$inscription) {
            return response()->json(['success' => false, 'message' => 'Inscription introuvable.'], 404);
        }

        DB::beginTransaction();
        try {
            // Supprimer les anciennes prévisions de cet étudiant
            Prevision::where('MATRICULE', $matricule)
                ->where('Id_Annee', $anneeId)
                ->where('ID_FILIERE', $filiere)
                ->delete();

            // Créer les nouvelles tranches
            foreach ($request->tranches as $tr) {
                Prevision::create([
                    'MATRICULE' => $matricule,
                    'Id_Annee' => $anneeId,
                    'ID_FILIERE' => $filiere,
                    'montant_a_payer' => (int) $tr['montant'],
                    'reste_a_payer' => (int) $tr['montant'],
                    'paye' => 0,
                    'echeance' => Carbon::parse($tr['date'])->format('Y-m-d'),
                ]);
            }

            // Synchroniser avec les paiements réels déjà faits
            self::synchroniserPrevisions($matricule, $anneeId, $filiere);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Échéancier enregistré avec succès (' . count($request->tranches) . ' tranches) !'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Génération automatique des tranches pour toute une classe (ou toutes les filières).
     */
    public function genererClasse(Request $request)
    {
        $request->validate([
            'anneeAcademique' => 'required',
            'anneeEtude' => 'required',
        ]);

        $anneeId = $request->anneeAcademique;
        $filiere = $request->anneeEtude;
        $ecraser = $request->boolean('ecraser', false);

        $anneeModel = AnneeAcademique::find($anneeId);
        $anneeAcad = $anneeModel ? $anneeModel->ANNEE_ACAD : null;

        $query = Inscription::where('Id_Annee', $anneeId);
        if ($filiere !== 'toutes') {
            $query->where('ID_FILIERE', $filiere);
        }
        $inscriptions = $query->get();

        if ($inscriptions->isEmpty()) {
            return back()->with('echec', 'Aucun étudiant inscrit trouvé pour cette sélection.');
        }

        $nbGeneres = 0;
        $nbIgnores = 0;

        DB::beginTransaction();
        try {
            foreach ($inscriptions as $ins) {
                $mat = $ins->MATRICULE;
                $fil = $ins->ID_FILIERE;
                $fraisTotal = (int) $ins->frais_paye;

                $prevsExistantes = Prevision::where('MATRICULE', $mat)
                    ->where('Id_Annee', $anneeId)
                    ->where('ID_FILIERE', $fil)
                    ->count();

                if ($prevsExistantes > 0 && !$ecraser) {
                    $nbIgnores++;
                    continue;
                }

                if ($prevsExistantes > 0 && $ecraser) {
                    Prevision::where('MATRICULE', $mat)
                        ->where('Id_Annee', $anneeId)
                        ->where('ID_FILIERE', $fil)
                        ->delete();
                }

                $niveau = self::detectNiveauFiliere($fil);
                $tranches = self::getTranchesParDefaut($niveau, $anneeAcad, $fraisTotal);

                foreach ($tranches as $tr) {
                    Prevision::create([
                        'MATRICULE' => $mat,
                        'Id_Annee' => $anneeId,
                        'ID_FILIERE' => $fil,
                        'montant_a_payer' => $tr['montant'],
                        'reste_a_payer' => $tr['montant'],
                        'paye' => 0,
                        'echeance' => $tr['date'],
                    ]);
                }

                self::synchroniserPrevisions($mat, $anneeId, $fil);
                $nbGeneres++;
            }

            DB::commit();

            $msg = "Génération terminée avec succès ! {$nbGeneres} étudiant(s) configuré(s).";
            if ($nbIgnores > 0) {
                $msg .= " ({$nbIgnores} étudiant(s) déjà configuré(s) ont été conservés sans modification).";
            }

            return back()->with('succes', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('echec', 'Erreur lors de la génération : ' . $e->getMessage());
        }
    }

    /** Supprimer une prévision individuelle. */
    public function destroy($id)
    {
        $prev = Prevision::find($id);
        if ($prev) {
            $mat = $prev->MATRICULE;
            $an = $prev->Id_Annee;
            $fil = $prev->ID_FILIERE;
            $prev->delete();
            self::synchroniserPrevisions($mat, $an, $fil);
        }
        return back()->with('succes', 'Tranche supprimée !');
    }
}
