<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /* ------------------------------------------------------------------
         | Compatibilité SQLite — usage local et audit uniquement.
         |
         | Le schéma de production est MySQL : plusieurs requêtes du projet
         | utilisent DATE_FORMAT, IFNULL, YEAR(), MONTH(), GREATEST ou
         | CONCAT_WS, absents de SQLite. Une base SQLite ne peut pas non plus
         | comparer un entier à une chaîne comme MySQL le fait.
         |
         | Ce bloc n'est donc exécuté que si la connexion courante est SQLite :
         | en production (MySQL), il ne fait rien du tout.
         | ------------------------------------------------------------------ */
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $this->enregistrerFonctionsSqlite();
    }

    /**
     * Déclare les fonctions SQL MySQL les plus utilisées par le projet.
     */
    protected function enregistrerFonctionsSqlite(): void
    {
        $pdo = DB::connection()->getPdo();

        $format = static function ($date, $motif) {
            if ($date === null || $date === '') {
                return null;
            }
            $ts = strtotime((string) $date);
            if ($ts === false) {
                return null;
            }
            /* Traduction des motifs MySQL les plus courants vers DateTime. */
            $remplacements = [
                '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n', '%d' => 'd',
                '%e' => 'j', '%H' => 'H', '%i' => 'i', '%s' => 's', '%M' => 'F',
                '%b' => 'M', '%W' => 'l', '%a' => 'D',
            ];
            return date(strtr($motif, $remplacements), $ts);
        };

        $fonctions = [
            'DATE_FORMAT' => $format,
            'IFNULL' => static fn ($valeur, $defaut) => $valeur ?? $defaut,
            'YEAR' => static fn ($d) => ($d === null || $d === '') ? null : (int) date('Y', strtotime((string) $d)),
            'MONTH' => static fn ($d) => ($d === null || $d === '') ? null : (int) date('n', strtotime((string) $d)),
            'DAY' => static fn ($d) => ($d === null || $d === '') ? null : (int) date('j', strtotime((string) $d)),
            'GREATEST' => static fn (...$valeurs) => max($valeurs),
            'LEAST' => static fn (...$valeurs) => min($valeurs),
            'CONCAT_WS' => static function ($separateur, ...$valeurs) {
                return implode($separateur, array_filter($valeurs, static fn ($v) => $v !== null && $v !== ''));
            },
        ];

        foreach ($fonctions as $nom => $appelable) {
            $pdo->sqliteCreateFunction($nom, $appelable, -1);
        }

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        error_reporting(E_ALL & ~E_DEPRECATED);
        Paginator::useBootstrapFive();
    }
}
