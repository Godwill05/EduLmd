<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Document émis, vérifiable publiquement par son code.
 *
 * Voir database/migrations/2026_11_24_000001_create_documents_verification_table.php
 */
class DocumentVerification extends Model
{
    protected $table = 'documents_verification';

    protected $fillable = [
        'code',
        'type_document',
        'reference',
        'matricule',
        'nom_complet',
        'date_nais',
        'lieu_nais',
        'cycle',
        'mention',
        'specialite',
        'session_etude',
        'lieu_delivrance',
        'date_delivrance',
        'etablissement',
        'statut',
        'motif_revocation',
        'revoque_le',
        'emis_par',
        'nb_impressions',
    ];

    protected $casts = [
        'revoque_le' => 'datetime',
        'nb_impressions' => 'integer',
    ];

    /** Le document est-il encore valide ? */
    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    /** Intitulé lisible du type (« attestation » → « Attestation de diplôme »). */
    public function libelleType(): string
    {
        return match ($this->type_document) {
            'attestation' => 'Attestation de diplôme',
            'certificat' => 'Certificat d\'authenticité',
            default => ucfirst($this->type_document),
        };
    }
}
