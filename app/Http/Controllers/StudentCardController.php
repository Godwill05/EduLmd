<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Http\Request;

/**
 * Fiche étudiant — cible des liens « voir la fiche » de la recherche
 * globale et de la page Qualité des données.
 *
 * Regroupe sur un seul écran : identité, contacts, inscriptions par année,
 * situation financière et derniers paiements, avec l'action d'encaissement.
 */
class StudentCardController extends Controller
{
    /**
     * GET /etudiants/fiche/{matricule}
     */
    public function show($matricule)
    {
        $etudiant = Etudiant::find($matricule);

        if (!$etudiant) {
            return redirect()->route('etudiants.index')
                ->with('echec', "Aucun étudiant ne porte le matricule « {$matricule} ».");
        }

        /* Inscriptions, enrichies de l'année académique et de l'année d'étude.
           On passe par des jointures explicites : les modèles concernés
           n'exposent pas toujours de relations Eloquent fiables. */
        $inscriptions = Inscription::query()
            ->leftJoin('anneeacademique', 'inscriptions.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->leftJoin('statut', 'inscriptions.codeStatut', '=', 'statut.codeStatut')
            ->where('inscriptions.MATRICULE', $matricule)
            ->orderByDesc('inscriptions.Id_Annee')
            ->orderBy('inscriptions.ID_FILIERE')
            ->get([
                'inscriptions.MATRICULE',
                'inscriptions.Id_Annee',
                'inscriptions.ID_FILIERE',
                'inscriptions.frais_paye',
                'inscriptions.reste_paye',
                'inscriptions.S_Finance',
                'inscriptions.codeStatut',
                'anneeacademique.ANNEE_ACAD',
                'annee_etude.Libelle_AnEtude',
                'statut.LibStatut',
            ]);

        /* Nombre de paiements et montant encaissé par inscription. */
        $paiementsParInscription = Paiement::query()
            ->where('MATRICULE', $matricule)
            ->selectRaw('Id_Annee, ID_FILIERE, COUNT(*) as nb, COALESCE(SUM(montantPaye), 0) as total')
            ->groupBy('Id_Annee', 'ID_FILIERE')
            ->get()
            ->keyBy(fn($p) => $p->Id_Annee . '|' . $p->ID_FILIERE);

        foreach ($inscriptions as $ins) {
            $cle = $ins->Id_Annee . '|' . $ins->ID_FILIERE;
            $ligne = $paiementsParInscription->get($cle);
            $ins->nb_paiements = (int) ($ligne->nb ?? 0);
            $ins->total_paye = (int) ($ligne->total ?? 0);
            $ins->attendu = (int) $ins->frais_paye;
            $ins->reste = (int) $ins->reste_paye;

            /* Taux de couverture de cette inscription, calculé ici pour
               que la vue reste sans logique. */
            $ins->taux = $ins->attendu > 0
                ? min(100, (int) round($ins->total_paye / $ins->attendu * 100))
                : 0;

            $ins->solde = $ins->reste <= 0;
        }

        /* Totaux de l'étudiant. */
        $totalAttendu = $inscriptions->sum('attendu');
        $totalPaye = $inscriptions->sum('total_paye');
        $totalReste = $inscriptions->sum('reste');
        $solde = $totalReste <= 0;

        /* Derniers paiements, tous confondus. */
        $derniersPaiements = Paiement::query()
            ->leftJoin('anneeacademique', 'paiements.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->leftJoin('annee_etude', 'paiements.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->where('paiements.MATRICULE', $matricule)
            ->orderByDesc('paiements.datePaiement')
            ->orderByDesc('paiements.id')
            ->limit(10)
            ->get([
                'paiements.id',
                'paiements.montantPaye',
                'paiements.datePaiement',
                'paiements.modePaiement',
                'paiements.numeroTranche',
                'anneeacademique.ANNEE_ACAD',
                'annee_etude.Libelle_AnEtude',
            ]);

        /* Initiales pour l'avatar de repli (pas de photo). */
        $initiales = mb_strtoupper(
            mb_substr((string) $etudiant->nomEtu, 0, 1)
            . mb_substr((string) $etudiant->prenomsEtu, 0, 1)
        );

        /* Inscription courante : la plus récente, celle que ciblent les
           actions de premier niveau (engagement, prévisions). */
        $inscriptionCourante = $inscriptions->first();

        /* Taux de couverture global. */
        $tauxGlobal = $totalAttendu > 0
            ? min(100, (int) round($totalPaye / $totalAttendu * 100))
            : 0;

        return view('fiche-etudiant', compact(
            'etudiant',
            'inscriptions',
            'derniersPaiements',
            'totalAttendu',
            'totalPaye',
            'totalReste',
            'solde',
            'initiales',
            'tauxGlobal',
            'inscriptionCourante'
        ));
    }
}
