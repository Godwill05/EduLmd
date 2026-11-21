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
            /* Un matricule inconnu doit répondre 404 : une redirection
               silencieuse masquait l'erreur et cassait les liens partagés. */
            abort(404, "Aucun étudiant ne porte le matricule « {$matricule} ».");
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

    /**
     * POST /etudiants/fiche/{matricule}/photo
     *
     * Mise à jour de la photo de la fiche.
     *
     * Les images sont écrites dans public/assets/img/etudiants (accès direct
     * par le serveur web, sans lien symbolique à créer), et non dans
     * storage/app/public comme le faisait la création : la vue affichait
     * /storage/... qui n'existe pas sur ce déploiement, d'où des photos
     * invisibles malgré un envoi réussi.
     */
    public function photo(Request $request, $matricule)
    {
        $etudiant = Etudiant::find($matricule);

        if (! $etudiant) {
            abort(404, "Aucun étudiant ne porte le matricule « {$matricule} ».");
        }

        $donnees = $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:4096',
        ], [
            'photo.required' => 'Choisissez une image.',
            'photo.image' => 'Le fichier envoyé n’est pas une image.',
            'photo.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
            'photo.max' => 'L’image ne doit pas dépasser 4 Mo.',
        ]);

        $dossier = public_path('assets/img/etudiants');

        if (! is_dir($dossier)) {
            @mkdir($dossier, 0755, true);
        }

        $extension = strtolower($donnees['photo']->extension() ?: 'jpg');
        $nomFichier = $etudiant->MATRICULE.'.'.$extension;

        /* Ancienne photo : on la retire pour ne pas laisser de fichiers
           orphelins quand le format change (.jpg → .png). */
        if ($etudiant->photo && $etudiant->photo !== $nomFichier) {
            $ancien = $dossier.DIRECTORY_SEPARATOR.$etudiant->photo;
            if (is_file($ancien)) {
                @unlink($ancien);
            }
        }

        $donnees['photo']->move($dossier, $nomFichier);

        $etudiant->photo = $nomFichier;
        $etudiant->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'photo' => $nomFichier,
                'url' => asset('assets/img/etudiants/'.$nomFichier).'?v='.time(),
                'message' => 'Photo mise à jour.',
            ]);
        }

        return back()->with('succes', 'Photo mise à jour.');
    }
}
