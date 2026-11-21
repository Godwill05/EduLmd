<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

/**
 * Règles de hiérarchie entre utilisateurs.
 *
 * Le principe : « plus le niveau est petit, plus le rôle est élevé ».
 * Un utilisateur ne peut agir que sur des comptes strictement moins
 * élevés que lui. Un administrateur ne peut donc ni se promouvoir, ni
 * désactiver un pair, ni toucher au compte du niveau 1.
 */
class Hierarchie
{
    /** Niveau de l'utilisateur (le plus élevé de ses rôles). */
    public static function niveau(User $user): int
    {
        if (! $user->relationLoaded('roles')) {
            $user->load('roles');
        }

        if ($user->roles->isEmpty()) {
            return Role::NIVEAU_MAX + 1;
        }

        return (int) $user->roles->map(fn (Role $r) => $r->niveau())->min();
    }

    /** L'utilisateur peut-il gérer (créer/modifier) les comptes ? */
    public static function peutGerer(User $user): bool
    {
        return $user->can('Gérer les utilisateurs');
    }

    /**
     * L'utilisateur peut-il agir sur la cible ?
     *
     * Il faut la permission de gérer les utilisateurs, un niveau
     * strictement meilleur que celui de la cible, et ne pas être la cible
     * de sa propre action.
     */
    public static function peutGererCible(User $user, User $cible): bool
    {
        if (! self::peutGerer($user)) {
            return false;
        }

        if ($user->getKey() === $cible->getKey()) {
            return false;
        }

        return self::niveau($user) < self::niveau($cible);
    }

    /**
     * Rôles que l'utilisateur peut attribuer : ceux strictement moins
     * élevés que son niveau (on ne distribue pas son propre rang).
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    public static function rolesAttribuables(User $user)
    {
        $sonNiveau = self::niveau($user);

        return Role::query()->orderBy('order')->orderBy('name')->get()
            ->filter(fn (Role $r) => $r->niveau() > $sonNiveau)
            ->values();
    }

    /**
     * Un rôle peut-il être attribué par cet utilisateur ?
     */
    public static function peutAttribuer(User $user, ?Role $role): bool
    {
        if (! self::peutGerer($user)) {
            return false;
        }

        if ($role === null) {
            return true; // retirer un rôle reste permis par la permission
        }

        /* Le niveau 1 n'est jamais attribuable : même un super
           administrateur ne crée pas d'égal, sinon deux comptes au sommet
           pourraient se rétrograder mutuellement. */
        if ($role->niveau() === 1) {
            return false;
        }

        return $role->niveau() > self::niveau($user);
    }
}
