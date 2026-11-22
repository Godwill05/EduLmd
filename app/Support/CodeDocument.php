<?php

namespace App\Support;

/**
 * Génération et normalisation des codes de vérification de documents.
 * ---------------------------------------------------------------------
 * Le code est imprimé en QR code sur l'attestation et le certificat. Il
 * doit être :
 *
 *   - **imprévisible** : impossible de deviner le code d'un autre diplôme
 *     en observant le sien. On tire donc dans un alphabet de 32 caractères
 *     sans les lettres ambigües (0/O, 1/I/L), sur 12 caractères, soit
 *     environ 60 bits d'entropie ;
 *   - **lisible à la main** : si le QR est abîmé, un secrétaire peut saisir
 *     le code tapé sous le QR. D'où le format groupé « A7K2-9XQ4-B3M8 »
 *     et l'absence de caractères qui se confondent à l'impression.
 *
 * La normalisation accepte la saisie avec ou sans tirets, en minuscules ou
 * en majuscules : « a7k2 9xq4 b3m8 » retrouve le bon document.
 */
class CodeDocument
{
    /**
     * Alphabet sans 0/O, 1/I/L : ce sont les caractères que le personnel
     * confond le plus souvent en recopiant un code depuis un papier.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Nombre de caractères tirés au sort (hors tirets). */
    private const LONGUEUR = 12;

    /** Un groupe de 4 caractères, trois groupes. */
    private const GROUPE = 4;

    /**
     * Tire un nouveau code, au format A7K2-9XQ4-B3M8.
     *
     * À appeler en boucle par l'appelant si la colonne `code` est unique :
     * une collision reste improbable (32^12), mais le code est la clé
     * publique du document, on ne parie pas dessus.
     */
    public static function generer(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $caracteres = [];

        for ($i = 0; $i < self::LONGUEUR; $i++) {
            $caracteres[] = self::ALPHABET[random_int(0, $max)];
        }

        return implode('-', str_split(implode('', $caracteres), self::GROUPE));
    }

    /**
     * Remet une saisie humaine dans la forme canonique.
     *
     * « a7k2 9xq4-b3m8 » → « A7K2-9XQ4-B3M8 »
     * « A7K29XQ4B3M8 »   → « A7K2-9XQ4-B3M8 »
     *
     * Les caractères hors alphabet sont ignorés : une saisie fautive
     * (« A7K2-9XQ4-B3MP ») donne simplement un code qui ne correspond à
     * aucun document, et la page répond « code inconnu ». On ne lève pas
     * d'exception : la page de vérification est publique, elle ne doit
     * jamais afficher d'erreur technique.
     */
    public static function normaliser(?string $saisie): string
    {
        $nettoye = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $saisie) ?? '');

        if ($nettoye === '') {
            return '';
        }

        return implode('-', str_split($nettoye, self::GROUPE));
    }

    /** Le code a-t-il la forme attendue (12 caractères de l'alphabet) ? */
    public static function valide(string $code): bool
    {
        return (bool) preg_match(
            '/^[' . self::ALPHABET . ']{' . self::LONGUEUR . '}$/',
            str_replace('-', '', $code)
        );
    }
}
