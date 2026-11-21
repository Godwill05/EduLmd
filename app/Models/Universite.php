<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property integer $id
 * @property string $code
 * @property string $nom
 * @property string $adressePostale
 * @property string $telephone
 * @property string $email
 * @property string $recteur
 * @property string $siteWeb
 * @property string $observations
 * @property string $created_at
 * @property string $updated_at
 */
class Universite extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    /**
     * @var array
     */
    protected $fillable = ['code', 'nom', 'adressePostale', 'logo', 'telephone', 'email', 'recteur', 'siteWeb', 'observations', 'created_at', 'updated_at'];
}
