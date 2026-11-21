<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Extrait les entrées scalaires de la requête sous forme de variables
     * locales, comme le faisait `extract(request()->except('_token'))`.
     *
     * Pourquoi un équivalent maison ?
     *  - extract() crée une variable pour CHAQUE paramètre reçu, y compris
     *    « this », « request » ou « app » : un utilisateur pouvait écraser
     *    des variables internes du contrôleur en ajoutant ?this=… à l'URL.
     *  - Les tableaux et fichiers ne sont pas extraits, pour éviter de
     *    propager des structures inattendues jusqu'aux requêtes SQL.
     *  - _token, _method et les clés réservées sont ignorés.
     *
     * @param  array<int, string>  $sauf  Paramètres supplémentaires à exclure
     * @return array<string, scalar|null>
     */
    protected function extraireEntrees(array $sauf = []): array
    {
        $reservees = array_merge(['_token', '_method', 'this', 'request', 'app'], $sauf);
        $sortie = [];

        foreach (request()->all() as $cle => $valeur) {
            if (!is_string($cle) || in_array($cle, $reservees, true)) {
                continue;
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $cle)) {
                continue;
            }
            if (is_array($valeur) || is_object($valeur)) {
                continue;
            }
            /* Les contrôleurs interpolent ces variables dans des requêtes SQL
               brutes : on refuse ici les valeurs capables d'en sortir. La
               validation a lieu une seule fois, à l'entrée. */
            $sortie[$cle] = is_string($valeur)
                ? \App\Support\GardeSql::valeur($valeur, $cle)
                : $valeur;
        }

        return $sortie;
    }

    /**
     * Point d'entrée public pour les closures de routes, qui n'ont pas de $this.
     *
     * @param  array<int, string>  $sauf
     * @return array<string, scalar|null>
     */
    public function extraireEntreesPourRoutes(array $sauf = []): array
    {
        return $this->extraireEntrees($sauf);
    }
}
