<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grille hebdomadaire de l'emploi du temps d'une classe.
 *
 * Source de vérité : la table `prog_cours`, qui contient déjà les créneaux
 * (date de début, date de fin, heures, ECUE, salle). Cette classe ne
 * réinvente pas le modèle : elle lit ce que la saisie de cours enregistre
 * et le met en forme de grille.
 *
 * Pourquoi passer par les dates plutôt que par un jour de la semaine :
 * `prog_cours` ne stocke pas « lundi », mais un intervalle de dates
 * (`datedebut` → `datefin`). Une séance du 13 au 29 juillet n'a pas lieu
 * chaque lundi : c'est un bloc. On calcule donc, pour chaque créneau, les
 * jours de la semaine qu'il couvre réellement, et on le répète sur la
 * grille autant de fois que son intervalle contient ce jour-là.
 *
 * Le résultat est un tableau indexé par jour (1 = lundi … 7 = dimanche)
 * puis par heure de début. Deux créneaux à la même heure un même jour
 * apparaissent côte à côte — on n'écrase rien.
 */
class EmploiDuTemps
{
    /** Jours de la semaine, dans l'ordre où la grille les affiche. */
    public const JOURS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    /**
     * Classes ayant au moins un créneau, pour le sélecteur.
     *
     * @return array<int, object>
     */
    public static function classes(): array
    {
        if (! Schema::hasTable('prog_cours')) {
            return [];
        }

        return DB::table('prog_cours as p')
            ->join('attr_ue_classe as a', 'p.Id_Attr_ue_Sem', '=', 'a.Id_Attr_ue_Sem')
            ->join('classe as c', 'a.id_S_Gr_Spe', '=', 'c.id_S_Gr_Spe')
            ->select('c.id_S_Gr_Spe', 'c.Nom_Classe')
            ->distinct()
            ->orderBy('c.Nom_Classe')
            ->get()
            ->all();
    }

    /**
     * Créneaux d'une classe sur une période, mis en grille.
     *
     * @return array{depart:?string, arrivee:?string, grille:array<string, array<int, array>>, total:int, du:?string, au:?string}
     */
    public static function grille(int $classe, ?string $du = null, ?string $au = null): array
    {
        $vide = ['depart' => null, 'arrivee' => null, 'grille' => [], 'total' => 0, 'du' => $du, 'au' => $au];

        if (! Schema::hasTable('prog_cours')) {
            return $vide;
        }

        $creneaux = DB::table('prog_cours as p')
            ->join('attr_ue_classe as a', 'p.Id_Attr_ue_Sem', '=', 'a.Id_Attr_ue_Sem')
            ->leftJoin('ue', 'a.codeUe', '=', 'ue.codeUe')
            ->leftJoin('ecue', 'p.codeEcu', '=', 'ecue.codeEcu')
            ->leftJoin('salle', 'p.codeSalle', '=', 'salle.codeSalle')
            ->where('a.id_S_Gr_Spe', $classe)
            ->when($du, fn ($q) => $q->whereDate('p.datefin', '>=', $du))
            ->when($au, fn ($q) => $q->whereDate('p.datedebut', '<=', $au))
            ->orderBy('p.datedebut')
            ->orderBy('p.heureDebutCours')
            ->select([
                'p.numeroProg',
                'p.datedebut',
                'p.datefin',
                'p.heureDebutCours',
                'p.heureFinCours',
                'p.codeEcu',
                'p.codeSalle',
                'a.codeUe',
                'ue.libelleUe',
                'ecue.libelleEcu',
                'salle.Lib_Salle',
            ])
            ->get();

        if ($creneaux->isEmpty()) {
            return $vide;
        }

        $grille = [];
        $depart = null;
        $arrivee = null;
        $total = 0;

        foreach ($creneaux as $c) {
            $debut = self::heure($c->heureDebutCours);
            $fin = self::heure($c->heureFinCours);

            if ($debut === null || $fin === null) {
                continue;
            }

            $depart = $depart === null ? $debut : min($depart, $debut);
            $arrivee = $arrivee === null ? $fin : max($arrivee, $fin);

            // Le créneau occupe-t-il ce jour de la semaine ? On teste sur
            // l'intervalle de dates, pas sur une seule occurrence.
            foreach (self::joursCouverts($c->datedebut, $c->datefin) as $jour) {
                $grille[$jour][$debut][] = [
                    'numero' => $c->numeroProg,
                    'ue' => $c->codeUe,
                    'libelle_ue' => $c->libelleUe ?: $c->codeUe,
                    'ecue' => $c->codeEcu,
                    'libelle_ecue' => $c->libelleEcu ?: $c->codeEcu,
                    'salle' => $c->codeSalle,
                    'libelle_salle' => $c->Lib_Salle ?: $c->codeSalle,
                    'debut' => $debut,
                    'fin' => $fin,
                    'du' => $c->datedebut,
                    'au' => $c->datefin,
                ];
                $total++;
            }
        }

        ksort($grille);
        foreach ($grille as &$parHeure) {
            ksort($parHeure);
        }
        unset($parHeure);

        return [
            'depart' => $depart,
            'arrivee' => $arrivee,
            'grille' => $grille,
            'total' => $total,
            'du' => $du,
            'au' => $au,
        ];
    }

    /**
     * Jours de la semaine (1–7) qu'un intervalle de dates couvre.
     *
     * Un intervalle de trois semaines couvre trois fois le même lundi :
     * la grille hebdomadaire répète le créneau trois fois, ce qui
     * correspond à ce que l'étudiant vit réellement.
     *
     * @return int[]
     */
    public static function joursCouverts(?string $debut, ?string $fin): array
    {
        $d = self::date($debut);
        $f = self::date($fin);

        if ($d === null) {
            return [];
        }

        if ($f === null || $f < $d) {
            return [(int) $d->isoWeekday()];
        }

        // Un intervalle absurde (plus d'un an) ferait exploser la grille :
        // on le ramène à son premier jour.
        if ($d->diffInDays($f) > 366) {
            return [(int) $d->isoWeekday()];
        }

        $jours = [];
        for ($jour = $d->copy(); $jour <= $f; $jour->addDay()) {
            $jours[(int) $jour->isoWeekday()] = true;
        }

        return array_keys($jours);
    }

    /** « 19:36:00 » ou « 08:00 » → « 08:00 ». */
    public static function heure(?string $valeur): ?string
    {
        if ($valeur === null || trim($valeur) === '') {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})/', trim($valeur), $m)) {
            return null;
        }

        $heures = min(23, max(0, (int) $m[1]));

        return sprintf('%02d:%02d', $heures, (int) $m[2]);
    }

    private static function date(?string $valeur)
    {
        if (empty($valeur)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($valeur)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
