<?php

namespace App\Providers;

use App\Support\Hierarchie;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Models\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        /*
         * Filet de sécurité du niveau 1.
         * ------------------------------------------------------------------
         * Constat sur la base de production : le rôle « Super administrateur »
         * (niveau 1) avait zéro permission attachée. Il ne pouvait donc rien
         * faire — et surtout pas se réparer, faute de « Gérer les utilisateurs ».
         *
         * Ce Gate::before garantit qu'un compte de niveau 1 possède toujours
         * tous les droits, même si l'attribution des permissions est
         * incomplète. Un compte de niveau 1 ne peut donc plus jamais être
         * enfermé dehors par un oubli de configuration.
         *
         * Pourquoi un verrou en plus de la migration de réparation ? Parce
         * qu'une future permission ajoutée sans être rattachée au rôle
         * laisserait à nouveau le niveau 1 sans accès. Ici, la question est
         * tranchée en une fois : niveau 1 = tous les droits.
         */
        Gate::before(function ($user, $ability) {
            // Les invités n'ont pas de rôles : on ne se prononce pas.
            if (! $user) {
                return null;
            }

            // 1 = le plus élevé. On s'appuie sur le niveau, pas sur le nom
            // du rôle : un rôle renommé reste couvert.
            return Hierarchie::niveau($user) === 1 ? true : null;
        });
    }
}
