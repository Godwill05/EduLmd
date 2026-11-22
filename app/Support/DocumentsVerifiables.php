<?php

namespace App\Support;

use App\Models\DocumentVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Émission et retrouvaille des documents vérifiables.
 * ---------------------------------------------------------------------
 * Une attestation et son certificat décrivent le même diplôme : ils
 * partagent donc le même code. Réimprimer ne crée pas un nouveau code,
 * on incrémente seulement le compteur d'impressions — un diplôme réédité
 * reste le même diplôme, et l'ancien QR continue de fonctionner.
 */
class DocumentsVerifiables
{
    /**
     * Enregistre (ou retrouve) le document et renvoie son code.
     *
     * Ne lève jamais d'exception : l'impression d'un diplôme ne doit pas
     * échouer parce que le registre est indisponible. En cas d'échec, on
     * journalise et on renvoie null ; le PDF sort alors sans QR code, ce
     * qui reste préférable à un écran d'erreur devant un étudiant.
     */
    public static function enregistrer(array $etu, string $typeDocument, array $etablissement = []): ?string
    {
        try {
            $matricule = (string) ($etu['matricule'] ?? '');
            $reference = (string) ($etu['reference'] ?? '');

            $existant = self::retrouver($matricule, $reference, $typeDocument);

            if ($existant) {
                $existant->increment('nb_impressions');

                return $existant->code;
            }

            return self::creer($etu, $typeDocument, $etablissement);
        } catch (\Throwable $e) {
            Log::warning('Vérification : émission impossible', [
                'matricule' => $etu['matricule'] ?? null,
                'type' => $typeDocument,
                'erreur' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Le code d'un document déjà émis pour ce diplôme, s'il existe.
     *
     * La recherche se fait sur le matricule + la référence : c'est le
     * couple qui identifie un diplôme précis. Le type n'entre pas dans la
     * clé, car l'attestation et le certificat partagent le même code.
     */
    public static function retrouver(string $matricule, string $reference, ?string $typeDocument = null): ?DocumentVerification
    {
        if ($matricule === '') {
            return null;
        }

        return DocumentVerification::query()
            ->where('matricule', $matricule)
            ->where('reference', $reference)
            ->orderBy('id')
            ->first();
    }

    /** Crée la ligne, en retirant un code tant qu'il est déjà pris. */
    private static function creer(array $etu, string $typeDocument, array $etablissement): string
    {
        do {
            $code = CodeDocument::generer();
        } while (DocumentVerification::where('code', $code)->exists());

        DocumentVerification::create([
            'code' => $code,
            'type_document' => $typeDocument,
            'reference' => $etu['reference'] ?? null,
            'matricule' => $etu['matricule'] ?? '',
            'nom_complet' => $etu['nom_complet'] ?? '',
            'date_nais' => $etu['date_nais'] ?? null,
            'lieu_nais' => $etu['lieu_nais'] ?? null,
            'cycle' => $etu['cycle'] ?? null,
            'mention' => $etu['mention'] ?? null,
            'specialite' => $etu['specialite'] ?? null,
            'session_etude' => $etu['session'] ?? null,
            'lieu_delivrance' => $etu['lieu_delivrance'] ?? null,
            'date_delivrance' => $etu['date_delivrance'] ?? null,
            'etablissement' => $etablissement['etablissement'] ?? null,
            'statut' => 'valide',
            'emis_par' => auth()->user()?->login ?? auth()->user()?->email,
            'nb_impressions' => 1,
        ]);

        return $code;
    }

    /** Adresse publique de vérification d'un code. */
    public static function url(string $code): string
    {
        return route('verification.document', ['code' => $code]);
    }

    /**
     * Le document partagé par l'attestation et le certificat porte un seul
     * code ; les deux pages doivent donc l'afficher. Renvoie le code déjà
     * enregistré pour ce diplôme, quel que soit le type demandé.
     */
    public static function codeDuDiplome(array $etu): ?string
    {
        $doc = self::retrouver((string) ($etu['matricule'] ?? ''), (string) ($etu['reference'] ?? ''));

        return $doc?->code;
    }
}
