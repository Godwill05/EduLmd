<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\Hierarchie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /** Liste des rôles, leur niveau et leur effectif. */
    public function index()
    {
        $roles = Role::query()->orderBy('order')->orderBy('name')->get();

        $effectifs = DB::table('model_has_roles')
            ->select('role_id', DB::raw('count(*) as total'))
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $nbPermissions = DB::table('role_has_permissions')
            ->select('role_id', DB::raw('count(*) as total'))
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        return view('roles', [
            'roles' => $roles,
            'effectifs' => $effectifs,
            'nbPermissions' => $nbPermissions,
            'monNiveau' => Hierarchie::niveau(auth()->user()),
            'peutGerer' => Hierarchie::peutGerer(auth()->user()),
        ]);
    }

    /** Formulaire de création (affiche dans la liste). */
    public function store(Request $request)
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')],
            'order' => ['required', 'integer', 'min:1', 'max:' . Role::NIVEAU_MAX],
        ], [], ['name' => 'nom', 'order' => 'niveau']);

        // On ne crée pas un rôle plus élevé que soi.
        if ((int) $donnees['order'] <= Hierarchie::niveau(auth()->user())) {
            return back()->withInput()->with('echec',
                'Vous ne pouvez pas créer un rôle de niveau égal ou supérieur au vôtre.');
        }

        Role::create([
            'name' => trim($donnees['name']),
            'guard_name' => 'web',
            'order' => (int) $donnees['order'],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return to_route('roles.index')->with('succes', 'Rôle créé.');
    }

    /** Formulaire de permissions du rôle. */
    public function edit(Role $role)
    {
        $this->autoriserSurRole($role);

        $permissions = Permission::query()
            ->orderBy('type')->orderBy('name')->get();

        return view('role-permissions', [
            'role' => $role,
            'permissions' => $permissions,
            'accordees' => $role->permissions->pluck('name')->all(),
            'monNiveau' => Hierarchie::niveau(auth()->user()),
        ]);
    }

    /** Enregistre les permissions et le niveau d'un rôle. */
    public function update(Request $request, Role $role)
    {
        $this->autoriserSurRole($role);

        $donnees = $request->validate([
            'order' => ['sometimes', 'integer', 'min:1', 'max:' . Role::NIVEAU_MAX],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (isset($donnees['order'])) {
            $nouveau = (int) $donnees['order'];
            // On ne hisse pas un rôle au-dessus de soi, et on ne se
            // rétrograde pas soi-même en baissant son propre niveau.
            if ($nouveau <= Hierarchie::niveau(auth()->user())) {
                return back()->with('echec',
                    'Le niveau doit rester inférieur au vôtre (' . Hierarchie::niveau(auth()->user()) . ').');
            }
            $role->update(['order' => $nouveau]);
        }

        $role->syncPermissions($donnees['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return to_route('roles.index')->with('succes', 'Rôle « ' . $role->name . ' » mis à jour.');
    }

    public function destroy(Role $role)
    {
        $this->autoriserSurRole($role);

        if ($role->niveau() === 1) {
            return back()->with('echec', 'Le rôle de plus haut niveau ne peut pas être supprimé.');
        }

        // On ne supprime pas un rôle encore attribué : les utilisateurs
        // concernés perdraient tous leurs droits d'un coup.
        $effectif = DB::table('model_has_roles')->where('role_id', $role->id)->count();
        if ($effectif > 0) {
            return back()->with('echec',
                "Ce rôle est encore attribué à {$effectif} utilisateur(s). Retirez-le d'abord.");
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return to_route('roles.index')->with('succes', 'Rôle supprimé.');
    }

    /**
     * Attribue ou retire un rôle à un utilisateur.
     *
     * C'est le seul chemin qui modifie les rôles : les deux contrôles
     * (rôle attribuable + cible gérable) y passent obligatoirement.
     */
    public function attribuer(Request $request, User $utilisateur)
    {
        if (! Hierarchie::peutGererCible(auth()->user(), $utilisateur)) {
            abort(403, 'Vous ne pouvez pas modifier les rôles de ce compte.');
        }

        $donnees = $request->validate([
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        $role = $donnees['role_id'] ? Role::find($donnees['role_id']) : null;

        if ($role !== null && ! Hierarchie::peutAttribuer(auth()->user(), $role)) {
            abort(403, 'Vous ne pouvez pas attribuer ce rôle.');
        }

        if ($role === null) {
            $utilisateur->syncRoles([]);
            $message = 'Rôle retiré à ' . $utilisateur->fullname() . '.';
        } else {
            /* On remplace le rôle principal mais on conserve les rôles
               secondaires éventuels (un utilisateur peut cumuler un rôle
               métier et un rôle de contrôle). */
            $utilisateur->syncRoles([$role->name]);
            $message = 'Rôle « ' . $role->name . ' » attribué à ' . $utilisateur->fullname() . '.';

            /* Le rôle porte les permissions : un utilisateur qui n'en a
               aucune se retrouve sans accès à rien. On signale les rôles
               vides pour éviter les surprises. */
            if ($role->permissions()->count() === 0) {
                $message .= ' Ce rôle n\'a aucune permission : pensez à les configurer.';
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('succes', $message);
    }

    /** Un acteur ne gère que les rôles strictement moins élevés que lui. */
    private function autoriserSurRole(Role $role): void
    {
        $acteur = auth()->user();

        if (! Hierarchie::peutGerer($acteur)) {
            abort(403);
        }

        if ($role->niveau() <= Hierarchie::niveau($acteur)) {
            abort(403, 'Ce rôle est au moins aussi élevé que le vôtre.');
        }
    }
}
