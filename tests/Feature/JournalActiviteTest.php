<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Journal d'activité exploité.
 *
 * La table `audits` contient 29 000 lignes dont 28 000 modifications de
 * notes. Sans contexte, une ligne dit « Note #25114 », ce qui ne permet
 * pas de répondre à un étudiant qui conteste. Ces tests vérifient que :
 *
 *  - la page identifie l'étudiant, l'ECUE et le type d'évaluation ;
 *  - l'export produit un CSV lisible, une ligne par champ modifié ;
 *  - les filtres s'appliquent aussi à l'export ;
 *  - l'accès reste réservé à la permission « Gérer les utilisateurs ».
 */
class JournalActiviteTest extends TestCase
{
    use DatabaseTransactions;

    private function utilisateur(): User
    {
        $utilisateur = User::where('email', 'audit@edulmd.test')->first();

        if (! $utilisateur) {
            $this->markTestSkipped("Compte d'audit absent de cette base.");
        }

        return $utilisateur;
    }

    /** La page répond et affiche le contexte métier des notes. */
    public function test_la_page_identifie_les_notes(): void
    {
        $aDesNotes = DB::table('audits')
            ->where('auditable_type', 'App\\Models\\Note')
            ->exists();

        if (! $aDesNotes) {
            $this->markTestSkipped('Aucune note dans le journal de cette base.');
        }

        $reponse = $this->actingAs($this->utilisateur())
            ->get('/journal-activite?modele=' . urlencode('App\\Models\\Note'));

        $reponse->assertOk();

        $html = $reponse->getContent();

        // Au moins une ligne doit porter un contexte « Nom · ECUE · Type ».
        $this->assertMatchesRegularExpression(
            '/[A-ZÀ-Ý]{2,} [A-Za-zÀ-ÿ·\s]*(·|&#183;)/',
            $html,
            'Les lignes de notes doivent afficher l\'étudiant et l\'ECUE.'
        );
    }

    /** L'export renvoie un CSV avec BOM, séparateur « ; » et en-têtes. */
    public function test_l_export_produit_un_csv(): void
    {
        $reponse = $this->actingAs($this->utilisateur())
            ->get('/journal-activite/export?modele=' . urlencode('App\\Models\\Note'));

        $reponse->assertOk();
        $this->assertStringContainsString('text/csv', $reponse->headers->get('Content-Type'));

        $contenu = $reponse->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenu,
            'Le BOM UTF-8 est requis pour qu\'Excel affiche les accents.');
        $this->assertStringContainsString('Date;Utilisateur;Événement', $contenu);
        $this->assertStringContainsString('Contexte', $contenu);
    }

    /** L'export respecte les filtres de l'écran. */
    public function test_l_export_applique_les_filtres(): void
    {
        $reponse = $this->actingAs($this->utilisateur())
            ->get('/journal-activite/export?modele=' . urlencode('App\\Models\\Etudiant')
                . '&du=' . now()->subDays(30)->toDateString());

        $contenu = $reponse->streamedContent();

        // Le filtre « objet = Étudiant » doit exclure les notes.
        $this->assertStringNotContainsString(';Note;', $contenu,
            'L\'export filtré sur Étudiant ne doit pas contenir de notes.');
    }

    /** Un filtre impossible ne produit pas d'erreur, juste un CSV vide. */
    public function test_un_export_sans_resultat_ne_plante_pas(): void
    {
        $reponse = $this->actingAs($this->utilisateur())
            ->get('/journal-activite/export?du=1900-01-01&au=1900-01-02');

        $reponse->assertOk();

        $contenu = $reponse->streamedContent();

        // En-têtes seuls : c'est un CSV vide, pas une 500.
        $this->assertStringContainsString('Date;Utilisateur', $contenu);
        $this->assertSame(1, substr_count(trim($contenu), "\n") + 1,
            'Un export sans résultat ne contient que la ligne d\'en-tête.');
    }

    /** L'export est réservé à ceux qui gèrent les utilisateurs. */
    public function test_l_export_est_protege(): void
    {
        $sansDroit = User::where('login', 'test')->first();

        if (! $sansDroit) {
            $this->markTestSkipped('Compte sans rôle absent de cette base.');
        }

        $this->actingAs($sansDroit)->get('/journal-activite/export')->assertForbidden();
    }

    /** Les identifiants techniques ne fuitent pas dans l'export. */
    public function test_l_export_ne_divulgue_pas_de_mot_de_passe(): void
    {
        $reponse = $this->actingAs($this->utilisateur())
            ->get('/journal-activite/export?du=' . now()->subDays(7)->toDateString());

        $contenu = strtolower($reponse->streamedContent());

        $this->assertStringNotContainsString('$2y$', $contenu,
            'Aucun condensat de mot de passe ne doit apparaître.');
        $this->assertStringNotContainsString('remember_token', $contenu);
    }
}
