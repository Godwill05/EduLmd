<?php

namespace App\Http\Controllers;

use App\Support\EmploiDuTemps;
use Illuminate\Http\Request;

/**
 * Emploi du temps : grille hebdomadaire des créneaux d'une classe.
 *
 * Ce que cet écran apporte : `prog_cours` contient déjà les créneaux, et
 * une saisie existe, mais il n'y avait aucun moyen de les relire sous la
 * forme dont un étudiant a besoin — une grille « lundi / mardi / … » avec
 * l'heure, l'UE, l'ECUE et la salle. On la produit ici, et on l'imprime.
 *
 * Périmètre assumé : pas de détection de conflits (salle occupée deux fois
 * au même moment, deux cours pour une même classe à la même heure). C'est
 * un choix explicite : la grille montre ce qui est saisi, elle ne censure
 * rien. Un conflit reste donc visible à l'œil, ce qui est préférable à un
 * blocage silencieux de la saisie.
 */
class EmploiDuTempsController extends Controller
{
    public function index(Request $request)
    {
        $classes = EmploiDuTemps::classes();

        // Classe demandée, sinon la première de la liste : l'écran ne doit
        // jamais s'ouvrir vide quand des créneaux existent.
        $classeChoisie = $request->filled('classe')
            ? (int) $request->input('classe')
            : optional($classes[0] ?? null)->id_S_Gr_Spe;

        $du = $this->date($request->input('du'));
        $au = $this->date($request->input('au'));

        $grille = $classeChoisie
            ? EmploiDuTemps::grille($classeChoisie, $du, $au)
            : ['depart' => null, 'arrivee' => null, 'grille' => [], 'total' => 0, 'du' => $du, 'au' => $au];

        $classe = null;
        foreach ($classes as $c) {
            if ((int) $c->id_S_Gr_Spe === (int) $classeChoisie) {
                $classe = $c;
                break;
            }
        }

        return view('emploi-du-temps', [
            'classes' => $classes,
            'classeChoisie' => $classeChoisie,
            'classe' => $classe,
            'du' => $du,
            'au' => $au,
            'grille' => $grille,
        ]);
    }

    /**
     * Version imprimable : même grille, sans navigation ni couleurs
     * d'écran, sur une page paysage. C'est ce qu'on affiche sur la porte
     * de la salle et ce qu'on distribue aux étudiants.
     */
    public function imprimer(Request $request)
    {
        $classeChoisie = $request->filled('classe') ? (int) $request->input('classe') : null;
        $du = $this->date($request->input('du'));
        $au = $this->date($request->input('au'));

        $grille = $classeChoisie
            ? EmploiDuTemps::grille($classeChoisie, $du, $au)
            : ['depart' => null, 'arrivee' => null, 'grille' => [], 'total' => 0, 'du' => $du, 'au' => $au];

        $classe = null;
        foreach (EmploiDuTemps::classes() as $c) {
            if ((int) $c->id_S_Gr_Spe === (int) $classeChoisie) {
                $classe = $c;
                break;
            }
        }

        if (! $classe) {
            abort(404, 'Classe introuvable.');
        }

        // Établissement : les données viennent de la table `ufr`, comme
        // partout ailleurs (en-têtes d'attestations, page À propos).
        $etablissement = \Illuminate\Support\Facades\DB::table('ufr')->first();

        return view('emploi-du-temps-impression', [
            'classe' => $classe,
            'grille' => $grille,
            'du' => $du,
            'au' => $au,
            'etablissement' => $etablissement,
        ]);
    }

    /** Une date valide, sinon null. Évite qu'une saisie libre casse la requête. */
    private function date($valeur): ?string
    {
        if (empty($valeur)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($valeur)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
