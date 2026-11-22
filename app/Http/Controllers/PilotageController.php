<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Support\Pilotage;
use Illuminate\Http\Request;

/**
 * Écran de pilotage : les indicateurs qui aident à décider.
 *
 * Différence avec le tableau de bord (`DashboardController`) : celui-ci
 * décrit l'existant (combien d'étudiants, combien de paiements), celui-là
 * met en évidence ce sur quoi il faut agir — la filière qui recouvre mal,
 * l'UE qui fait échouer, la trajectoire de l'établissement.
 *
 * Les chiffres sont calculés à la demande par `App\Support\Pilotage` :
 * aucune table d'agrégat à maintenir, donc aucun risque de données
 * périmées après une modification de note ou de paiement.
 */
class PilotageController extends Controller
{
    public function index(Request $request)
    {
        $annees = AnneeAcademique::orderByDesc('id_Annee_Acad')->get(['id_Annee_Acad', 'ANNEE_ACAD']);

        $anneeChoisie = $request->filled('annee')
            ? (int) $request->input('annee')
            : optional($annees->first())->id_Annee_Acad;

        /* Un identifiant d'année absent de la base (lien périmé, favori
           pointant sur 2026-2027 avant sa création) ne doit pas produire
           un écran de zéros : on retombe sur l'année la plus récente et
           on le dit à l'utilisateur, sinon il croit à une panne. */
        $anneeDemandee = $anneeChoisie;
        $anneeInconnue = false;

        if (! $annees->contains('id_Annee_Acad', $anneeChoisie)) {
            $anneeInconnue = $anneeChoisie !== null;
            $anneeChoisie = optional($annees->first())->id_Annee_Acad;
        }

        $reussitesParAnnee = collect(Pilotage::reussiteParAnnee())
            ->mapWithKeys(fn ($r) => [$r['annee'] => $r['taux']])
            ->all();

        return view('pilotage', [
            'annees' => $annees,
            'anneeChoisie' => $anneeChoisie,
            'synthese' => Pilotage::synthese($anneeChoisie),
            'evolution' => Pilotage::evolution(),
            'reussitesParAnnee' => $reussitesParAnnee,
            'recouvrement' => Pilotage::recouvrementParFiliere($anneeChoisie),
            'impayes' => Pilotage::impayesLesPlusLourds($anneeChoisie),
            'ueDifficiles' => Pilotage::ueLesPlusDifficiles(null, 5, 10, $anneeChoisie),
            'anneeInconnue' => $anneeInconnue,
            'anneeDemandee' => $anneeDemandee,
        ]);
    }

    /**
     * Export CSV des indicateurs de l'année : un directeur doit pouvoir
     * emporter ces chiffres en réunion sans refaire les calculs.
     *
     * Séparateur « ; » et BOM UTF-8 : c'est ce qu'Excel attend en
     * configuration française, sinon les accents sautent.
     */
    public function export(Request $request)
    {
        $anneeAcad = $request->filled('annee') ? (int) $request->input('annee') : null;
        $annee = $anneeAcad ? AnneeAcademique::find($anneeAcad) : null;
        $synthese = Pilotage::synthese($anneeAcad);

        $lignes = [];
        $lignes[] = ['Indicateur', 'Valeur'];
        $lignes[] = ['Année académique', $annee->ANNEE_ACAD ?? 'toutes'];
        $lignes[] = ['Effectif inscrit', $synthese['effectif']];
        $lignes[] = ['Montant attendu (F)', $synthese['attendu']];
        $lignes[] = ['Montant encaissé (F)', $synthese['encaisse']];
        $lignes[] = ['Reste à recouvrer (F)', $synthese['reste']];
        $lignes[] = ['Taux de recouvrement (%)', $synthese['taux_recouvrement']];
        $lignes[] = ['Taux de réussite (%)', $synthese['taux_reussite']];
        $lignes[] = ['UE en difficulté', $synthese['ue_en_difficulte']];
        $lignes[] = [];
        $lignes[] = ['Filière', 'Effectif', 'Encaissé (F)', 'Reste (F)', 'Taux (%)'];

        foreach (Pilotage::recouvrementParFiliere($anneeAcad, 100) as $r) {
            $lignes[] = [$r['libelle'], $r['effectif'], $r['encaisse'], $r['reste'], $r['taux']];
        }

        $lignes[] = [];
        $lignes[] = ['UE', 'Code', 'Inscrits', 'Validée', 'Moyenne', 'Taux (%)'];

        foreach (Pilotage::ueLesPlusDifficiles(null, 5, 100, $anneeAcad) as $r) {
            $lignes[] = [$r['ue'], $r['code'], $r['presents'], $r['reussis'], $r['moyenne'], $r['taux']];
        }

        $contenu = "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
        foreach ($lignes as $ligne) {
            $contenu .= implode(';', array_map(
                fn ($v) => is_string($v) ? '"' . str_replace('"', '""', $v) . '"' : (string) $v,
                $ligne
            )) . "\r\n";
        }

        $nom = 'pilotage_' . ($annee->ANNEE_ACAD ?? 'toutes') . '_' . date('Ymd_His') . '.csv';

        return response($contenu, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nom . '"',
        ]);
    }
}
