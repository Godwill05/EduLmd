<?php

namespace App\Support;

use App\Models\ParametreSysteme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Accès aux constantes système (téléphone, e-mail, direction…).
 *
 * Les valeurs sont lues à chaque page (pied de page, en-têtes
 * d'impression) : on les garde en cache pour éviter une requête par
 * constante. Toute écriture vide le cache.
 *
 * L'application doit rester utilisable si la migration n'a pas encore été
 * jouée : `get()` renvoie alors la valeur de repli au lieu de planter.
 */
class Parametres
{
    private const CACHE = 'parametres_systeme';

    /** @var array<string, string>|null */
    private static ?array $memoire = null;

    /** Toutes les valeurs, indexées par clé. */
    public static function tous(): array
    {
        if (self::$memoire !== null) {
            return self::$memoire;
        }

        return self::$memoire = Cache::rememberForever(self::CACHE, function () {
            if (! Schema::hasTable('parametres_systeme')) {
                return [];
            }

            return ParametreSysteme::query()
                ->pluck('valeur', 'cle')
                ->map(fn ($v) => (string) $v)
                ->all();
        });
    }

    /** Valeur d'une constante, ou $defaut si elle n'existe pas. */
    public static function get(string $cle, string $defaut = ''): string
    {
        $tous = self::tous();
        $valeur = $tous[$cle] ?? null;

        if ($valeur === null || trim($valeur) === '') {
            return $defaut;
        }

        return $valeur;
    }

    /** Écrit une constante et vide le cache. */
    public static function set(string $cle, ?string $valeur): void
    {
        ParametreSysteme::query()->where('cle', $cle)->update(['valeur' => $valeur]);
        self::oublier();
    }

    /** Vide le cache et la mémoire statique. */
    public static function oublier(): void
    {
        self::$memoire = null;
        Cache::forget(self::CACHE);
    }

    /**
     * Les paramètres groupés pour l'affichage, dans l'ordre déclaré par
     * `GROUPES` puis par `ordre`.
     *
     * @return array<string, \Illuminate\Support\Collection<int, ParametreSysteme>>
     */
    public static function groupes(): array
    {
        if (! Schema::hasTable('parametres_systeme')) {
            return [];
        }

        $lignes = ParametreSysteme::query()->orderBy('ordre')->orderBy('libelle')->get();

        $groupes = [];
        foreach (array_keys(ParametreSysteme::GROUPES) as $code) {
            $groupe = $lignes->where('groupe', $code)->values();
            if ($groupe->isNotEmpty()) {
                $groupes[$code] = $groupe;
            }
        }

        // Un groupe créé hors interface ne doit pas devenir invisible.
        foreach ($lignes->pluck('groupe')->unique() as $code) {
            if (! isset($groupes[$code])) {
                $groupes[$code] = $lignes->where('groupe', $code)->values();
            }
        }

        return $groupes;
    }

    /** Libellé lisible d'un groupe. */
    public static function libelleGroupe(string $code): string
    {
        return ParametreSysteme::GROUPES[$code] ?? ucfirst($code);
    }
}
