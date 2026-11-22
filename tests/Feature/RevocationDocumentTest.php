<?php

namespace Tests\Feature;

use App\Models\DocumentVerification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Révocation d'un document émis.
 *
 * Le point délicat : la révocation n'efface rien. Le QR code est imprimé
 * sur du papier qui circule ; si la ligne disparaissait, le scan
 * répondrait « code inconnu » — c'est-à-dire « faux document » — alors que
 * le document a bien été émis puis annulé. Ces tests vérifient que le
 * statut bascule et que la page publique raconte la bonne histoire.
 */
class RevocationDocumentTest extends TestCase
{
    use DatabaseTransactions;

    private ?User $superAdmin = null;

    /** Compte de niveau 1, créé à la demande (la base n'en a pas). */
    private function niveauUn(): User
    {
        if ($this->superAdmin) {
            return $this->superAdmin;
        }

        $role = Role::where('order', 1)->first();

        if (! $role) {
            $this->markTestSkipped('Aucun rôle de niveau 1 en base.');
        }

        $utilisateur = User::create([
            'login' => 'tmp_revoc_' . uniqid(),
            'firstname' => 'Super',
            'lastname' => 'Test',
            'email' => 'tmp_revoc_' . uniqid() . '@edulmd.test',
            'password' => bcrypt('x'),
            'telephone' => '00000000',
        ]);
        $utilisateur->syncRoles([$role]);

        return $this->superAdmin = $utilisateur;
    }

    /** Un document valide, avec un code conforme à l'alphabet. */
    private function document(array $surcharges = []): DocumentVerification
    {
        return DocumentVerification::create(array_merge([
            'code' => 'ABCD-EFGH-JKMN',
            'type_document' => 'attestation',
            'reference' => 'N° 2026/TEST/IUSAA/DA',
            'matricule' => '20008225',
            'nom_complet' => 'KOUASSI Test Révocation',
            'date_nais' => '12/03/2000',
            'lieu_nais' => 'Cotonou',
            'cycle' => 'Licence Professionnelle',
            'mention' => 'Génie Civil',
            'specialite' => 'Génie Civil',
            'session_etude' => 'Juin 2026',
            'lieu_delivrance' => 'Porto-Novo',
            'date_delivrance' => '20/06/2026',
            'etablissement' => 'IUSAA',
            'statut' => 'valide',
            'emis_par' => 'test',
            'nb_impressions' => 1,
        ], $surcharges));
    }

    /** L'écran liste les documents émis. */
    public function test_l_ecran_liste_les_documents(): void
    {
        $this->document();

        $reponse = $this->actingAs($this->niveauUn())->get('/revocation-documents');

        $reponse->assertOk();
        $reponse->assertSee('Révocation');
        $reponse->assertSee('KOUASSI Test Révocation');
        $reponse->assertSee('ABCD-EFGH-JKMN');
    }

    /** Un motif est obligatoire : pas de révocation anonyme. */
    public function test_le_motif_est_obligatoire(): void
    {
        $document = $this->document();

        $reponse = $this->actingAs($this->niveauUn())
            ->post("/revocation-documents/{$document->id}/revoquer", []);

        $reponse->assertSessionHasErrors('motif');

        $document->refresh();
        $this->assertSame('valide', $document->statut,
            'Sans motif, le document ne doit pas être révoqué.');
    }

    /** Un motif trop court est refusé : « erreur » ne justifie rien. */
    public function test_un_motif_vague_est_refuse(): void
    {
        $document = $this->document();

        $reponse = $this->actingAs($this->niveauUn())
            ->post("/revocation-documents/{$document->id}/revoquer", ['motif' => 'erreur']);

        $reponse->assertSessionHasErrors('motif');
        $this->assertSame('valide', $document->fresh()->statut);
    }

    /** La révocation bascule le statut, date et conserve le motif. */
    public function test_la_revocation_est_datee_et_motivee(): void
    {
        $document = $this->document();

        $this->actingAs($this->niveauUn())
            ->post("/revocation-documents/{$document->id}/revoquer", [
                'motif' => "Diplôme délivré sur la base d'un relevé falsifié.",
            ]);

        $document->refresh();

        $this->assertSame('revoque', $document->statut);
        $this->assertNotNull($document->revoque_le);
        $this->assertStringContainsString('falsifié', $document->motif_revocation);

        // Le document reste en base : le QR imprimé doit continuer de répondre.
        $this->assertDatabaseHas('documents_verification', ['id' => $document->id]);
    }

    /** La page publique annonce la révocation, avec le motif. */
    public function test_la_page_publique_affiche_la_revocation(): void
    {
        $document = $this->document([
            'statut' => 'revoque',
            'motif_revocation' => "Diplôme délivré sur la base d'un relevé falsifié.",
            'revoque_le' => now(),
        ]);

        $reponse = $this->get('/verifier/' . $document->code);

        $reponse->assertOk();
        $reponse->assertSee('Document révoqué');
        $reponse->assertSee('Ne pas accepter');
        $reponse->assertSee('falsifié');
        $reponse->assertDontSee('Document authentique');
    }

    /** Le document révoqué ne divulgue pas plus que le document valide. */
    public function test_la_page_revoquee_ne_divulgue_rien_de_plus(): void
    {
        $document = $this->document([
            'statut' => 'revoque',
            'motif_revocation' => 'Erreur de délivrance constatée par la scolarité.',
            'revoque_le' => now(),
        ]);

        $html = $this->get('/verifier/' . $document->code)->getContent();

        $this->assertStringNotContainsString('20008225', $html, 'Le matricule ne doit pas être exposé.');
        $this->assertStringNotContainsString('12/03/2000', $html, 'La date de naissance ne doit pas être exposée.');
    }

    /** Rétablir remet le document en service. */
    public function test_le_retablissement_annule_la_revocation(): void
    {
        $document = $this->document([
            'statut' => 'revoque',
            'motif_revocation' => 'Révoqué par erreur sur une homonymie.',
            'revoque_le' => now()->subDay(),
        ]);

        $this->actingAs($this->niveauUn())
            ->post("/revocation-documents/{$document->id}/retablir");

        $document->refresh();

        $this->assertSame('valide', $document->statut);
        $this->assertNull($document->motif_revocation);
        $this->assertNull($document->revoque_le);

        $this->get('/verifier/' . $document->code)->assertSee('Document authentique');
    }

    /** Le rétablissement laisse une trace dans le journal. */
    public function test_le_retablissement_est_journalise(): void
    {
        $document = $this->document([
            'statut' => 'revoque',
            'motif_revocation' => 'Révoqué par erreur.',
            'revoque_le' => now(),
        ]);

        $this->actingAs($this->niveauUn())
            ->post("/revocation-documents/{$document->id}/retablir");

        $this->assertTrue(
            DB::table('audits')
                ->where('auditable_type', DocumentVerification::class)
                ->where('auditable_id', $document->id)
                ->exists(),
            'Un rétablissement doit laisser une trace : il efface une décision.'
        );
    }

    /** Un administrateur de niveau 2 ne peut pas révoquer. */
    public function test_seul_le_niveau_un_peut_revoquer(): void
    {
        $document = $this->document();

        $administrateur = User::where('email', 'audit@edulmd.test')->first();

        if (! $administrateur) {
            $this->markTestSkipped("Compte d'audit absent de cette base.");
        }

        $this->actingAs($administrateur)->get('/revocation-documents')->assertForbidden();

        $this->actingAs($administrateur)
            ->post("/revocation-documents/{$document->id}/revoquer", [
                'motif' => 'Tentative par un compte non habilité.',
            ])
            ->assertForbidden();

        $this->assertSame('valide', $document->fresh()->statut);
    }

    /** Un code hors alphabet de génération reste consultable. */
    public function test_un_code_hors_alphabet_reste_verifiable(): void
    {
        // « I » et « L » ne sont jamais générés (confusion à l'impression),
        // mais une reprise de données peut en contenir. Le document doit
        // rester vérifiable, sinon son QR imprimé ne répond plus.
        $document = $this->document(['code' => 'ABCI-EFGL-JKMN']);

        $this->get('/verifier/ABCI-EFGL-JKMN')
            ->assertOk()
            ->assertSee('Document authentique');
    }
}
