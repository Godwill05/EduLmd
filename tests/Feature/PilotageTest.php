<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Pilotage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tableau de bord « pilotage ».
 *
 * Ce que ces tests protègent :
 *  - les indicateurs sont calculés sur des données réelles, et cohérents
 *    entre eux (encaissé + reste = attendu) ;
 *  - une année inconnue retombe sur la plus récente au lieu de planter ;
 *  - l'écran est réservé à ceux qui ont la permission « Audit » ;
 *  - l'export CSV est bien un CSV, avec le BOM que réclame Excel.
 */
class PilotageTest extends TestCase
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

    /** Les indicateurs de synthèse sont cohérents. */
    public function test_la_synthese_est_coherente(): void
    {
        $s = Pilotage::synthese(null);

        $this->assertGreaterThanOrEqual(0, $s['attendu']);
        $this->assertGreaterThanOrEqual(0, $s['encaisse']);
        $this->assertGreaterThanOrEqual(0, $s['reste']);

        $this->assertSame($s['attendu'], $s['encaisse'] + $s['reste'],
            'Encaissé + reste doit égaler l\'attendu.');

        $this->assertGreaterThanOrEqual(0, $s['taux_recouvrement']);
        $this->assertLessThanOrEqual(100, $s['taux_recouvrement']);
        $this->assertGreaterThanOrEqual(0, $s['taux_reussite']);
        $this->assertLessThanOrEqual(100, $s['taux_reussite']);
    }

    /** Le recouvrement par filière est trié du plus faible au plus fort. */
    public function test_le_recouvrement_par_filiere_est_trie(): void
    {
        $lignes = Pilotage::recouvrementParFiliere(null, 50);

        if (count($lignes) < 2) {
            $this->markTestSkipped('Pas assez de filières avec des montants.');
        }

        $taux = array_column($lignes, 'taux');
        $triee = $taux;
        sort($triee);

        $this->assertSame($triee, $taux,
            'Les filières les plus faibles doivent apparaître en premier.');

        foreach ($lignes as $ligne) {
            $this->assertSame($ligne['attendu'], $ligne['encaisse'] + $ligne['reste'],
                "Cohérence rompue pour la filière {$ligne['filiere']}.");
        }
    }

    /**
     * Les UE en difficulté sont comptées sur l'année demandée, pas sur
     * toutes les promotions confondues.
     *
     * Sans ce filtre, le compteur additionnait quatre années académiques :
     * il annonçait 61 UE en difficulté là où l'année en cours en compte 36.
     * Un chiffre faux d'un facteur deux dans un tableau de bord est pire
     * qu'une absence de chiffre.
     */
    public function test_les_ue_en_difficulte_suivent_l_annee(): void
    {
        $annee = \Illuminate\Support\Facades\DB::table('anneeacademique')
            ->orderByDesc('id_Annee_Acad')->value('id_Annee_Acad');

        if (! $annee) {
            $this->markTestSkipped('Aucune année académique en base.');
        }

        $toutes = Pilotage::synthese(null);
        $uneAnnee = Pilotage::synthese((int) $annee);

        $this->assertLessThanOrEqual(
            $toutes['ue_en_difficulte'],
            $uneAnnee['ue_en_difficulte'],
            'Le compteur d\'une seule année ne peut pas dépasser le total toutes années.'
        );
    }

    /** Les UE difficiles respectent le seuil d'effectif. */
    public function test_les_ue_difficiles_respectent_le_seuil(): void
    {
        $seuil = 5;
        $lignes = Pilotage::ueLesPlusDifficiles(null, $seuil, 100);

        foreach ($lignes as $ligne) {
            $this->assertGreaterThanOrEqual($seuil, $ligne['presents'],
                "L'UE {$ligne['code']} ne devrait pas apparaître avec {$ligne['presents']} inscrits.");
            $this->assertLessThanOrEqual($ligne['presents'], $ligne['reussis']);
            $this->assertGreaterThanOrEqual(0, $ligne['taux']);
            $this->assertLessThanOrEqual(100, $ligne['taux']);
        }
    }

    /** L'évolution couvre toutes les années, dans l'ordre. */
    public function test_l_evolution_est_chronologique(): void
    {
        $annees = array_column(Pilotage::evolution(), 'annee');

        if (count($annees) < 2) {
            $this->markTestSkipped('Pas assez d\'années en base.');
        }

        $triee = $annees;
        sort($triee);
        $this->assertSame($triee, $annees, 'Les années doivent être ordonnées.');
    }

    /** Les impayés sont triés par montant décroissant et positifs. */
    public function test_les_impayes_sont_les_plus_lourds(): void
    {
        $impayes = Pilotage::impayesLesPlusLourds(null, 20);

        $precedent = PHP_INT_MAX;
        foreach ($impayes as $ligne) {
            $this->assertGreaterThan(0, $ligne['reste']);
            $this->assertLessThanOrEqual($precedent, $ligne['reste'],
                'Les impayés doivent être triés du plus lourd au plus léger.');
            $precedent = $ligne['reste'];
        }
    }

    /** L'écran répond et affiche les cartes attendues. */
    public function test_l_ecran_de_pilotage_repond(): void
    {
        $reponse = $this->actingAs($this->utilisateur())->get('/pilotage');

        $reponse->assertOk();
        $reponse->assertSee('Recouvrement');
        $reponse->assertSee('Reste à recouvrer');
        $reponse->assertSee('UE en difficulté');
        $reponse->assertSee('Évolution');
        $reponse->assertSee('Impayés les plus lourds');
    }

    /** Une année inconnue retombe sur la plus récente, sans erreur. */
    public function test_une_annee_inconnue_ne_plante_pas(): void
    {
        $reponse = $this->actingAs($this->utilisateur())->get('/pilotage?annee=999999');

        $reponse->assertOk();
        $reponse->assertSee('Recouvrement');
    }

    /** L'écran est refusé à qui n'a pas la permission Audit. */
    public function test_l_ecran_est_protege(): void
    {
        $sansDroit = User::where('login', 'test')->first();

        if (! $sansDroit) {
            $this->markTestSkipped('Compte sans rôle absent de cette base.');
        }

        $this->actingAs($sansDroit)->get('/pilotage')->assertForbidden();
    }

    /** L'export produit un CSV exploitable. */
    public function test_l_export_produit_un_csv(): void
    {
        $reponse = $this->actingAs($this->utilisateur())->get('/pilotage/export');

        $reponse->assertOk();
        $this->assertStringContainsString('text/csv', $reponse->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $reponse->headers->get('Content-Disposition'));

        $contenu = $reponse->getContent();

        // BOM UTF-8 : sans lui, Excel affiche « Ã© » à la place de « é ».
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenu);
        $this->assertStringContainsString('Taux de recouvrement', $contenu);
        $this->assertStringContainsString(';', $contenu);
    }
}
