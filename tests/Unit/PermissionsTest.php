<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Régression : sans Spatie\Permission\PermissionServiceProvider dans
 * config/app.php, tous les Gate::allows() renvoyaient false et les routes
 * protégées par « can:… » répondaient 403 à tout le monde.
 */
class PermissionsTest extends TestCase
{
    public function test_le_prestataire_spatie_est_charge(): void
    {
        $charges = array_keys(app()->getLoadedProviders());

        $this->assertContains(
            'Spatie\Permission\PermissionServiceProvider',
            $charges,
            'Permissions inutilisables : PermissionServiceProvider non chargé.'
        );
    }

    public function test_un_utilisateur_avec_permission_passe_le_gate(): void
    {
        $utilisateur = User::where('email', 'audit@edulmd.test')->first();
        if (!$utilisateur) {
            $this->markTestSkipped("Compte d'audit absent.");
        }

        $this->assertTrue(
            Gate::forUser($utilisateur)->allows('Gérer les utilisateurs'),
            'Le Gate doit honorer les permissions Spatie.'
        );
    }
}
