<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Hierarchie;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Le rôle le plus élevé doit pouvoir tout faire.
 *
 * Constat corrigé : « Super administrateur » (niveau 1) avait zéro
 * permission attachée, et le Gate::before n'existait pas. Il ne pouvait ni
 * gérer les comptes, ni supprimer un étudiant, ni éditer les paramètres —
 * et surtout pas se réparer lui-même, faute de « Gérer les utilisateurs ».
 *
 * Ces tests empêchent la réapparition du problème : que les permissions
 * soient bien en base, et que le niveau 1 passe outre quoi qu'il arrive.
 */
class PermissionsParDefautTest extends TestCase
{
    use DatabaseTransactions;

    private function roleLePlusEleve(): ?Role
    {
        return Role::orderBy('order')->first();
    }

    /** Le rôle de niveau 1 détient toutes les permissions de la base. */
    public function test_le_role_le_plus_eleve_detient_toutes_les_permissions(): void
    {
        $role = $this->roleLePlusEleve();
        $this->assertNotNull($role);

        $this->assertSame(1, $role->niveau(),
            'Le premier rôle par ordre doit avoir le niveau 1.');

        $attendues = Permission::pluck('name')->sort()->values()->all();
        $detenues = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $role->id)
            ->pluck('permissions.name')
            ->sort()->values()->all();

        $this->assertSame($attendues, $detenues,
            'Le rôle de niveau 1 doit détenir toutes les permissions existantes.');
    }

    /** Aucun rôle ne doit rester sans aucune permission. */
    public function test_aucun_role_ne_reste_sans_permission(): void
    {
        foreach (Role::all() as $role) {
            $nb = DB::table('role_has_permissions')->where('role_id', $role->id)->count();

            // « Consultation » est volontairement en lecture seule : on ne
            // lui exige pas de permission d'écriture, mais elle doit avoir
            // accès aux écrans de consultation (permission « Audit »).
            $this->assertGreaterThan(0, $nb,
                "Le rôle « {$role->name} » n'a aucune permission : il est bloqué.");
        }
    }

    /** Le Gate accorde tout au niveau 1, même sans permission en base. */
    public function test_le_niveau_1_passe_outre_les_permissions(): void
    {
        $role = $this->roleLePlusEleve();

        $utilisateur = new User([
            'login' => 'test-super-admin',
            'firstname' => 'Test',
            'lastname' => 'Super',
            'email' => 'test.super@edulmd.test',
            'password' => bcrypt('secret'),
        ]);
        $utilisateur->id = 999999;
        $utilisateur->setRelation('roles', collect([$role]));

        $this->assertSame(1, Hierarchie::niveau($utilisateur));

        // Une permission qui n'existe même pas : le niveau 1 doit passer.
        $this->assertTrue(
            \Illuminate\Support\Facades\Gate::forUser($utilisateur)->allows('une permission inventée'),
            'Le niveau 1 doit être autorisé à tout.'
        );
    }

    /** Le niveau 1 peut créer un administrateur (exigence explicite). */
    public function test_le_niveau_1_peut_creer_un_administrateur(): void
    {
        $roleSuper = $this->roleLePlusEleve();

        $utilisateur = new User([
            'login' => 'test-super-admin-2',
            'firstname' => 'Test',
            'lastname' => 'Super',
            'email' => 'test.super2@edulmd.test',
            'password' => bcrypt('secret'),
        ]);
        $utilisateur->id = 999998;
        $utilisateur->setRelation('roles', collect([$roleSuper]));

        $this->assertTrue(Hierarchie::peutGerer($utilisateur),
            'Le niveau 1 doit pouvoir gérer les comptes.');

        $attribuables = Hierarchie::rolesAttribuables($utilisateur);
        $this->assertGreaterThan(0, $attribuables->count(),
            'Le niveau 1 doit pouvoir attribuer au moins un rôle.');
    }

    /** Un rôle de niveau intermédiaire reste limité : on n'a rien cassé. */
    public function test_un_role_intermediaire_ne_depasse_pas_ses_droits(): void
    {
        $roleScolarite = Role::where('name', 'Scolarité')->first();

        if (! $roleScolarite) {
            $this->markTestSkipped('Rôle Scolarité absent de cette base.');
        }

        $utilisateur = new User([
            'login' => 'test-scolarite',
            'firstname' => 'Test',
            'lastname' => 'Scolarité',
            'email' => 'test.scolarite@edulmd.test',
            'password' => bcrypt('secret'),
        ]);
        $utilisateur->id = 999997;
        $utilisateur->setRelation('roles', collect([$roleScolarite]));

        $this->assertGreaterThan(1, Hierarchie::niveau($utilisateur));

        // La scolarité modifie les inscriptions, mais ne gère pas les comptes.
        $this->assertTrue($utilisateur->can('Modifier les inscriptions'));
        $this->assertFalse($utilisateur->can('Gérer les utilisateurs'),
            'Le niveau 10 ne doit pas gérer les comptes.');
    }
}
