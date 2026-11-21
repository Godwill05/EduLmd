<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'impression-attestation',
        'impression-diplome',
        'etudiants/*',
        'impression-semestre-selection',
        'impression-situation-echeance',
        'situation-paiements-echeance',
        'impression-paiement',
        'impression-journal-encaissement',
        'journal-encaissement',
        'attestations-diplomes/*',
        'dP',
    ];
}
