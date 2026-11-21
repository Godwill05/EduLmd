<?php

namespace App\Http\Controllers;

use App\Models\CorbeilleEtudiant;
use App\Support\CorbeilleEtudiantService;
use Illuminate\Http\Request;

/**
 * Corbeille des fiches étudiantes.
 *
 * Remplace la suppression définitive : une fiche retirée est conservée avec
 * l'intégralité de son parcours (inscriptions, notes, paiements) et peut être
 * restaurée telle quelle.
 */
class CorbeilleEtudiantController extends Controller
{
    /** Liste des fiches en corbeille. */
    public function index()
    {
        $entrees = CorbeilleEtudiant::orderByDesc('created_at')->paginate(25);

        return view('corbeille-etudiants', [
            'entrees' => $entrees,
            'total' => CorbeilleEtudiant::count(),
        ]);
    }

    /**
     * Aperçu avant suppression : nombre d'inscriptions, de paiements et de
     * notes concernés. Permet de prévenir l'utilisateur de ce qu'il retire.
     */
    public function apercu(Request $request)
    {
        $donnees = $request->validate([
            'matricule' => 'required|string|max:50',
        ]);

        $resultat = CorbeilleEtudiantService::mettreEnCorbeille(
            $donnees['matricule'],
            motif: '',
            type: 'manuel',
            supprimer: false
        );

        return response()->json([
            'ok' => $resultat['ok'],
            'message' => $resultat['message'],
            'apercu' => $resultat['apercu'],
        ], $resultat['ok'] ? 200 : 404);
    }

    /** Place la fiche en corbeille. */
    public function store(Request $request, string $matricule)
    {
        $donnees = $request->validate([
            'motif' => 'nullable|string|max:255',
            'type' => 'nullable|in:doublon,manuel',
        ]);

        $resultat = CorbeilleEtudiantService::mettreEnCorbeille(
            $matricule,
            $donnees['motif'] ?? '',
            $donnees['type'] ?? 'manuel'
        );

        if ($request->expectsJson()) {
            return response()->json($resultat, $resultat['ok'] ? 200 : 404);
        }

        return redirect()->route('qualite-donnees.index', ['tab' => 'doublons'])
            ->with($resultat['ok'] ? 'succes' : 'echec', $resultat['message']);
    }

    /** Restaure une fiche depuis la corbeille. */
    public function restaurer(Request $request, int $id)
    {
        $resultat = CorbeilleEtudiantService::restaurer($id);

        if ($request->expectsJson()) {
            return response()->json($resultat, $resultat['ok'] ? 200 : 422);
        }

        return redirect()->route('corbeille.index')
            ->with($resultat['ok'] ? 'succes' : 'echec', $resultat['message']);
    }
}
