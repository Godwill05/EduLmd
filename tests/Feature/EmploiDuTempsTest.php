<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EmploiDuTemps;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Emploi du temps : grille hebdomadaire par classe.
 *
 * Aucune table n'a été créée : les créneaux existent déjà dans
 * `prog_cours`, alimentée par « Gestion des cours ». Ces tests protègent
 * la lecture — c'est là que se joue la justesse de l'affichage.
 */
class EmploiDuTempsTest extends TestCase
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

    /** Les heures sortent dans un format constant. */
    public function test_les_heures_sont_normalisees(): void
    {
        $this->assertSame('08:00', EmploiDuTemps::heure('08:00'));
        $this->assertSame('08:00', EmploiDuTemps::heure('8:00:00'));
        $this->assertSame('19:36', EmploiDuTemps::heure('19:36:00'));
        $this->assertNull(EmploiDuTemps::heure(''));
        $this->assertNull(EmploiDuTemps::heure(null));
        $this->assertNull(EmploiDuTemps::heure('pas une heure'));
    }

    /** Un intervalle de dates couvre bien les jours qu'il traverse. */
    public function test_les_jours_couverts_sont_exacts(): void
    {
        // Du lundi 14 au mercredi 16 juillet 2025 : trois jours.
        $jours = EmploiDuTemps::joursCouverts('2025-07-14', '2025-07-16');
        sort($jours);

        $this->assertSame([1, 2, 3], $jours, 'Lundi, mardi, mercredi.');

        // Un intervalle d'une seule journée ne renvoie que ce jour.
        $this->assertSame([5], EmploiDuTemps::joursCouverts('2025-07-18', '2025-07-18'));
    }

    /** Un intervalle aberrant ne fait pas exploser la grille. */
    public function test_un_intervalle_absurde_ne_plante_pas(): void
    {
        $jours = EmploiDuTemps::joursCouverts('2020-01-01', '2025-12-31');

        $this->assertCount(1, $jours, 'Un intervalle de plusieurs années se réduit à son premier jour.');
    }

    /** Une date de fin absente ne casse rien. */
    public function test_une_date_de_fin_absente_ne_plante_pas(): void
    {
        $this->assertSame([1], EmploiDuTemps::joursCouverts('2025-07-14', null));
        $this->assertSame([], EmploiDuTemps::joursCouverts(null, '2025-07-14'));
    }

    /** La grille réelle d'une classe contient des séances placées. */
    public function test_la_grille_d_une_classe_est_remplie(): void
    {
        $classes = EmploiDuTemps::classes();

        if (count($classes) === 0) {
            $this->markTestSkipped('Aucun créneau saisi dans cette base.');
        }

        $grille = EmploiDuTemps::grille((int) $classes[0]->id_S_Gr_Spe);

        $this->assertGreaterThan(0, $grille['total']);
        $this->assertNotNull($grille['depart']);
        $this->assertNotNull($grille['arrivee']);

        foreach ($grille['grille'] as $jour => $heures) {
            $this->assertGreaterThanOrEqual(1, $jour);
            $this->assertLessThanOrEqual(7, $jour);

            foreach ($heures as $heure => $seances) {
                $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', (string) $heure);

                foreach ($seances as $seance) {
                    $this->assertNotEmpty($seance['libelle_ecue']);
                    $this->assertNotEmpty($seance['salle']);
                    $this->assertNotSame('', $seance['debut']);
                }
            }
        }
    }

    /** Le filtre de période restreint réellement les créneaux. */
    public function test_le_filtre_de_periode_restreint(): void
    {
        $classes = EmploiDuTemps::classes();

        if (count($classes) === 0) {
            $this->markTestSkipped('Aucun créneau saisi dans cette base.');
        }

        $id = (int) $classes[0]->id_S_Gr_Spe;

        $tout = EmploiDuTemps::grille($id);
        $loin = EmploiDuTemps::grille($id, '1990-01-01', '1990-12-31');

        $this->assertSame(0, $loin['total'], 'Aucune séance ne peut avoir lieu en 1990.');
        $this->assertGreaterThan(0, $tout['total']);
    }

    /** Une classe sans créneau renvoie une grille vide, pas une erreur. */
    public function test_une_classe_sans_creneau_ne_plante_pas(): void
    {
        $grille = EmploiDuTemps::grille(999999);

        $this->assertSame(0, $grille['total']);
        $this->assertSame([], $grille['grille']);
        $this->assertNull($grille['depart']);
    }

    /** L'écran répond et affiche les sept jours. */
    public function test_l_ecran_affiche_la_grille(): void
    {
        $reponse = $this->actingAs($this->utilisateur())->get('/emploi-du-temps');

        $reponse->assertOk();

        foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $jour) {
            $reponse->assertSee($jour);
        }
    }

    /** L'impression est une page autonome, sans dépendance externe. */
    public function test_l_impression_est_autonome(): void
    {
        $classes = EmploiDuTemps::classes();

        if (count($classes) === 0) {
            $this->markTestSkipped('Aucun créneau saisi dans cette base.');
        }

        $reponse = $this->actingAs($this->utilisateur())
            ->get('/emploi-du-temps/imprimer?classe=' . $classes[0]->id_S_Gr_Spe);

        $reponse->assertOk();
        $reponse->assertSee('Emploi du temps');
        $reponse->assertSee($classes[0]->Nom_Classe);

        $html = $reponse->getContent();

        // Autonome : aucune feuille de style ni police distante. La page
        // doit s'imprimer même si le réseau est coupé. Le logo, lui, est
        // servi par l'application (asset local) : c'est le seul recours
        // distant admis.
        $this->assertStringNotContainsString('<link rel="stylesheet"', $html);
        $this->assertStringNotContainsString('@import', $html);
        $this->assertStringNotContainsString('<script', $html);

        // Les ressources chargées (src=, link href=) doivent toutes être
        // locales. On ne cherche pas le mot « cdn » dans le texte : il
        // apparaîtrait dans un commentaire, ce qui ferait échouer le test
        // pour une mauvaise raison.
        preg_match_all('#(?:src|href)\s*=\s*["\']([^"\']+)["\']#i', $html, $ressources);
        foreach ($ressources[1] ?? [] as $url) {
            $this->assertMatchesRegularExpression(
                '#^(/|https?://localhost)#',
                $url,
                "La page d'impression ne doit charger aucune ressource externe : {$url}"
            );
        }
        $this->assertStringContainsString('@page', $html, 'Une feuille de style d\'impression est attendue.');
    }

    /** L'impression d'une classe inconnue est refusée proprement. */
    public function test_l_impression_d_une_classe_inconnue_renvoie_404(): void
    {
        $this->actingAs($this->utilisateur())
            ->get('/emploi-du-temps/imprimer?classe=999999')
            ->assertNotFound();
    }

    /** L'emploi du temps exige une connexion. */
    public function test_l_emploi_du_temps_exige_une_connexion(): void
    {
        auth()->logout();

        $this->get('/emploi-du-temps')->assertRedirect();
    }
}
