<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Test d'existence d'une table, insensible à la casse.
 *
 * Pourquoi ce détour : la base de production vient d'un dump SQL importé
 * à la main. Les tables existent, mais l'historique de la table
 * `migrations` est vide — `php artisan migrate` tentait donc de tout
 * recréer et s'arrêtait sur « Table 'users' already exists ».
 *
 * Les migrations sont de ce fait idempotentes : elles ne créent que ce qui
 * manque. Reste un piège de casse : MySQL sous Windows (insensible à la
 * casse) a créé `filierecpls`, alors que `Schema::hasTable('filiereCpls')`
 * est sensible à la casse sur SQLite. On compare donc les noms en
 * minuscules.
 */
class Tables
{
    /** @var array<string, bool>|null  cache des noms de tables existantes */
    private static ?array $connues = null;

    /** La table existe-t-elle (casse ignorée) ? */
    public static function existe(string $table, ?string $connexion = null): bool
    {
        $schema = $connexion ? Schema::connection($connexion) : Schema::getFacadeRoot();

        if ($schema->hasTable($table)) {
            return true;
        }

        return isset(self::noms($connexion)[strtolower($table)]);
    }

    /**
     * Noms des tables présentes, indexés en minuscules.
     *
     * @return array<string, string>
     */
    private static function noms(?string $connexion): array
    {
        $cle = $connexion ?? 'defaut';
        if (self::$connues !== null && isset(self::$connues[$cle])) {
            return self::$connues[$cle];
        }

        $schema = $connexion ? Schema::connection($connexion) : Schema::getFacadeRoot();
        $pilote = $schema->getConnection()->getDriverName();
        $liste = [];

        try {
            if ($pilote === 'sqlite') {
                $lignes = $schema->getConnection()
                    ->select("select name from sqlite_master where type = 'table'");
                foreach ($lignes as $l) {
                    $liste[strtolower($l->name)] = $l->name;
                }
            } elseif ($pilote === 'mysql') {
                $base = $schema->getConnection()->getDatabaseName();
                $lignes = $schema->getConnection()
                    ->select('select TABLE_NAME as nom from information_schema.TABLES where TABLE_SCHEMA = ?', [$base]);
                foreach ($lignes as $l) {
                    $liste[strtolower($l->nom)] = $l->nom;
                }
            }
        } catch (\Throwable $e) {
            // Catalogue illisible : on s'en tient à `hasTable`.
        }

        self::$connues[$cle] = $liste;

        return $liste;
    }

    /** À appeler après une création, pour ne pas garder un cache périmé. */
    public static function oublier(): void
    {
        self::$connues = null;
    }
}
