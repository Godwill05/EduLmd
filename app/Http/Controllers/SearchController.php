<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Http\Request;

/**
 * Recherche globale (Ctrl+K).
 *
 * Répond en JSON à la palette de commandes du layout. Recherche un étudiant
 * par nom, prénoms ou matricule et renvoie, pour chaque résultat, les actions
 * disponibles (fiche, paiements, parcours, inscription).
 */
class SearchController extends Controller
{
    /**
     * GET /api/recherche?q=...
     * Limité aux 8 meilleurs résultats pour rester léger côté client.
     */
    public function etudiants(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        /* Un seul caractère renvoyait une liste vide *avec le statut 200* :
           l'interface affichait « aucun étudiant » alors que la recherche
           n'avait pas été faite. On répond désormais explicitement. */
        if (mb_strlen($q) < 2) {
            return response()->json([
                'query' => $q,
                'count' => 0,
                'results' => [],
                'min_length' => 2,
                'message' => 'Saisissez au moins deux caractères.',
            ]);
        }

        // On échappe les jokers SQL pour éviter un LIKE involontairement large
        $term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

        $etudiants = Etudiant::query()
            ->where(function ($query) use ($term) {
                $query->where('nomEtu', 'like', $term)
                    ->orWhere('prenomsEtu', 'like', $term)
                    ->orWhere('MATRICULE', 'like', $term);
            })
            ->orderBy('nomEtu')
            ->orderBy('prenomsEtu')
            ->limit(8)
            ->get(['MATRICULE', 'nomEtu', 'prenomsEtu', 'photo', 'sexeEtu', 'telEtu', 'emailEtu']);

        if ($etudiants->isEmpty()) {
            return response()->json(['query' => $q, 'count' => 0, 'results' => []]);
        }

        // Dernière inscription de chaque étudiant (une seule requête)
        $matricules = $etudiants->pluck('MATRICULE');

        $inscriptions = Inscription::query()
            ->leftJoin('anneeacademique', 'inscriptions.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->whereIn('inscriptions.MATRICULE', $matricules)
            ->orderByDesc('inscriptions.Id_Annee')
            ->get([
                'inscriptions.MATRICULE',
                'inscriptions.Id_Annee',
                'inscriptions.ID_FILIERE',
                'inscriptions.reste_paye',
                'anneeacademique.ANNEE_ACAD',
                'annee_etude.Libelle_AnEtude',
            ])
            ->keyBy('MATRICULE');

        // Nombre de paiements par étudiant (une seule requête)
        $nbPaiements = Paiement::query()
            ->whereIn('MATRICULE', $matricules)
            ->selectRaw('MATRICULE, COUNT(*) as total')
            ->groupBy('MATRICULE')
            ->pluck('total', 'MATRICULE');

        $results = $etudiants->map(function ($e) use ($inscriptions, $nbPaiements) {
            $ins = $inscriptions->get($e->MATRICULE);
            $initiales = mb_strtoupper(
                mb_substr((string) $e->nomEtu, 0, 1) . mb_substr((string) $e->prenomsEtu, 0, 1)
            );

            return [
                'matricule' => $e->MATRICULE,
                'nom' => trim($e->nomEtu . ' ' . $e->prenomsEtu),
                'initiales' => $initiales ?: '?',
                'sexe' => $e->sexeEtu,
                'tel' => $e->telEtu,
                'email' => $e->emailEtu,
                'annee' => $ins->ANNEE_ACAD ?? null,
                'niveau' => $ins->Libelle_AnEtude ?? null,
                'reste' => (int) ($ins->reste_paye ?? 0),
                'paiements' => (int) ($nbPaiements[$e->MATRICULE] ?? 0),
                'actions' => [
                    'fiche' => route('etudiants.fiche', ['matricule' => $e->MATRICULE]),
                    'paiements' => route('paiements.index') . '?matricule=' . urlencode($e->MATRICULE),
                    'parcours' => route('parcours'),
                ],
            ];
        })->values();

        return response()->json([
            'query' => $q,
            'count' => $results->count(),
            'results' => $results,
        ]);
    }
}
