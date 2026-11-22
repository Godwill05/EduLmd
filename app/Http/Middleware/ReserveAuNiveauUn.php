<?php

namespace App\Http\Middleware;

use App\Support\Hierarchie;
use Closure;
use Illuminate\Http\Request;

/**
 * Réserve une route aux comptes de niveau 1 (Super administrateur).
 *
 * Distinct d'une permission : « Gérer les utilisateurs » est accordée aux
 * administrateurs, alors que révoquer un diplôme engage la responsabilité
 * de l'établissement et ne doit pas être délégué. Le niveau est lu sur le
 * rôle (`roles.order`), pas sur son nom : un rôle renommé reste couvert.
 */
class ReserveAuNiveauUn
{
    public function handle(Request $request, Closure $next)
    {
        $utilisateur = $request->user();

        if (! $utilisateur || Hierarchie::niveau($utilisateur) !== 1) {
            abort(403, "Cette opération est réservée au niveau 1 (Super administrateur).");
        }

        return $next($request);
    }
}
