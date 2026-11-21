<?php

namespace App\Support;

use App\Models\CorbeilleEtudiant;
use App\Models\Etudiant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Mise en corbeille et restauration d'une fiche étudiante.
 * ----------------------------------------------------------------------
 * Une fiche étudiante n'est jamais supprimée réellement : ses données sont
 * copiées dans `corbeille_etudiants` (JSON), puis retirées des 8 tables qui
 * la référencent. La restauration remet chaque ligne exactement comme elle
 * était, identifiants d'origine compris.
 *
 * Ordre de suppression (du plus dépendant au moins dépendant) :
 *   obtenir → inscriptions → paiements → prévisions → recap_annuel
 *   → recap_sem → recap_ue → repartir → etudiant
 */
class CorbeilleEtudiantService
{
    /** Tables liées au matricule, avec la colonne qui porte la valeur. */
    public const TABLES_LIEES = [
        'inscriptions' => 'MATRICULE',
        'paiements' => 'MATRICULE',
        'previsions' => 'MATRICULE',
        'previsions_1' => 'MATRICULE',
        'recap_annuel' => 'MATRICULE',
        'recap_sem' => 'MATRICULE',
        'recap_ue' => 'MATRICULE',
        'repartir' => 'Matricule',
    ];

    /**
     * Copie toutes les données d'une fiche et la place en corbeille.
     *
     * @param  bool  $supprimer  false = simulation (aperçu avant suppression)
     * @return array{ok:bool,message:string,apercu:array,corbeille?:CorbeilleEtudiant}
     */
    public static function mettreEnCorbeille(
        string $matricule,
        string $motif = '',
        string $type = 'manuel',
        bool $supprimer = true
    ): array {
        $etudiant = Etudiant::where('MATRICULE', $matricule)->first();

        if (! $etudiant) {
            return ['ok' => false, 'message' => 'Cet étudiant est introuvable.', 'apercu' => []];
        }

        $donnees = [
            'etudiant' => (array) $etudiant->getAttributes(),
            'tables' => [],
            'obtenir' => [],
        ];

        // Lignes des tables directement liées au matricule
        foreach (self::TABLES_LIEES as $table => $colonne) {
            if (! \Schema::hasTable($table)) {
                continue;
            }
            $donnees['tables'][$table] = DB::table($table)
                ->where($colonne, $matricule)
                ->get()
                ->map(fn ($ligne) => (array) $ligne)
                ->all();
        }

        /* Notes : la table `obtenir` référence l'étudiant par son matricule
           dans la colonne `numeroInscription` (il n'y a pas de table de
           liaison). On récupère donc directement sur cette valeur. */
        if (\Schema::hasTable('obtenir')) {
            $donnees['obtenir'] = DB::table('obtenir')
                ->where('numeroInscription', $matricule)
                ->get()
                ->map(fn ($ligne) => (array) $ligne)
                ->all();
        }

        $apercu = [
            'inscriptions' => count($donnees['tables']['inscriptions'] ?? []),
            'paiements' => count($donnees['tables']['paiements'] ?? []),
            'previsions' => count($donnees['tables']['previsions'] ?? []),
            'notes' => count($donnees['obtenir']),
            'repartition' => count($donnees['tables']['repartir'] ?? []),
        ];

        if (! $supprimer) {
            return [
                'ok' => true,
                'message' => 'Aperçu de la mise en corbeille.',
                'apercu' => $apercu,
            ];
        }

        $corbeille = DB::transaction(function () use ($etudiant, $matricule, $motif, $type, $donnees, $apercu) {
            $ligne = CorbeilleEtudiant::create([
                'MATRICULE' => $matricule,
                'nom_complet' => trim($etudiant->nomEtu.' '.$etudiant->prenomsEtu),
                'motif' => $motif !== '' ? $motif : null,
                'type' => in_array($type, ['doublon', 'manuel'], true) ? $type : 'manuel',
                'supprime_par' => Auth::user()->login ?? Auth::user()->email ?? null,
                'donnees' => $donnees,
                'nb_inscriptions' => $apercu['inscriptions'],
                'nb_paiements' => $apercu['paiements'],
            ]);

            if (\Schema::hasTable('obtenir')) {
                DB::table('obtenir')->where('numeroInscription', $matricule)->delete();
            }

            foreach (self::TABLES_LIEES as $table => $colonne) {
                if (! \Schema::hasTable($table)) {
                    continue;
                }
                DB::table($table)->where($colonne, $matricule)->delete();
            }

            DB::table('etudiant')->where('MATRICULE', $matricule)->delete();

            return $ligne;
        });

        $message = sprintf(
            'Fiche %s mise en corbeille (%d inscription(s), %d paiement(s), %d note(s)).',
            $matricule,
            $apercu['inscriptions'],
            $apercu['paiements'],
            $apercu['notes']
        );

        return ['ok' => true, 'message' => $message, 'apercu' => $apercu, 'corbeille' => $corbeille];
    }

    /**
     * Restaure une fiche depuis la corbeille, à l'identique.
     *
     * @return array{ok:bool,message:string}
     */
    public static function restaurer(int $id): array
    {
        $entree = CorbeilleEtudiant::find($id);

        if (! $entree) {
            return ['ok' => false, 'message' => 'Entrée de corbeille introuvable.'];
        }

        $donnees = $entree->donnees ?? [];
        $matricule = $entree->MATRICULE;

        if (Etudiant::where('MATRICULE', $matricule)->exists()) {
            return [
                'ok' => false,
                'message' => 'Un étudiant portant le matricule '.$matricule.' existe déjà : restauration annulée pour éviter un doublon.',
            ];
        }

        DB::transaction(function () use ($donnees, $entree) {
            self::reinserer('etudiant', [$donnees['etudiant'] ?? []]);

            foreach (($donnees['tables'] ?? []) as $table => $lignes) {
                self::reinserer($table, $lignes);
            }

            self::reinserer('obtenir', $donnees['obtenir'] ?? []);

            $entree->delete();
        });

        return ['ok' => true, 'message' => 'Fiche '.$matricule.' restaurée avec son parcours.'];
    }

    /**
     * Réinsère des lignes brutes, identifiants compris.
     */
    protected static function reinserer(string $table, array $lignes): void
    {
        if (! $lignes || ! \Schema::hasTable($table)) {
            return;
        }

        /* Découpage en lots : 8 tables × plusieurs centaines de lignes
           dépasseraient la limite de variables liées d'une requête. */
        foreach (array_chunk($lignes, 100) as $lot) {
            DB::table($table)->insert($lot);
        }
    }

    /** Nombre de fiches disponibles en corbeille. */
    public static function nombreEnCorbeille(): int
    {
        return \Schema::hasTable('corbeille_etudiants') ? CorbeilleEtudiant::count() : 0;
    }
}
