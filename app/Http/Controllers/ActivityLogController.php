<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Journal d'activité lisible.
 *
 * La table `audits` (owen-it/laravel-auditing) contient déjà tout, mais
 * sous forme de JSON illisible. Cette page la rend exploitable :
 *   - traduction des types de modèles en libellés métier
 *   - diff lisible des valeurs (avant → après)
 *   - filtres par utilisateur, événement, période et recherche libre
 */
class ActivityLogController extends Controller
{
    /** Traduction des classes techniques en libellés métier. */
    private const MODELES = [
        'App\\Models\\Etudiant' => 'Étudiant',
        'App\\Models\\Inscription' => 'Inscription',
        'App\\Models\\Paiement' => 'Paiement',
        'App\\Models\\Note' => 'Note',
        'App\\Models\\Evaluation' => 'Évaluation',
        'App\\Models\\Ue' => 'UE',
        'App\\Models\\Ecue' => 'ECUE',
        'App\\Models\\AnneeAcademique' => 'Année académique',
        'App\\Models\\AnneeEtude' => 'Année d\'étude',
        'App\\Models\\Enseignant' => 'Enseignant',
        'App\\Models\\Salle' => 'Salle',
        'App\\Models\\User' => 'Utilisateur',
        'App\\Models\\Cours' => 'Cours',
        'App\\Models\\Prevision' => 'Prévision',
    ];

    private const EVENEMENTS = [
        'created' => ['Création', 'ok'],
        'updated' => ['Modification', 'info'],
        'deleted' => ['Suppression', 'bad'],
        'restored' => ['Restauration', 'ok'],
    ];

    public function index(Request $request)
    {
        $userId = $request->filled('user') ? (int) $request->input('user') : null;
        $event = $request->filled('event') ? $request->input('event') : null;
        $modele = $request->filled('modele') ? $request->input('modele') : null;
        $du = $request->filled('du') ? $request->input('du') : null;
        $au = $request->filled('au') ? $request->input('au') : null;
        $q = trim((string) $request->input('q', ''));

        $query = Audit::query();

        if ($userId) $query->where('user_id', $userId);
        if ($event && isset(self::EVENEMENTS[$event])) $query->where('event', $event);
        if ($modele && isset(self::MODELES[$modele])) $query->where('auditable_type', $modele);
        if ($du) $query->whereDate('created_at', '>=', $du);
        if ($au) $query->whereDate('created_at', '<=', $au);
        if ($q !== '') {
            $term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('auditable_id', 'like', $term)
                    ->orWhere('url', 'like', $term);
            });
        }

        $audits = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // Utilisateurs concernés (pour le filtre) — on ne charge que les
        // identifiants réellement présents dans le journal.
        $userIds = Audit::query()
            ->whereNotNull('user_id')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        $utilisateurs = User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Résumé par événement (badges du haut)
        $resume = Audit::query()
            ->select('event', DB::raw('COUNT(*) as total'))
            ->groupBy('event')
            ->pluck('total', 'event');

        // Mise en forme lisible : libellé du modèle + diff des valeurs
        $audits->getCollection()->transform(function ($a) {
            $a->modele_libelle = self::MODELES[$a->auditable_type] ?? class_basename($a->auditable_type);
            $liste = self::EVENEMENTS[$a->event] ?? [$a->event, 'info'];
            $a->evenement_libelle = $liste[0];
            $a->evenement_ton = $liste[1];
            /* Icône et date précalculées : la vue reste sans logique. */
            $a->icone = match ($a->event) {
                'created' => 'add-line',
                'deleted' => 'delete-bin-line',
                'restored' => 'refresh-line',
                default => 'edit-line',
            };
            $a->moment_lisible = $a->created_at
                ? \Carbon\Carbon::parse($a->created_at)->format('d/m/Y à H:i')
                : '—';

            $a->diff = $this->construireDiff($a);
            $a->user_nom = $a->user_id
                ? optional(User::find($a->user_id))->name
                : null;
            return $a;
        });

        return view('journal-activite', compact(
            'audits', 'utilisateurs', 'resume', 'userId', 'event', 'modele',
            'du', 'au', 'q'
        ))->with([
            'modeles' => self::MODELES,
            'evenements' => self::EVENEMENTS,
        ]);
    }

    /**
     * Construit une liste lisible de changements à partir des colonnes
     * JSON old_values / new_values.
     */
    private function construireDiff(Audit $audit): array
    {
        $old = json_decode((string) $audit->old_values, true) ?: [];
        $new = json_decode((string) $audit->new_values, true) ?: [];

        /* Champs techniques à masquer : ils polluent la lecture. */
        $ignorer = ['updated_at', 'created_at', 'Moment', 'Auteur', 'version', 'password', 'remember_token'];

        $cles = array_unique(array_merge(array_keys($old), array_keys($new)));
        $diff = [];

        foreach ($cles as $cle) {
            if (in_array($cle, $ignorer, true)) continue;

            $avant = $old[$cle] ?? null;
            $apres = $new[$cle] ?? null;

            if ($avant === $apres) continue;

            $diff[] = [
                'champ' => $cle,
                'avant' => $this->formater($avant),
                'apres' => $this->formater($apres),
            ];

            /* On borne l'affichage : au-delà de 12 changements, la ligne
               devient illisible et le détail est masquable. */
            if (count($diff) >= 12) break;
        }

        return $diff;
    }

    private function formater($valeur)
    {
        if ($valeur === null) return '—';
        if (is_bool($valeur)) return $valeur ? 'oui' : 'non';
        if (is_array($valeur)) return json_encode($valeur, JSON_UNESCAPED_UNICODE);
        $s = (string) $valeur;
        return $s === '' ? '—' : (mb_strlen($s) > 60 ? mb_substr($s, 0, 57) . '…' : $s);
    }
}
