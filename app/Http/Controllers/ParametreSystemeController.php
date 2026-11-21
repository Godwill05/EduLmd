<?php

namespace App\Http\Controllers;

use App\Models\ParametreSysteme;
use App\Support\Parametres;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametreSystemeController extends Controller
{
    /**
     * Met à jour les constantes système.
     *
     * Seules les clés déjà connues sont mises à jour : le formulaire ne
     * peut pas inventer une constante, et une constante ajoutée à la main
     * en base n'est pas effacée par un enregistrement partiel.
     */
    public function update(Request $request)
    {
        $donnees = $request->validate([
            'parametres' => 'required|array',
            'parametres.*' => 'nullable|string|max:2000',
        ], [], [
            'parametres' => 'paramètres',
        ]);

        $connues = ParametreSysteme::query()->pluck('cle')->all();
        $modifiees = [];

        DB::transaction(function () use ($donnees, $connues, &$modifiees) {
            foreach ($donnees['parametres'] as $cle => $valeur) {
                if (! in_array($cle, $connues, true)) {
                    continue;
                }

                $valeur = is_string($valeur) ? trim($valeur) : null;
                $parametre = ParametreSysteme::query()->where('cle', $cle)->first();

                if ($parametre && (string) $parametre->valeur !== (string) $valeur) {
                    $parametre->update(['valeur' => $valeur]);
                    $modifiees[] = $parametre->libelle;
                }
            }
        });

        Parametres::oublier();

        if ($modifiees === []) {
            return back()->with('warning', 'Aucune modification à enregistrer.');
        }

        $liste = implode(', ', array_slice($modifiees, 0, 4))
            . (count($modifiees) > 4 ? ' et ' . (count($modifiees) - 4) . ' autre(s)' : '');

        return back()->with('succes', count($modifiees) . ' paramètre(s) mis à jour : ' . $liste . '.');
    }
}
