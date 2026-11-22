<?php

namespace App\Http\Controllers;

use App\Models\DocumentVerification;
use App\Support\CodeDocument;
use Illuminate\Http\Request;

/**
 * Vérification publique d'un document émis.
 * ---------------------------------------------------------------------
 * Un employeur scanne le QR code de l'attestation : il arrive ici, sans
 * compte et sans connexion. La page répond à une seule question — « ce
 * document est-il authentique ? » — et n'expose rien de plus que ce qui
 * est déjà imprimé sur le papier :
 *
 *   - le nom du titulaire, le cycle, la mention, la spécialité ;
 *   - la date et le lieu de délivrance ;
 *   - l'établissement émetteur ;
 *   - l'état : valide, ou révoqué avec son motif.
 *
 * Ne sont JAMAIS affichés : le matricule, la date de naissance, la
 * situation financière, les notes. Un vérificateur n'en a pas besoin, et
 * un code qui fuirait ne doit pas devenir une fuite d'état civil.
 */
class VerificationDocumentController extends Controller
{
    /**
     * Page de vérification.
     *
     * Protection contre l'énumération : la page ne révèle pas si un code
     * « ressemble » à un code valide. Un code inconnu et un code mal formé
     * donnent le même écran. Par ailleurs la route est limitée en débit
     * (throttle) pour rendre le balayage de codes inexploitable.
     */
    public function show(Request $request, string $code)
    {
        $normalise = CodeDocument::normaliser($code);

        $document = null;

        if ($normalise !== '' && CodeDocument::valide($normalise)) {
            $document = DocumentVerification::where('code', $normalise)->first();
        }

        /* Journalisation légère : on veut pouvoir répondre à un employeur
           qui demande « votre page m'a dit que le document est inconnu ».
           On ne stocke ni l'adresse IP en clair ni le code complet. */
        if ($document === null) {
            logger()->info('Vérification : code inconnu', [
                'code_partiel' => substr($normalise, 0, 4) . '…',
            ]);
        }

        return view('verification-document', [
            'document' => $document,
            'code' => $normalise,
            'saisie' => $request->query('code'),
        ]);
    }

    /**
     * Recherche manuelle : le secrétaire tape le code imprimé sous le QR
     * quand celui-ci est abîmé ou que le tiers ne peut pas scanner.
     */
    public function recherche(Request $request)
    {
        $donnees = $request->validate(
            ['code' => ['required', 'string', 'max:30']],
            [],
            ['code' => 'code de vérification']
        );

        return redirect()->route('verification.document', [
            'code' => CodeDocument::normaliser($donnees['code']),
        ]);
    }
}
