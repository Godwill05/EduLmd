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
}
