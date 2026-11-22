<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Thèmes et apparence.
 *
 * Ce qui est protégé ici :
 *  - le script d'amorçage est présent AVANT le corps de page (sinon flash) ;
 *  - les feuilles de thème arrivent après la feuille compilée (sinon les
 *    variables sont écrasées et le mode sombre ne prend pas) ;
 *  - le bouton et son panneau sont là ;
 *  - les pages publiques (vérification d'un QR) ne portent pas le menu
 *    d'un utilisateur connecté.
 */
class ApparenceTest extends TestCase
{
    use DatabaseTransactions;

    private function utilisateur(): User
    {
        $u = User::where('email', 'audit@edulmd.test')->first();
        if (! $u) $this->markTestSkipped("Compte d'audit absent.");
        return $u;
    }

    /** Les feuilles de thème sont chargées, après la feuille compilée. */
    public function test_les_feuilles_de_theme_sont_chargees(): void
    {
        $html = $this->actingAs($this->utilisateur())->get('/home')->getContent();

        $this->assertStringContainsString('edulmd-theme.css', $html);
        $this->assertStringContainsString('edulmd-layout.css', $html);

        // L'ordre compte : la compilée d'abord, les surcharges ensuite.
        $posCompilee = strpos($html, 'edulmd.css');
        $posTheme    = strpos($html, 'edulmd-theme.css');
        $posLayout   = strpos($html, 'edulmd-layout.css');

        $this->assertLessThan($posTheme, $posCompilee,
            'edulmd-theme.css doit être chargée après edulmd.css.');
        $this->assertLessThan($posLayout, $posCompilee,
            'edulmd-layout.css doit être chargée après edulmd.css.');
    }

    /** Le thème est appliqué avant le rendu du corps. */
    public function test_le_theme_est_applique_avant_le_corps(): void
    {
        $html = $this->actingAs($this->utilisateur())->get('/home')->getContent();

        $posScript = strpos($html, "localStorage.getItem('edulmd.apparence')");
        $posBody   = strpos($html, '<body');

        $this->assertNotFalse($posScript, 'Le script d\'amorçage du thème est absent.');
        $this->assertNotFalse($posBody);
        $this->assertLessThan($posBody, $posScript,
            'L\'amorçage doit précéder <body>, sinon la page clignote en clair.');
    }

    /** Le bouton et le script du panneau sont présents. */
    public function test_le_panneau_d_apparence_est_present(): void
    {
        $html = $this->actingAs($this->utilisateur())->get('/home')->getContent();

        $this->assertStringContainsString('data-ed-apparence', $html);
        $this->assertStringContainsString('edulmd-apparence.js', $html);
    }

    /** Le thème suit sur les pages récentes (pilotage, emploi du temps). */
    public function test_le_theme_suit_sur_les_nouvelles_pages(): void
    {
        foreach (['/pilotage', '/emploi-du-temps'] as $url) {
            $html = $this->actingAs($this->utilisateur())->get($url)->getContent();
            $this->assertStringContainsString('data-ed-apparence', $html, "Bouton absent sur {$url}.");
            $this->assertStringContainsString('edulmd-theme.css', $html, "Thème absent sur {$url}.");
        }
    }

    /** La page publique de vérification reste sans barre latérale. */
    public function test_la_page_publique_reste_autonome(): void
    {
        $html = $this->get('/verifier')->getContent();

        $this->assertStringNotContainsString('data-ed-apparence', $html,
            'La page publique ne doit pas proposer le menu de l\'application.');
        $this->assertStringNotContainsString('sidebar', $html);
    }

    /**
     * Les accents restent lisibles en mode sombre.
     *
     * Défaut trouvé en construisant le thème : l'ambre (#fbbf24) avec du
     * texte blanc donne 1,7:1 — illisible, très en dessous du seuil AA de
     * 4,5:1. On ne teste pas « c'est joli », on teste un rapport de
     * luminance calculé, qui ne dépend d'aucun avis.
     */
    public function test_les_accents_sont_lisibles_en_mode_sombre(): void
    {
        // Texte posé sur l'accent, tel que le CSS le déclare.
        $paires = [
            'bordeaux' => ['#b8536d', '#ffffff'],
            'bleu'     => ['#4d67e8', '#ffffff'],
            'emeraude' => ['#2dd4bf', '#06211f'],
            'violet'   => ['#a78bfa', '#1b1233'],
            'ambre'    => ['#fbbf24', '#2b1d05'],
            'ardoise'  => ['#94a3b8', '#131a24'],
        ];

        foreach ($paires as $nom => [$fond, $texte]) {
            $ratio = $this->contraste($fond, $texte);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "L'accent « {$nom} » n'atteint pas le seuil AA : {$ratio}:1."
            );
        }
    }

    /** Le texte principal est lisible sur le fond sombre. */
    public function test_le_texte_est_lisible_sur_le_fond_sombre(): void
    {
        $this->assertGreaterThanOrEqual(7.0, $this->contraste('#f2f2f4', '#101114'));
        $this->assertGreaterThanOrEqual(4.5, $this->contraste('#a4a7b0', '#191a1e'));
    }

    /**
     * Rapport de contraste WCAG entre deux couleurs.
     *
     * Formule officielle : (L1 + 0,05) / (L2 + 0,05), où L est la
     * luminance relative. Le seuil AA pour du texte normal est 4,5:1.
     */
    private function contraste(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);
        $haut = max($la, $lb);
        $bas = min($la, $lb);

        return round(($haut + 0.05) / ($bas + 0.05), 2);
    }

    private function luminance(string $hexa): float
    {
        $h = ltrim($hexa, '#');
        $canaux = [];

        foreach ([0, 2, 4] as $i) {
            $c = hexdec(substr($h, $i, 2)) / 255;
            $canaux[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $canaux[0] + 0.7152 * $canaux[1] + 0.0722 * $canaux[2];
    }
}
