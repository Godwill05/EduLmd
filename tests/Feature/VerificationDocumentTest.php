<?php

namespace Tests\Feature;

use App\Models\DocumentVerification;
use App\Models\User;
use App\Support\CodeDocument;
use App\Support\DocumentsVerifiables;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Vérification publique d'un document émis.
 *
 * Ce que ces tests protègent :
 *  - un document imprimé est retrouvable par son code, sans compte ;
 *  - la page n'expose que ce qui est déjà sur le papier (pas de matricule,
 *    pas de date de naissance, pas de données financières) ;
 *  - un code inconnu ne provoque pas d'erreur et ne dit pas si le code
 *    « ressemble » à un code valide ;
 *  - réimprimer un diplôme ne change pas son code.
 */
class VerificationDocumentTest extends TestCase
{
    use DatabaseTransactions;

    private const ETU = [
        'nom_complet' => 'DOSSOU Koffi Emmanuel',
        'date_nais' => '12/03/2000',
        'lieu_nais' => 'Porto-Novo',
        'matricule' => 'TESTS0001',
        'lieu_delivrance' => 'Porto-Novo',
        'date_delivrance' => '21/11/2026',
        'cycle' => 'Licence',
        'mention' => 'Bien',
        'specialite' => 'Génie Logiciel',
        'session' => 'Juin 2026',
        'reference' => 'N° TEST/0001/IUSAA/DV',
    ];

    /** Le code est lisible, unique et d'entropie suffisante. */
    public function test_le_code_genere_est_lisible_et_imprevisible(): void
    {
        $code = CodeDocument::generer();

        $this->assertMatchesRegularExpression(
            '/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/',
            $code,
            'Le code doit être en trois groupes de quatre, sans caractères ambigus.'
        );

        $this->assertTrue(CodeDocument::valide($code));

        // Deux appels ne doivent pas produire le même code.
        $autres = [];
        for ($i = 0; $i < 50; $i++) {
            $autres[] = CodeDocument::generer();
        }
        $this->assertCount(50, array_unique($autres), 'Les codes doivent être distincts.');
        $this->assertNotContains($code, $autres);
    }

    /** La saisie humaine est ramenée à la forme canonique. */
    public function test_la_saisie_est_tolerante(): void
    {
        $this->assertSame('A7K2-9XQ4-B3M8', CodeDocument::normaliser('a7k2 9xq4 b3m8'));
        $this->assertSame('A7K2-9XQ4-B3M8', CodeDocument::normaliser('A7K29XQ4B3M8'));
        $this->assertSame('A7K2-9XQ4-B3M8', CodeDocument::normaliser('  A7K2-9XQ4-B3M8  '));
        $this->assertSame('A7K2-9XQ4-B3M8', CodeDocument::normaliser('A7K2!9XQ4@B3M8'));
        $this->assertSame('', CodeDocument::normaliser(''));
        $this->assertSame('', CodeDocument::normaliser(null));
    }

    /** Réimprimer un diplôme réutilise le même code. */
    public function test_reimprimer_ne_change_pas_le_code(): void
    {
        $premier = DocumentsVerifiables::enregistrer(self::ETU, 'attestation');
        $second = DocumentsVerifiables::enregistrer(self::ETU, 'certificat');

        $this->assertNotNull($premier);
        $this->assertSame($premier, $second,
            'Le certificat doit porter le code de l\'attestation : c\'est le même diplôme.');

        $document = DocumentVerification::where('code', $premier)->first();
        $this->assertSame(2, $document->nb_impressions);
    }

    /** La page publique trouve le document et n'affiche rien de sensible. */
    public function test_la_page_publique_affiche_le_document(): void
    {
        $code = DocumentsVerifiables::enregistrer(self::ETU, 'attestation');

        $reponse = $this->get('/verifier/' . $code);

        $reponse->assertOk();
        $reponse->assertSee('Document authentique');
        $reponse->assertSee('DOSSOU Koffi Emmanuel');
        $reponse->assertSee('Licence');
        $reponse->assertSee('Génie Logiciel');
        $reponse->assertSee($code);

        // Ce qui est sur le papier est affiché ; le reste ne doit pas fuiter.
        $reponse->assertDontSee('TESTS0001', false);
        $reponse->assertDontSee('12/03/2000', false);
    }

    /** Un code inconnu ne produit ni erreur ni révélation. */
    public function test_un_code_inconnu_repond_sans_erreur(): void
    {
        $reponse = $this->get('/verifier/ZZZZ-9999-8888');

        $reponse->assertOk();
        $reponse->assertSee('Code inconnu');
    }

    /** Un code mal formé est traité comme un code inconnu. */
    public function test_un_code_mal_forme_est_traite_comme_inconnu(): void
    {
        $reponse = $this->get('/verifier/nimportequoi');

        $reponse->assertOk();
        $reponse->assertSee('Code inconnu');
    }

    /** Un document révoqué le dit clairement. */
    public function test_un_document_revoque_est_signale(): void
    {
        $code = DocumentsVerifiables::enregistrer(self::ETU, 'attestation');

        DocumentVerification::where('code', $code)->update([
            'statut' => 'revoque',
            'motif_revocation' => 'Diplôme annulé après vérification de fraude',
            'revoque_le' => now(),
        ]);

        $reponse = $this->get('/verifier/' . $code);

        $reponse->assertOk();
        $reponse->assertSee('Document révoqué');
        $reponse->assertSee('fraude');
        $reponse->assertDontSee('Document authentique');
    }

    /** La recherche manuelle accepte un code tapé sans tirets. */
    public function test_la_recherche_manuelle_accepte_une_saisie_libre(): void
    {
        $code = DocumentsVerifiables::enregistrer(self::ETU, 'attestation');
        $sansTirets = str_replace('-', '', $code);

        $reponse = $this->post('/verifier', ['code' => $sansTirets]);

        $reponse->assertRedirect(route('verification.document', ['code' => $code]));

        $this->get('/verifier/' . $code)->assertSee('Document authentique');
    }

    /** La page de vérification reste accessible sans être connecté. */
    public function test_la_page_est_accessible_sans_connexion(): void
    {
        $code = DocumentsVerifiables::enregistrer(self::ETU, 'attestation');

        $this->assertGuest();

        $this->get('/verifier/' . $code)->assertOk();
    }

    /** La page de vérification n'expose aucune donnée financière. */
    public function test_aucune_donnee_financiere_n_est_exposee(): void
    {
        $code = DocumentsVerifiables::enregistrer(self::ETU, 'attestation');

        $html = $this->get('/verifier/' . $code)->getContent();

        foreach (['reste_paye', 'frais_paye', 'S_Finance', 'paiement', 'FCFA', 'reste à payer'] as $terme) {
            $this->assertStringNotContainsString($terme, $html,
                "La page ne doit pas exposer « {$terme} ».");
        }
    }

    /** Un utilisateur sans rôle ne peut pas accéder à la vérification privée. */
    public function test_la_verification_ne_donne_acces_a_aucun_ecran_prive(): void
    {
        $code = DocumentsVerifiables::enregistrer(self::ETU, 'attestation');

        // La page publique ne doit proposer aucun lien vers l'administration.
        $html = $this->get('/verifier/' . $code)->getContent();

        foreach (['/etudiants', '/paiements', '/utilisateurs', '/journal-activite', '/attestations-diplomes'] as $lien) {
            $this->assertStringNotContainsString('href="' . url($lien) . '"', $html);
        }
    }

    /**
     * Un visiteur qui tape « /verifier » sans code tombe sur le
     * formulaire, pas sur une erreur 405.
     *
     * Cas courant : le QR est abîmé, la personne recopie l'adresse à la
     * main en oubliant le code. « Méthode non autorisée » ne veut rien
     * dire pour elle.
     */
    public function test_la_page_sans_code_affiche_le_formulaire(): void
    {
        $reponse = $this->get('/verifier');

        $reponse->assertOk();
        $reponse->assertSee('Vérifier un autre document');
        $reponse->assertSee('Code inconnu');
    }
}
