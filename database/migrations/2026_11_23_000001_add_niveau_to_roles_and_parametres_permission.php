<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hiérarchie des rôles.
 *
 * La colonne `order` porte le niveau : 1 = le plus élevé. Elle sert à
 * empêcher qu'un administrateur distribue des rôles aussi élevés que le
 * sien (voir App\Support\Hierarchie).
 *
 * La migration ajoute aussi la permission « Modifier les paramètres
 * système » et les rôles de départ si la base n'en a qu'un.
 */
class AddNiveauToRolesAndParametresPermission extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('roles', 'order')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedInteger('order')->default(50)->after('guard_name');
            });
        }

        $maintenant = now();

        // Permission dédiée à l'édition de la page « À propos ».
        $permissionId = DB::table('permissions')
            ->where('name', 'Modifier les paramètres système')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Modifier les paramètres système',
                'guard_name' => 'web',
                'type' => 'parametres',
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }

        /* Le rôle d'administration existant doit pouvoir éditer les
           paramètres, sinon personne ne le peut après la migration. */
        $roleAdmin = DB::table('roles')->where('name', 'Administrateur (audit)')->value('id')
            ?? DB::table('roles')->orderBy('id')->value('id');

        if ($roleAdmin) {
            $deja = DB::table('role_has_permissions')
                ->where('role_id', $roleAdmin)->where('permission_id', $permissionId)->exists();

            if (! $deja) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleAdmin,
                ]);
            }
        }

        if (DB::table('roles')->count() <= 1) {
            $depart = [
                ['Super administrateur', 1],
                ['Administrateur', 2],
                ['Scolarité', 10],
                ['Enseignant', 20],
                ['Consultation', 30],
            ];

            foreach ($depart as [$nom, $niveau]) {
                if (! DB::table('roles')->where('name', $nom)->exists()) {
                    DB::table('roles')->insert([
                        'name' => $nom,
                        'guard_name' => 'web',
                        'order' => $niveau,
                        'created_at' => $maintenant,
                        'updated_at' => $maintenant,
                    ]);
                }
            }
        }

        // Le rôle d'administration existant est hissé au niveau 2.
        DB::table('roles')->where('name', 'Administrateur (audit)')
            ->where('order', 50)->update(['order' => 2]);
    }

    public function down()
    {
        if (Schema::hasColumn('roles', 'order')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('order');
            });
        }

        DB::table('permissions')->where('name', 'Modifier les paramètres système')->delete();
    }
}
