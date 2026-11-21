<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Facades\Schema;

/**
 * Rôle applicatif, étendu avec un niveau hiérarchique.
 *
 * `niveau` (colonne `order` de la table `roles`) porte la hiérarchie :
 * 1 = le plus élevé (Super administrateur), les nombres suivants sont
 * des rôles de plus en plus restreints. Seul un utilisateur dont le
 * niveau est strictement inférieur peut distribuer un rôle ou créer un
 * compte, ce qui empêche un administrateur d'élever ses propres droits.
 */
class Role extends SpatieRole
{
    public const NIVEAU_MAX = 100;

    protected $fillable = ['name', 'guard_name', 'order'];

    protected $casts = ['order' => 'integer'];

    /**
     * Le niveau, avec repli quand la colonne n'existe pas encore (base
     * non migrée) : tout le monde est alors au même niveau.
     */
    public function niveau(): int
    {
        if (! Schema::hasColumn('roles', 'order')) {
            return 50;
        }

        return (int) ($this->order ?? 50);
    }

    /** Un rôle est « élevé » s'il est au niveau 1 ou 2. */
    public function estEleve(): bool
    {
        return $this->niveau() <= 2;
    }

    protected static function booted()
    {
        // Supprimer un rôle ne doit pas laisser de liens orphelins.
        static::deleting(function (Role $role) {
            if (Schema::hasTable('model_has_roles')) {
                \Illuminate\Support\Facades\DB::table('model_has_roles')
                    ->where('role_id', $role->id)->delete();
            }
            if (Schema::hasTable('role_has_permissions')) {
                \Illuminate\Support\Facades\DB::table('role_has_permissions')
                    ->where('role_id', $role->id)->delete();
            }
        });
    }
}
