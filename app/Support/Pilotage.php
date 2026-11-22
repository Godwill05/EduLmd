<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Indicateurs de pilotage pour la direction.
 * ---------------------------------------------------------------------
 * Ce que le tableau de bord descriptif ne disait pas :
 *
 *   - **où part l'argent** : le taux de recouvrement global ne dit pas
 *     quelle filière rapporte et laquelle coûte ;
 *   - **quelles UE font échouer** : une UE où 60 % des inscrits sont en
 *     échec plombe une promotion entière et se repère en une requête ;
 *   - **comment évolue l'établissement** : les effectifs seuls ne disent
 *     rien de la trajectoire (réussite, recouvrement).
 *
 * Toutes les méthodes s'appuient sur des données déjà présentes
 * (`recap_ue.Etat_UE`, `recap_annuel.DecisionJury`, `inscriptions`).
 * Aucune table nouvelle : ces indicateurs sont calculés à la demande.
 *
 * Les requêtes sont volontairement en SQL agrégé et non en Eloquent :
 * `recap_ue` compte 10 000 lignes, `obtenir` 24 000, et charger des
 * modèles pour compter serait dix fois plus lent.
 */
class Pilotage
{
    /**
     * Recouvrement par filière : ce qui était attendu, ce qui est rentré,
     * et le taux.
     *
     * @return array<int, array{filiere: string, attendu: int, encaisse: int, reste: int, taux: float, effectif: int}>
     */
    public static function recouvrementParFiliere(?int $anneeAcad = null, int $limite = 12): array
    {
        $lignes = DB::table('inscriptions')
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->when($anneeAcad, fn ($q) => $q->where('inscriptions.Id_Annee', $anneeAcad))
            ->groupBy('inscriptions.ID_FILIERE')
            ->selectRaw(
                'inscriptions.ID_FILIERE as filiere,
                 COALESCE(annee_etude.Libelle_AnEtude, inscriptions.ID_FILIERE) as libelle,
                 COALESCE(SUM(inscriptions.frais_paye), 0) as attendu,
                 COALESCE(SUM(inscriptions.reste_paye), 0) as reste,
                 COUNT(*) as effectif'
            )
            ->get();

        return $lignes
            ->map(function ($r) {
                $attendu = (int) $r->attendu;
                $reste = (int) $r->reste;
                $encaisse = max(0, $attendu - $reste);

                return [
                    'filiere' => $r->filiere,
                    'libelle' => $r->libelle,
                    'attendu' => $attendu,
                    'encaisse' => $encaisse,
                    'reste' => $reste,
                    'taux' => $attendu > 0 ? round($encaisse / $attendu * 100, 1) : 0.0,
                    'effectif' => (int) $r->effectif,
                ];
            })
            // Les filières sans montant ne disent rien du recouvrement.
            ->filter(fn ($r) => $r['attendu'] > 0)
            ->sortBy('taux')
            ->take($limite)
            ->values()
            ->all();
    }

    /**
     * UE les plus difficiles : taux de réussite le plus bas, pour un effectif
     * suffisant (une UE suivie par 2 étudiants ne prouve rien).
     *
     * `recap_ue.Etat_UE` vaut « V » (validée) ou « NV » (non validée).
     *
     * Deux précautions qui changent le sens du chiffre :
     *
     *  - `$anneeAcad` filtre sur l'année académique. `recap_ue` ne la
     *    porte pas directement, mais `Id_Attr_ue_Sem` désigne une
     *    affectation UE-classe propre à une année : on passe donc par
     *    `attr_ue_classe.id_Annee_Acad`. Sans ce filtre, un « 0 % sur
     *    UEDPU » additionne des promotions qui n'ont rien à voir et le
     *    taux est faux.
     *  - `$anneeEtude` restreint à un niveau (LP1, LP2…). Les mêmes
     *    libellés d'UE reviennent d'un niveau à l'autre avec des
     *    exigences différentes.
     *
     * @return array<int, array{ue: string, code: string, presents: int, reussis: int, taux: float, moyenne: float}>
     */
    public static function ueLesPlusDifficiles(?string $anneeEtude = null, int $minInscrits = 5, int $limite = 10, ?int $anneeAcad = null): array
    {
        $lignes = DB::table('recap_ue')
            ->join('attr_ue_classe', 'recap_ue.Id_Attr_ue_Sem', '=', 'attr_ue_classe.Id_Attr_ue_Sem')
            ->join('ue', 'attr_ue_classe.codeUe', '=', 'ue.codeUe')
            ->when($anneeAcad, fn ($q) => $q->where('attr_ue_classe.id_Annee_Acad', $anneeAcad))
            ->when($anneeEtude, fn ($q) => $q->where('recap_ue.Code_Annee_Etude', $anneeEtude))
            ->groupBy('ue.codeUe', 'ue.libelleUe')
            ->havingRaw('COUNT(*) >= ?', [$minInscrits])
            ->selectRaw(
                'ue.codeUe as code,
                 ue.libelleUe as ue,
                 COUNT(*) as presents,
                 SUM(CASE WHEN recap_ue.Etat_UE = \'V\' THEN 1 ELSE 0 END) as reussis,
                 AVG(recap_ue.Moyenne_UE) as moyenne'
            )
            ->get();

        return $lignes
            ->map(fn ($r) => [
                'code' => $r->code,
                'ue' => $r->ue,
                'presents' => (int) $r->presents,
                'reussis' => (int) $r->reussis,
                'taux' => $r->presents > 0 ? round($r->reussis / $r->presents * 100, 1) : 0.0,
                'moyenne' => round((float) $r->moyenne, 2),
            ])
            ->sortBy('taux')
            ->take($limite)
            ->values()
            ->all();
    }

    /**
     * Réussite globale par année académique : décisions du jury.
     *
     * Les décisions observées en base : « V » (validé), « V + R » (validé
     * avec reprise), « INV » (invalidé), « NV » (non validé).
     * Tout ce qui n'est pas « INV »/« NV » est considéré comme un succès.
     *
     * @return array<int, array{annee: string, total: int, reussis: int, taux: float}>
     */
    public static function reussiteParAnnee(): array
    {
        return DB::table('recap_annuel')
            ->join('anneeacademique', 'recap_annuel.id_Annee_Acad', '=', 'anneeacademique.id_Annee_Acad')
            ->groupBy('anneeacademique.ANNEE_ACAD')
            ->orderBy('anneeacademique.ANNEE_ACAD')
            ->selectRaw(
                'anneeacademique.ANNEE_ACAD as annee,
                 COUNT(*) as total,
                 SUM(CASE WHEN recap_annuel.DecisionJury IN (\'INV\', \'NV\') THEN 0 ELSE 1 END) as reussis'
            )
            ->get()
            ->map(fn ($r) => [
                'annee' => $r->annee,
                'total' => (int) $r->total,
                'reussis' => (int) $r->reussis,
                'taux' => $r->total > 0 ? round($r->reussis / $r->total * 100, 1) : 0.0,
            ])
            ->all();
    }

    /**
     * Évolution des effectifs et du recouvrement, année par année.
     *
     * Sert à répondre à « est-ce que l'établissement grandit en santé ? »
     * et pas seulement « combien d'étudiants cette année ? ».
     *
     * @return array<int, array{annee: string, effectif: int, attendu: int, encaisse: int, taux: float}>
     */
    public static function evolution(): array
    {
        return DB::table('inscriptions')
            ->join('anneeacademique', 'inscriptions.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->groupBy('anneeacademique.ANNEE_ACAD')
            ->orderBy('anneeacademique.ANNEE_ACAD')
            ->selectRaw(
                'anneeacademique.ANNEE_ACAD as annee,
                 COUNT(*) as effectif,
                 COALESCE(SUM(inscriptions.frais_paye), 0) as attendu,
                 COALESCE(SUM(inscriptions.reste_paye), 0) as reste'
            )
            ->get()
            ->map(function ($r) {
                $attendu = (int) $r->attendu;
                $encaisse = max(0, $attendu - (int) $r->reste);

                return [
                    'annee' => $r->annee,
                    'effectif' => (int) $r->effectif,
                    'attendu' => $attendu,
                    'encaisse' => $encaisse,
                    'taux' => $attendu > 0 ? round($encaisse / $attendu * 100, 1) : 0.0,
                ];
            })
            ->all();
    }

    /**
     * Les impayés les plus lourds, avec leur ancienneté.
     *
     * C'est la liste qui permet de relancer : on trie par montant restant
     * puis par ancienneté, et on affiche de quand date l'inscription.
     *
     * @return array<int, array{matricule: string, nom: string, filiere: string, reste: int, annee: string, depuis: int}>
     */
    public static function impayesLesPlusLourds(?int $anneeAcad = null, int $limite = 15): array
    {
        $aujourdhui = now();

        return DB::table('inscriptions')
            ->leftJoin('etudiant', 'inscriptions.MATRICULE', '=', 'etudiant.MATRICULE')
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->leftJoin('anneeacademique', 'inscriptions.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->when($anneeAcad, fn ($q) => $q->where('inscriptions.Id_Annee', $anneeAcad))
            ->where('inscriptions.reste_paye', '>', 0)
            ->orderByDesc('inscriptions.reste_paye')
            ->limit($limite)
            ->selectRaw(
                'inscriptions.MATRICULE as matricule,
                 COALESCE(etudiant.nomEtu, \'\') as nom,
                 COALESCE(etudiant.prenomsEtu, \'\') as prenoms,
                 COALESCE(annee_etude.Libelle_AnEtude, inscriptions.ID_FILIERE) as filiere,
                 inscriptions.reste_paye as reste,
                 anneeacademique.ANNEE_ACAD as annee,
                 inscriptions.created_at as cree'
            )
            ->get()
            ->map(fn ($r) => [
                'matricule' => $r->matricule,
                'nom' => trim($r->nom . ' ' . $r->prenoms),
                'filiere' => $r->filiere,
                'reste' => (int) $r->reste,
                'annee' => $r->annee,
                'depuis' => $r->cree
                    ? (int) \Carbon\Carbon::parse($r->cree)->diffInDays($aujourdhui)
                    : 0,
            ])
            ->all();
    }

    /**
     * Synthèse en quatre chiffres, destinée aux cartes du haut de page.
     *
     * @return array{taux_recouvrement: float, encaisse: int, attendu: int, reste: int, taux_reussite: float, effectif: int, ue_en_difficulte: int}
     */
    public static function synthese(?int $anneeAcad = null): array
    {
        $fin = DB::table('inscriptions')
            ->when($anneeAcad, fn ($q) => $q->where('Id_Annee', $anneeAcad))
            ->selectRaw('COALESCE(SUM(frais_paye), 0) as attendu, COALESCE(SUM(reste_paye), 0) as reste, COUNT(*) as effectif')
            ->first();

        $attendu = (int) ($fin->attendu ?? 0);
        $reste = (int) ($fin->reste ?? 0);
        $encaisse = max(0, $attendu - $reste);

        // Réussite : décisions du jury de l'année demandée.
        $jury = DB::table('recap_annuel')
            ->when($anneeAcad, fn ($q) => $q->where('id_Annee_Acad', $anneeAcad))
            ->selectRaw(
                'COUNT(*) as total,
                 SUM(CASE WHEN DecisionJury IN (\'INV\', \'NV\') THEN 0 ELSE 1 END) as reussis'
            )
            ->first();

        $totalJury = (int) ($jury->total ?? 0);
        $reussis = (int) ($jury->reussis ?? 0);

        /* UE réellement en difficulté (moins de la moitié de réussite).
           `recap_ue` ne porte pas l'année académique : on la retrouve via
           `attr_ue_classe`, sans quoi le compteur additionnerait quatre
           promotions et annoncerait « 61 UE en difficulté » là où l'année
           en cours n'en compte qu'une poignée. */
        $ueDifficiles = DB::table('recap_ue')
            ->join('attr_ue_classe', 'recap_ue.Id_Attr_ue_Sem', '=', 'attr_ue_classe.Id_Attr_ue_Sem')
            ->when($anneeAcad, fn ($q) => $q->where('attr_ue_classe.id_Annee_Acad', $anneeAcad))
            ->groupBy('recap_ue.Id_Attr_ue_Sem')
            ->havingRaw('COUNT(*) >= 5')
            ->havingRaw('SUM(CASE WHEN recap_ue.Etat_UE = \'V\' THEN 1 ELSE 0 END) * 2 < COUNT(*)')
            ->selectRaw('recap_ue.Id_Attr_ue_Sem')
            ->get()
            ->count();

        return [
            'attendu' => $attendu,
            'encaisse' => $encaisse,
            'reste' => $reste,
            'taux_recouvrement' => $attendu > 0 ? round($encaisse / $attendu * 100, 1) : 0.0,
            'effectif' => (int) ($fin->effectif ?? 0),
            'taux_reussite' => $totalJury > 0 ? round($reussis / $totalJury * 100, 1) : 0.0,
            'ue_en_difficulte' => $ueDifficiles,
        ];
    }
}
