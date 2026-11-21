<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Statut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Actions groupées sur les listes d'étudiants / inscriptions.
 *
 *   - changer le statut d'inscription de N étudiants d'un coup
 *   - exporter la sélection en CSV
 *   - préparer une impression en lot (le rendu est fait côté navigateur
 *     à partir des mêmes données, pour ne pas dépendre d'une file d'attente)
 */
class BulkActionController extends Controller
{
    /** Nombre maximal d'identifiants traités en une fois. */
    private const MAX = 500;

    /**
     * Change le statut de plusieurs inscriptions.
     * POST /api/actions-groupees/statut
     */
    public function changerStatut(Request $request)
    {
        $data = $request->validate([
            'matricules' => 'required|array|min:1|max:' . self::MAX,
            'matricules.*' => 'required|string',
            'statut' => 'required|string',
            'idAnnee' => 'nullable',
            'idFiliere' => 'nullable|string',
        ]);

        $statut = Statut::where('codeStatut', $data['statut'])->first();
        if (!$statut) {
            return response()->json([
                'ok' => false,
                'message' => "Ce statut n'existe pas.",
            ], 422);
        }

        $query = Inscription::whereIn('MATRICULE', $data['matricules']);

        /* Si un contexte (année + filière) est fourni, on ne touche qu'à
           l'inscription concernée : on ne réécrit pas l'historique. */
        if (!empty($data['idAnnee'])) {
            $query->where('Id_Annee', $data['idAnnee']);
        }
        if (!empty($data['idFiliere'])) {
            $query->where('ID_FILIERE', $data['idFiliere']);
        }

        $nb = (clone $query)->count();

        if ($nb === 0) {
            return response()->json([
                'ok' => false,
                'message' => "Aucune inscription ne correspond à cette sélection.",
            ], 422);
        }

        $modifiees = DB::transaction(function () use ($query, $statut) {
            return $query->update([
                'codeStatut' => $statut->codeStatut,
                'Auteur' => auth()->user()->name ?? 'système',
                'Moment' => now(),
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => $modifiees . ' inscription(s) passée(s) au statut « ' . $statut->LibStatut . ' ».',
            'modifiees' => $modifiees,
        ]);
    }

    /**
     * Export CSV de la sélection.
     * POST /api/actions-groupees/export
     *
     * Renvoie un fichier CSV (séparateur « ; », BOM UTF-8 pour Excel FR).
     */
    public function exporter(Request $request)
    {
        $data = $request->validate([
            'matricules' => 'required|array|min:1|max:' . self::MAX,
            'matricules.*' => 'required|string',
            'champs' => 'nullable|array',
        ]);

        $champsDemandes = $data['champs'] ?? [
            'MATRICULE', 'nomEtu', 'prenomsEtu', 'sexeEtu', 'dateNaisEtu',
            'lieuNaisEtu', 'telEtu', 'emailEtu',
        ];

        $autorises = [
            'MATRICULE', 'nomEtu', 'prenomsEtu', 'sexeEtu', 'dateNaisEtu',
            'lieuNaisEtu', 'telEtu', 'emailEtu', 'codeNationalite', 'Observation',
        ];
        $champs = array_values(array_intersect($champsDemandes, $autorises));
        if (!$champs) $champs = ['MATRICULE', 'nomEtu', 'prenomsEtu'];

        $etudiants = Etudiant::whereIn('MATRICULE', $data['matricules'])
            ->orderBy('nomEtu')
            ->get($champs);

        $entetes = [
            'MATRICULE' => 'Matricule',
            'nomEtu' => 'Nom',
            'prenomsEtu' => 'Prénoms',
            'sexeEtu' => 'Sexe',
            'dateNaisEtu' => 'Date de naissance',
            'lieuNaisEtu' => 'Lieu de naissance',
            'telEtu' => 'Téléphone',
            'emailEtu' => 'Email',
            'codeNationalite' => 'Nationalité',
            'Observation' => 'Observations',
        ];

        $nomFichier = 'etudiants-selection-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($etudiants, $champs, $entetes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 : accents corrects dans Excel

            fputcsv($out, array_map(fn($c) => $entetes[$c] ?? $c, $champs), ';');

            foreach ($etudiants as $e) {
                $ligne = [];
                foreach ($champs as $c) {
                    $ligne[] = (string) ($e->{$c} ?? '');
                }
                fputcsv($out, $ligne, ';');
            }

            fclose($out);
        }, $nomFichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Données pour l'impression en lot (JSON, consommé par le navigateur).
     * POST /api/actions-groupees/impression
     */
    public function impression(Request $request)
    {
        $data = $request->validate([
            'matricules' => 'required|array|min:1|max:' . self::MAX,
            'matricules.*' => 'required|string',
            'idAnnee' => 'nullable',
        ]);

        $etudiants = Etudiant::whereIn('MATRICULE', $data['matricules'])
            ->orderBy('nomEtu')
            ->get(['MATRICULE', 'nomEtu', 'prenomsEtu', 'sexeEtu', 'dateNaisEtu', 'lieuNaisEtu', 'telEtu']);

        /* Situation financière, si un contexte d'année est fourni. */
        $finances = collect();
        if (!empty($data['idAnnee'])) {
            $finances = Inscription::whereIn('MATRICULE', $data['matricules'])
                ->where('Id_Annee', $data['idAnnee'])
                ->get(['MATRICULE', 'frais_paye', 'reste_paye', 'ID_FILIERE'])
                ->keyBy('MATRICULE');
        }

        $lignes = $etudiants->map(function ($e) use ($finances) {
            $fin = $finances->get($e->MATRICULE);
            return [
                'matricule' => $e->MATRICULE,
                'nom' => $e->nomEtu,
                'prenoms' => $e->prenomsEtu,
                'sexe' => $e->sexeEtu,
                'dateNais' => $e->dateNaisEtu,
                'lieuNais' => $e->lieuNaisEtu,
                'tel' => $e->telEtu,
                'frais' => $fin ? (int) $fin->frais_paye : null,
                'reste' => $fin ? (int) $fin->reste_paye : null,
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'titre' => 'Liste d\'émargement',
            'genereLe' => now()->format('d/m/Y à H:i'),
            'total' => $lignes->count(),
            'lignes' => $lignes,
        ]);
    }
}
