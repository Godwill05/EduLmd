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
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get(['id', 'firstname', 'lastname', 'email']);

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

            /* Les notes forment 28 000 des 29 000 lignes du journal. Un
               « Note #25114 » ne dit rien : on va chercher l'étudiant,
               l'ECUE et le type d'évaluation pour que la ligne soit
               lisible par un responsable pédagogique. */
            $a->contexte = $this->contexteLisible($a);

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
     * Contexte métier d'une ligne du journal.
     *
     * Pour une note (`obtenir.idObtenir`), on remonte jusqu'à l'étudiant
     * et l'évaluation : « FATIOU Alao · 1MAI1201 · Rattrapage ».
     * Pour les autres objets, on renvoie null : leur identifiant suffit.
     *
     * Les requêtes sont mises en cache par page (une seule par
     * identifiant distinct), et une note supprimée depuis ne provoque pas
     * d'erreur : on affiche alors ce qu'on peut.
     */
    private function contexteLisible(Audit $audit): ?string
    {
        if ($audit->auditable_type !== 'App\\Models\\Note') {
            return null;
        }

        $cache = $this->cacheContexte;

        if (array_key_exists($audit->auditable_id, $cache)) {
            return $cache[$audit->auditable_id];
        }

        $note = DB::table('obtenir')
            ->where('idObtenir', $audit->auditable_id)
            ->select('numeroInscription', 'numeroEval')
            ->first();

        if (! $note) {
            return $cache[$audit->auditable_id] = null;
        }

        $etudiant = DB::table('etudiant')
            ->where('MATRICULE', $note->numeroInscription)
            ->select('nomEtu', 'prenomsEtu')
            ->first();

        $evaluation = DB::table('evaluation')
            ->where('numeroEval', $note->numeroEval)
            ->select('codeEcu', 'codTypEval')
            ->first();

        $parties = [];
        if ($etudiant) {
            $parties[] = trim($etudiant->nomEtu . ' ' . $etudiant->prenomsEtu);
        } else {
            $parties[] = $note->numeroInscription;
        }
        if ($evaluation && $evaluation->codeEcu) {
            $parties[] = $evaluation->codeEcu;
        }
        if ($evaluation && $evaluation->codTypEval) {
            $parties[] = $evaluation->codTypEval;
        }

        return $cache[$audit->auditable_id] = implode(' · ', $parties);
    }

    /** Cache du contexte, le temps d'une page. */
    private array $cacheContexte = [];

    /**
     * Export CSV du journal filtré.
     *
     * Pourquoi c'est indispensable : en cas de contestation d'une note,
     * l'établissement doit pouvoir produire une trace datée. Un écran
     * paginé de 30 lignes ne suffit pas — il faut le fichier.
     *
     * Le même jeu de filtres que l'écran est appliqué, et la limite est
     * haute mais bornée (50 000 lignes) pour ne pas faire tomber le
     * serveur sur une année entière de saisie de notes.
     */
    public function exporter(Request $request)
    {
        $query = $this->requeteFiltree($request)->limit(50000);

        $nom = 'journal-activite_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $sortie = fopen('php://output', 'w');

            // BOM UTF-8 : sans lui, Excel français affiche « Ã© » pour « é ».
            fwrite($sortie, "\xEF\xBB\xBF");

            fputcsv($sortie, [
                'Date', 'Utilisateur', 'Événement', 'Objet', 'Identifiant',
                'Contexte', 'Champ', 'Avant', 'Après',
            ], ';');

            /* La table `users` de cette base n'a pas de colonne `name` :
               le nom se compose de `firstname` et `lastname`. Utiliser
               pluck('name') levait « Undefined property: name ». */
            $noms = User::query()
                ->get(['id', 'firstname', 'lastname'])
                ->mapWithKeys(fn ($u) => [$u->id => trim($u->firstname . ' ' . $u->lastname)])
                ->all();

            $query->chunk(500, function ($lignes) use ($sortie, $noms) {
                foreach ($lignes as $audit) {
                    $evenement = self::EVENEMENTS[$audit->event][0] ?? $audit->event;
                    $modele = self::MODELES[$audit->auditable_type] ?? class_basename($audit->auditable_type);
                    $contexte = $this->contexteLisible($audit) ?? '';

                    $diff = $this->construireDiff($audit);

                    // Une ligne par champ modifié : c'est ce qu'un contrôle
                    // veut lire (« la note est passée de 5 à 05 le 18/08 »).
                    if (empty($diff)) {
                        fputcsv($sortie, [
                            $audit->created_at, $noms[$audit->user_id] ?? 'Système',
                            $evenement, $modele, $audit->auditable_id,
                            $contexte, '', '', '',
                        ], ';');
                        continue;
                    }

                    foreach ($diff as $champ) {
                        fputcsv($sortie, [
                            $audit->created_at, $noms[$audit->user_id] ?? 'Système',
                            $evenement, $modele, $audit->auditable_id,
                            $contexte, $champ['champ'], $champ['avant'], $champ['apres'],
                        ], ';');
                    }
                }
            });

            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Applique les filtres de la requête, sans pagination. */
    private function requeteFiltree(Request $request)
    {
        $userId = $request->filled('user') ? (int) $request->input('user') : null;
        $event = $request->filled('event') ? $request->input('event') : null;
        $modele = $request->filled('modele') ? $request->input('modele') : null;
        $du = $request->filled('du') ? $request->input('du') : null;
        $au = $request->filled('au') ? $request->input('au') : null;
        $q = trim((string) $request->input('q', ''));

        return Audit::query()
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($event && isset(self::EVENEMENTS[$event]), fn ($query) => $query->where('event', $event))
            ->when($modele && isset(self::MODELES[$modele]), fn ($query) => $query->where('auditable_type', $modele))
            ->when($du, fn ($query) => $query->whereDate('created_at', '>=', $du))
            ->when($au, fn ($query) => $query->whereDate('created_at', '<=', $au))
            ->when($q !== '', function ($query) use ($q) {
                $term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
                $query->where(function ($sub) use ($term) {
                    $sub->where('auditable_id', 'like', $term)
                        ->orWhere('url', 'like', $term);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
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
