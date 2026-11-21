<?php

namespace App\Support;

/**
 * Filet de sécurité contre l'injection SQL.
 * ----------------------------------------------------------------------
 * Pourquoi ce garde-fou ?
 *   Le contrôleur d'impression (22 méthodes, ~8 000 lignes) interpole des
 *   variables directement dans le texte de ses requêtes SQL brutes :
 *
 *       DB::select("… AND annee_etude_semestre.Code_Annee_Etude='$anEtude'")
 *       DB::select("… and `Id_Annee` = $anneeaca …")
 *
 *   Ces variables viennent de la requête HTTP (formulaire ou JSON). Une
 *   apostrophe dans un nom ou un identifiant suffit à produire un SQL
 *   invalide, et un utilisateur malveillant peut y glisser du code.
 *
 * Ce que fait la classe :
 *   - sans altérer le SQL, elle **rejette toute valeur qui contient un
 *     caractère capable de sortir d'un littéral ou d'une valeur numérique**
 *     (quote, point-virgule, commentaire `--` ou `/*`, antislash) ;
 *   - elle accepte les valeurs légitimes de l'application : identifiants,
 *     matricules, codes de filière (ex. « LP3,4-GC »), libellés accentués,
 *     espaces, points, tirets.
 *
 * Donc :
 *   - injection impossible (aucune quote ni opérateur disponible) ;
 *   - SQL toujours valide (plus d'erreur « 1525 » / syntax error) ;
 *   - résultat d'une valeur refusée : 400 explicite, jamais une 500.
 */
class GardeSql
{
    /**
     * Caractères qui permettent de quitter un littéral SQL ou de chaîner une
     * instruction : apostrophe, guillemet double, antislash, point-virgule,
     * et les deux formes de commentaire.
     */
    private const MOTIFS_INTERDITS = [
        "/'/u"          => "apostrophe",
        '/"/u'          => 'guillemet',
        '/\\\\/u'       => 'antislash',
        '/;/u'          => 'point-virgule',
        '/--/u'         => 'commentaire SQL',
        '/\/\*/u'       => 'commentaire SQL',
    ];

    /** Longueur maximale acceptée pour une valeur injectée. */
    private const LONGUEUR_MAX = 255;

    /**
     * Nettoie un lot de valeurs (typiquement les données d'un formulaire).
     *
     * @param  array|object|null  $valeurs
     * @return array  mêmes clés, valeurs nettoyées
     *
     * @throws \InvalidArgumentException  si une valeur contient un motif interdit
     */
    public static function nettoyer(array $valeurs, string $contexte = ''): array
    {
        $sortie = [];

        foreach ($valeurs as $cle => $valeur) {
            if (is_array($valeur)) {
                $sortie[$cle] = self::nettoyer($valeur, $contexte.'.'.$cle);
                continue;
            }

            if (is_bool($valeur) || is_int($valeur) || is_float($valeur) || $valeur === null) {
                $sortie[$cle] = $valeur;
                continue;
            }

            $sortie[$cle] = self::valeur((string) $valeur, $contexte !== '' ? $contexte.'.'.$cle : (string) $cle);
        }

        return $sortie;
    }

    /**
     * Valide une valeur scalaire destinée à être interpolée dans un SQL.
     *
     * @throws \InvalidArgumentException
     */
    public static function valeur(string $valeur, string $champ = ''): string
    {
        if (mb_strlen($valeur) > self::LONGUEUR_MAX) {
            throw new \InvalidArgumentException(sprintf(
                'La valeur du champ « %s » est trop longue (maximum %d caractères).',
                $champ !== '' ? $champ : 'inconnu',
                self::LONGUEUR_MAX
            ));
        }

        foreach (self::MOTIFS_INTERDITS as $motif => $libelle) {
            if (preg_match($motif, $valeur)) {
                throw new \InvalidArgumentException(sprintf(
                    'La valeur du champ « %s » contient un caractère non autorisé (%s).',
                    $champ !== '' ? $champ : 'inconnu',
                    $libelle
                ));
            }
        }

        return $valeur;
    }

    /**
     * Convertit en entier sûr — pour les identifiants numériques.
     */
    public static function entier($valeur): int
    {
        return (int) $valeur;
    }
}
