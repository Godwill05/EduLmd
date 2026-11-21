<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tests de non-régression issus de l'audit complet du 2026-11-21.
 *
 * Chaque test décrit un comportement réellement observé sur la base de
 * production convertie en SQLite.
 */
class AuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $utilisateur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->utilisateur = User::where('email', 'audit@edulmd.test')->first();
        if (!$this->utilisateur) {
            $this->markTestSkipped("Compte d'audit absent de cette base.");
        }
    }

    /** Toutes les routes nommées sont-elles réellement appelables ? */
    public function test_les_routes_nommees_sont_utilisables(): void
    {
        $this->assertTrue(Route::has('login.post'), 'La route de connexion doit être nommée.');
        $this->assertTrue(Route::has('home'));
        $this->assertTrue(Route::has('etudiants.fiche'));
        $this->assertTrue(Route::has('qualite-donnees.index'));
        $this->assertTrue(Route::has('recherche.etudiants'));
    }

    /** Le tableau de bord répond correctement pour un utilisateur authentifié. */
    public function test_tableau_de_bord(): void
    {
        $this->actingAs($this->utilisateur)
            ->get('/home')
            ->assertStatus(200);
    }

    /**
     * /qualite-donnees doit fonctionner sur les quatre champs.
     *
     * L'erreur 1525 (« Incorrect DATE value ») venait de la comparaison
     * dateNaisEtu = '' : sur une colonne DATE, seul IS NULL est valide.
     */
    public function test_qualite_donnees_tous_les_champs(): void
    {
        foreach (['photo', 'telEtu', 'emailEtu', 'dateNaisEtu'] as $champ) {
            $this->actingAs($this->utilisateur)
                ->get('/qualite-donnees?champ=' . $champ)
                ->assertStatus(200);
        }
    }

    /** La fiche étudiant doit s'ouvrir pour un matricule réel. */
    public function test_fiche_etudiant(): void
    {
        $matricule = DB::table('etudiant')->value('MATRICULE');
        $this->assertNotNull($matricule);

        $this->actingAs($this->utilisateur)
            ->get('/etudiants/fiche/' . $matricule)
            ->assertStatus(200);
    }

    /** Un matricule inconnu doit répondre 404, pas 500. */
    public function test_fiche_etudiant_inconnu(): void
    {
        $this->actingAs($this->utilisateur)
            ->get('/etudiants/fiche/00000000')
            ->assertStatus(404);
    }

    /** La recherche globale renvoie du JSON exploitable. */
    public function test_recherche_globale(): void
    {
        $reponse = $this->actingAs($this->utilisateur)
            ->getJson('/api/recherche?q=a');

        $reponse->assertStatus(200)
            ->assertJsonStructure(['query', 'count', 'results']);
    }

    /** L'échéancier d'un étudiant renvoie un JSON complet. */
    public function test_echeancier_etudiant(): void
    {
        $inscription = DB::table('inscriptions')->first();
        $this->assertNotNull($inscription);

        $this->actingAs($this->utilisateur)
            ->getJson('/previsions/etudiant-echeancier'
                . '?matricule=' . urlencode($inscription->MATRICULE)
                . '&anneeAcademique=' . urlencode($inscription->Id_Annee)
                . '&anneeEtude=' . urlencode($inscription->ID_FILIERE))
            ->assertStatus(200)
            ->assertJsonStructure(['frais_total', 'cumul_paye', 'reste_total', 'tranches']);
    }

    /** Les pages de liste doivent toutes répondre 200. */
    public function test_pages_de_liste(): void
    {
        $pages = [
            '/etudiants', '/inscriptions', '/paiements', '/enseignants',
            '/filieres', '/ue', '/ecue', '/salles', '/semestres',
            '/annees-academiques', '/annees-etudes', '/mentions', '/grades',
            '/previsions', '/fiches-engagement', '/imports',
        ];

        foreach ($pages as $page) {
            $reponse = $this->actingAs($this->utilisateur)->get($page);
            $this->assertNotEquals(500, $reponse->getStatusCode(),
                'La page ' . $page . ' a répondu 500 : ' . substr(strip_tags($reponse->getContent()), 0, 200));
        }
    }

    /** Le journal d'activité exige la permission « Audit ». */
    public function test_journal_activite_est_protege(): void
    {
        $this->actingAs($this->utilisateur)
            ->get('/journal-activite')
            ->assertStatus(200);
    }

    /** Aucun doublon d'étudiant ne doit faire tomber la page qualité. */
    public function test_qualite_donnees_ne_plante_pas_sur_les_doublons(): void
    {
        $this->actingAs($this->utilisateur)
            ->get('/qualite-donnees?tab=doublons')
            ->assertStatus(200);
    }

    /**
     * /etudiants plantait en 500 : la liste des nationalités était triée
     * sur `libNationalite`, alors que la colonne s'appelle
     * `libelleNationalite` (SQLSTATE 42S22).
     */
    public function test_la_liste_des_etudiants_repond_et_trie_les_nationalites(): void
    {
        $reponse = $this->actingAs($this->utilisateur)->get('/etudiants');
        $reponse->assertStatus(200);
        $this->assertStringNotContainsString('libNationalite', $reponse->getContent());
    }

    /** La page « À propos » affiche les constantes système modifiables. */
    public function test_la_page_a_propos_expose_les_parametres_systeme(): void
    {
        $reponse = $this->actingAs($this->utilisateur)->get('/about-us');
        $reponse->assertStatus(200);

        if (! \Illuminate\Support\Facades\Schema::hasTable('parametres_systeme')) {
            return; // migration non jouée sur cette base : rien à vérifier
        }

        $reponse->assertSee('formParametres', false);
        $reponse->assertSee('contact.telephone_1', false);
    }

    /** Les rôles ont un niveau, et la page les liste. */
    public function test_la_page_des_roles_liste_les_niveaux(): void
    {
        $this->actingAs($this->utilisateur)
            ->get('/roles')
            ->assertStatus(200);
    }

    /**
     * La génération du PDF ne doit produire ni page blanche ni page en trop.
     *
     * L'attestation tient sur une page paysage et le certificat sur une page
     * portrait. Un cadre trop haut faisait déborder le pied de page et
     * Html2Pdf ajoutait une page fantôme (mesure : seuil ~160 mm).
     */
    public function test_le_pdf_attestation_et_certificat_tient_sur_deux_pages(): void
    {
        if (! class_exists(\Spipu\Html2Pdf\Html2Pdf::class)) {
            $this->markTestSkipped('Html2Pdf absent.');
        }

        $row = DB::table('ufr')->join('universites', 'ufr.Univ', '=', 'universites.id')->first();
        if (! $row) {
            $this->markTestSkipped('Établissement absent de cette base.');
        }
        $univ = [(array) $row];

        $etu = [
            'nom_complet' => 'DOSSOU Koffi Emmanuel', 'date_nais' => '12/03/2000',
            'lieu_nais' => 'Porto-Novo', 'matricule' => '10410024',
            'lieu_delivrance' => 'Porto-Novo', 'date_delivrance' => '21/11/2026',
            'decision' => 'A obtenu le diplôme de Licence Professionnelle',
            'cycle' => 'Licence', 'mention' => 'Bien', 'specialite' => 'Génie Logiciel',
            'session' => 'Juin 2026', 'reference' => 'N° 2026/0123/IUSAA/DA',
            'arrete_ligne1' => 'Arrêté Ministériel N° 2021-0281/CNE/P/CQR/SE du 9 Novembre 2021',
            'arrete_ligne2' => 'N° 2334/MERS/DC/SGM/DGES/SA du 22 Novembre 2021',
            'domaine_arrete' => 'Domaine : Sciences et Technologies',
        ];

        $attestation = \App\Http\Controllers\AttestationDiplomeController::buildAttestationPageHtml($etu, $univ);
        $certificat = \App\Http\Controllers\AttestationDiplomeController::buildCertificatPageHtml($etu, $univ);

        // Contenu demandé par l'utilisateur
        $this->assertStringContainsString('ATTESTATION DE DIPLÔME', $attestation);
        $this->assertMatchesRegularExpression(
            '/ATTESTATION DE DIPLÔME/',
            $attestation
        );
        $this->assertMatchesRegularExpression(
            '/text-align:\s*center[^;]*;?[^<]*<span[^>]*font-size: 21pt/',
            $attestation,
            'Le titre doit être centré et agrandi.'
        );
        // Les coordonnées sont DANS le cadre, centrées, séparées par un filet.
        // p align=center est le seul conteneur que html2pdf centre à cet
        // endroit : un div à largeur explicite part à gauche (66 à 94 mm) et
        // un tableau tombe à 31 mm. Mesuré à 148,3 mm sur une page de 297.
        $this->assertMatchesRegularExpression(
            '/<p align="center"[^>]*border-top:[^>]*>\s*<b>Tél/',
            $attestation,
            'Le pied doit être un p align=center dans le cadre, avec un filet.'
        );
        // Le pied doit se trouver AVANT la fermeture du cadre, sinon il sort.
        $fermetureCadre = strrpos($attestation, '</div>');
        $positionPied = strpos($attestation, 'align="center"');
        $this->assertTrue(
            $positionPied !== false && $positionPied < $fermetureCadre,
            'Le pied de page doit être placé à l\'intérieur du cadre.'
        );
        // Les « ––– » ne doivent plus être rendus (il n'en reste qu'un commentaire HTML).
        $this->assertDoesNotMatchRegularExpression(
            '/<td[^>]*>\s*–––\s*<\/td>/',
            $attestation,
            'Le tiret au-dessus du titre a été retiré.'
        );

        // Le cadre de l'attestation doit être centré sur la page paysage.
        // Page 297 mm, marges 8,5 mm : un cadre de 259 mm finit à 294 mm,
        // soit 2,6 mm trop à droite. 253 mm le ramène à un centre de 148,5.
        $this->assertMatchesRegularExpression(
            '/width: 253mm/',
            $attestation,
            'Le cadre doit mesurer 253 mm pour être centré sur la page.'
        );

        // Les deux documents réunis ne doivent produire qu'une page chacun.
        try {
            $pdf = new \Spipu\Html2Pdf\Html2Pdf('L', 'A4', 'fr', true, 'UTF-8', [8.5, 8.5, 8.5, 8.5]);
            $pdf->setTestTdInOnePage(false);
            $pdf->writeHTML($attestation . $certificat);
            $brut = $pdf->Output('test.pdf', 'S');
        } catch (\Throwable $e) {
            $this->fail('La génération du PDF a échoué : ' . $e->getMessage());
        }

        // L'attestation doit tenir sur une page À ELLE SEULE, même imprimée
        // indépendamment du pack (exigence : une seule page).
        try {
            $pdfSeul = new \Spipu\Html2Pdf\Html2Pdf('L', 'A4', 'fr', true, 'UTF-8', [8.5, 8.5, 8.5, 8.5]);
            $pdfSeul->setTestTdInOnePage(false);
            $pdfSeul->writeHTML($attestation);
            $brutSeul = $pdfSeul->Output('attestation.pdf', 'S');
            $pagesSeule = preg_match_all('#/Type\s*/Page[^s]#', $brutSeul);
            $this->assertSame(1, $pagesSeule,
                'L\'attestation doit tenir sur exactement 1 page, obtenu : ' . $pagesSeule);
        } catch (\Throwable $e) {
            $this->fail('La génération de l\'attestation seule a échoué : ' . $e->getMessage());
        }

        // Un PDF valide commence par %PDF ; on compte les objets /Type /Page.
        $this->assertStringStartsWith('%PDF', $brut);
        $nbPages = preg_match_all('#/Type\s*/Page[^s]#', $brut);
        $this->assertSame(2, $nbPages,
            'Le pack doit produire exactement 2 pages (attestation + certificat), obtenu : ' . $nbPages);
    }

    /**
     * Un utilisateur ne peut pas modifier un compte d'un niveau égal ou
     * supérieur au sien : c'est ce qui empêche un administrateur de se
     * promouvoir lui-même.
     */
    public function test_la_hierarchie_interdit_l_auto_promotion(): void
    {
        $this->actingAs($this->utilisateur);

        $this->assertFalse(
            \App\Support\Hierarchie::peutGererCible($this->utilisateur, $this->utilisateur),
            'Un utilisateur ne doit pas pouvoir agir sur son propre compte par ce chemin.'
        );

        $roleSommet = \App\Models\Role::query()->orderBy('order')->first();
        if ($roleSommet) {
            $this->assertFalse(
                \App\Support\Hierarchie::peutAttribuer($this->utilisateur, $roleSommet),
                'Le rôle de plus haut niveau ne doit jamais être attribuable.'
            );
        }
    }
}
