<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * @property string $cle
 * @property string $libelle
 * @property string|null $valeur
 * @property string $groupe
 * @property string $type
 * @property string|null $aide
 * @property int $ordre
 */
class ParametreSysteme extends Model
{
    protected $table = 'parametres_systeme';

    protected $fillable = ['cle', 'libelle', 'valeur', 'groupe', 'type', 'aide', 'ordre'];

    protected $casts = ['ordre' => 'integer'];

    /** Libellés des groupes, dans l'ordre d'affichage. */
    public const GROUPES = [
        'etablissement' => 'Établissement',
        'contact'       => 'Contacts',
        'direction'     => 'Direction',
        'academique'    => 'Académique',
    ];
}
