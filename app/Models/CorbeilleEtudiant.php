<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fiche étudiante placée en corbeille.
 *
 * Conserve une copie JSON complète des données retirées (étudiant,
 * inscriptions, notes, paiements, prévisions, récapitulatifs, répartition)
 * pour permettre une restauration à l'identique.
 *
 * @property int $id
 * @property string $MATRICULE
 * @property string|null $nom_complet
 * @property string|null $motif
 * @property string $type
 * @property string|null $supprime_par
 * @property array $donnees
 */
class CorbeilleEtudiant extends Model
{
    protected $table = 'corbeille_etudiants';

    protected $fillable = [
        'MATRICULE', 'nom_complet', 'motif', 'type',
        'supprime_par', 'donnees', 'nb_inscriptions', 'nb_paiements',
    ];

    protected $casts = [
        'donnees' => 'array',
    ];

    /** Nombre total de fiches en corbeille. */
    public static function total(): int
    {
        return static::count();
    }
}
