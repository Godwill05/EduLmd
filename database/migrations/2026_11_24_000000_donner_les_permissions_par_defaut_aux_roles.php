<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Réparation des permissions par défaut des rôles.
 * ---------------------------------------------------------------------
 * Constat mesuré sur la base de production importée :
 *
 *   rôle                   niveau   permissions
 *   Super administrateur        1   0  ← le rôle le plus élevé, bloqué
 *   Administrateur (audit)      2   13
 *   Administrateur              2   0
 *   Scolarité                  10   0
 *   Enseignant                 20   0
 *   Consultation               30   0
 *
 * Le rôle de niveau 1 ne pouvait donc RIEN faire : ni gérer les comptes,
 * ni supprimer un étudiant, ni éditer les paramètres. Il ne pouvait même
 * pas se réparer lui-même puisqu'il n'avait pas « Gérer les utilisateurs ».
 *
 * Cette migration donne à chaque rôle un socle cohérent avec son niveau.
 * Elle est idempotente : les permissions déjà présentes ne sont pas
 * dupliquées, et un rôle qui a été réglé à la main n'est pas écrasé.
 *
 * Le rôle de niveau 1 reçoit TOUTES les permissions existantes, y compris
 * celles ajoutées plus tard : voir App\Support\Hierarchie::synchroniser().
 */
class DonnerLesPermissionsParDefautAuxRoles extends Migration
{
    /**
     * Socle par nom de rôle.
     *
     * Volontairement limité : on donne le minimum utile pour que chaque
     * métier travaille, pas un « tout permis ».
     */
    private const SOCLE = [
        'Super administrateur' => '*',

        'Administrateur' => [
            'Gérer les utilisateurs',
            'Gérer les universités',
            'Gérer les établissements',
            'Modifier les paramètres système',
            'Audit',
            'Supprimer les étudiants',
            'Supprimer les enseignants',
            'Supprimer les inscriptions',
            'Modifier les inscriptions',
            'Supprimer les notes',
            'Créer un domaine',
            'Modifier un domaine',
            'Supprimer un domaine',
        ],

        // La scolarité manipule les inscriptions et les notes, pas les comptes.
        'Scolarité' => [
            'Modifier les inscriptions',
            'Supprimer les inscriptions',
            'Supprimer les notes',
        ],

        // L'enseignant saisit et corrige ses notes.
        'Enseignant' => [
            'Supprimer les notes',
        ],

        /* Consultation : lecture seule. Sans la permission « Audit » (qui
           ouvre les écrans de consultation et le journal), ce rôle ne
           pouvait ouvrir aucune page — il était bloqué, pas « en lecture
           seule ». On lui donne donc la lecture, et rien d'autre. */
        'Consultation' => [
            'Audit',
        ],
    ];

    public function up()
    {
        $maintenant = now();

        $permissions = DB::table('permissions')->get();

        foreach (DB::table('roles')->orderBy('order')->get() as $role) {
            $socle = self::SOCLE[$role->name] ?? null;

            if ($socle === null) {
                continue;
            }

            if ($socle === '*') {
                $aDonner = $permissions;
            } else {
                $aDonner = $permissions->whereIn('name', $socle);
            }

            foreach ($aDonner as $permission) {
                $deja = DB::table('role_has_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (! $deja) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }

        /* Le niveau 1 doit aussi recevoir les permissions créées plus tard.
           La synchronisation ci-dessous s'appuie sur le niveau (order = 1)
           et non sur le nom : un rôle renommé reste couvert. */
        $premier = DB::table('roles')->orderBy('order')->first();

        if ($premier) {
            foreach ($permissions as $permission) {
                $deja = DB::table('role_has_permissions')
                    ->where('role_id', $premier->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (! $deja) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $premier->id,
                    ]);
                }
            }
        }

        /* Trace dans le journal d'audit de Laravel : cette migration change
           les droits d'accès, elle doit être datée et attribuable. */
        if (DB::getSchemaBuilder()->hasTable('migrations')) {
            // rien de plus : la table `migrations` enregistre déjà l'exécution.
        }
    }

    /**
     * On ne retire rien : supprimer des permissions accordées pourrait
     * bloquer un administrateur en production. La réparation est
     * volontairement non réversible.
     */
    public function down()
    {
        // volontairement vide
    }
}
