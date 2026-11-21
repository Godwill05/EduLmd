<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    /**
     * Page « Gestion des notes ».
     *
     * GET  : formulaire de filtres + éventuellement la liste des évaluations.
     * POST : mêmes filtres, liste des évaluations filtrées.
     *
     * La page affiche les évaluations ; la saisie des notes se fait ensuite
     * dans une console dédiée, chargée en Ajax (voir `console()`), ce qui
     * permet d'enchaîner les évaluations sans quitter la page.
     */
    public function index(Request $request)
    {
        if (! $request->isMethod('post')) {
            return view('notes', [
                'anneesEtude' => AnneeEtude::orderBy('Libelle_AnEtude')->get(),
                'anneesAcademiques' => AnneeAcademique::orderByDesc('id_Annee_Acad')->get(),
            ]);
        }

        $evaluations = $this->evaluationsFiltrees($request);

        return view('notes', [
            'evaluations' => $evaluations,
            'anneesEtude' => AnneeEtude::orderBy('Libelle_AnEtude')->get(),
            'anneesAcademiques' => AnneeAcademique::orderByDesc('id_Annee_Acad')->get(),
        ]);
    }

    /**
     * Liste des évaluations correspondant aux filtres du formulaire.
     */
    protected function evaluationsFiltrees(Request $request)
    {
        if (! $request->filled(['anneeAcademique', 'semestreEtude', 'ue', 'ecue'])) {
            return collect();
        }

        $sql = 'SELECT attr_ecue_ue.Id_Attr_ue_Sem as nattr,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD,'
            . 'annee_etude.Code_Annee_Etude,attr_ue_classe.`id_S_Gr_Spe` as clas ,Nom_Classe,'
            . 'attr_ue_classe.`codeUe` as ue ,libelleUe,attr_ecue_ue.codeEcu as ecue,libelleEcu,'
            . 'attr_ecue_ue.numeroProf as nprof,nomProf,prenomsProf,telProf,`numeroEval`,'
            . 'evaluation.`dateEval`,`heureDebutEval`,`dureeEval`,`codeGroupe`,evaluation.codTypEval,libTypEval '
            . 'FROM  annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,'
            . 'attr_ecue_ue,ecue,professeur,evaluation,typeevaluation  '
            . 'WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` '
            . 'AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe` '
            . 'AND attr_ue_classe.`codeUe`= ue.`codeUe` '
            . 'AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem '
            . 'and attr_ecue_ue.codeEcu=ecue.codeEcu '
            . 'and attr_ecue_ue.numeroProf=professeur.numeroProf '
            . 'and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude '
            . 'AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe '
            . 'AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem '
            . 'AND evaluation.codeEcu=attr_ecue_ue.codeEcu '
            . 'AND evaluation.codTypEval=typeevaluation.codTypEval '
            . 'AND attr_ue_classe.`id_Annee_Acad`=? AND attr_ue_classe.`id_S_Gr_Spe`=? '
            . 'AND attr_ue_classe.Id_Attr_ue_Sem=? AND evaluation.codeEcu=? '
            . 'ORDER BY `dateEval`,`heureDebutEval` ASC';

        return collect(DB::select($sql, [
            (int) $request->anneeAcademique,
            (int) $request->semestreEtude,
            (int) $request->ue,
            (string) $request->ecue,
        ]));
    }

    /**
     * Console de saisie d'une évaluation, appelée en Ajax depuis la page.
     *
     * Renvoie du JSON : entête de l'évaluation + une ligne par étudiant
     * inscrit, avec la note déjà saisie s'il y en a une.
     *
     * Aucune vue Blade, aucun accès direct par URL : si l'évaluation
     * n'existe pas on répond 404 proprement au lieu de planter.
     */
    public function console(Request $request)
    {
        $donnees = $request->validate([
            'id_eval' => 'required|integer',
            'id_annee' => 'required|integer',
            'id_an_etude' => 'required|string|max:50',
        ]);

        $evaluation = DB::table('evaluation')
            ->leftJoin('typeevaluation', 'typeevaluation.codTypEval', '=', 'evaluation.codTypEval')
            ->leftJoin('ecue', 'ecue.codeEcu', '=', 'evaluation.codeEcu')
            ->where('evaluation.numeroEval', $donnees['id_eval'])
            ->select(
                'evaluation.numeroEval',
                'evaluation.codeEcu',
                'evaluation.dateEval',
                'evaluation.heureDebutEval',
                'evaluation.dureeEval',
                'typeevaluation.libTypEval',
                'ecue.libelleEcu'
            )
            ->first();

        if (! $evaluation) {
            return response()->json(['message' => 'Cette évaluation est introuvable.'], 404);
        }

        $annee = DB::table('anneeacademique')
            ->where('id_Annee_Acad', $donnees['id_annee'])
            ->value('ANNEE_ACAD');

        if ($annee === null) {
            return response()->json(['message' => 'Année académique introuvable.'], 404);
        }

        /* Les notes déjà saisies, en une seule requête. */
        $notes = DB::table('obtenir')
            ->where('numeroEval', $donnees['id_eval'])
            ->pluck('Note', 'numeroInscription');

        $etudiants = DB::table('etudiant')
            ->join('inscriptions', 'inscriptions.MATRICULE', '=', 'etudiant.MATRICULE')
            ->where('inscriptions.Id_Annee', (int) $donnees['id_annee'])
            ->where('inscriptions.ID_FILIERE', $donnees['id_an_etude'])
            ->orderBy('etudiant.nomEtu')
            ->orderBy('etudiant.prenomsEtu')
            ->select('etudiant.MATRICULE', 'etudiant.nomEtu', 'etudiant.prenomsEtu')
            ->get()
            ->map(function ($e) use ($notes) {
                return [
                    'matricule' => $e->MATRICULE,
                    'nom' => $e->nomEtu,
                    'prenoms' => $e->prenomsEtu,
                    'note' => $notes[$e->MATRICULE] ?? null,
                ];
            });

        /* Les statistiques ne portent que sur les étudiants de la promotion
           affichée : sans ce filtrage, on comptait les notes de toute
           l'évaluation (25 « saisies » pour 6 étudiants). */
        $notesAffichees = $etudiants->pluck('note')->filter(fn ($n) => $n !== null && $n !== '');
        $saisies = $notesAffichees->count();
        $total = $etudiants->count();

        return response()->json([
            'evaluation' => [
                'numero' => $evaluation->numeroEval,
                'ecue' => $evaluation->codeEcu,
                'libelle' => $evaluation->libelleEcu,
                'type' => $evaluation->libTypEval,
                'date' => $evaluation->dateEval,
                'heure' => $evaluation->heureDebutEval,
                'duree' => $evaluation->dureeEval,
            ],
            'anneeAcademique' => $annee,
            'anneeEtude' => $donnees['id_an_etude'],
            'etudiants' => $etudiants,
            'statistiques' => [
                'total' => $total,
                'saisies' => $saisies,
                'restantes' => $total - $saisies,
                'moyenne' => $saisies
                    ? round($notesAffichees->filter(fn ($n) => is_numeric($n))->avg(), 2)
                    : null,
            ],
        ]);
    }

    /**
     * Enregistrement des notes saisies dans la console.
     *
     * Renvoie du JSON : la console attend un succès pour confirmer la saisie
     * et mettre à jour son compteur sans recharger la page.
     */
    public function insererNotes(Request $request)
    {
        $donnees = $request->validate([
            'numeroEvaluation' => 'required|integer',
        ]);

        $numeroEval = (int) $donnees['numeroEvaluation'];

        /* Toutes les entrées sauf les champs techniques sont des
           « matricule => note ». On ignore les valeurs vides et on refuse
           une note hors de l'échelle 0–20 plutôt que de l'enregistrer. */
        $notes = $request->except(['_token', '_method', 'numeroEvaluation']);

        $enregistrees = 0;
        $ignorees = 0;

        foreach ($notes as $numeroInscription => $note) {
            if ($note === null || $note === '' || ! is_numeric($note)) {
                $ignorees++;
                continue;
            }

            $valeur = round((float) $note, 2);
            if ($valeur < 0 || $valeur > 20) {
                $ignorees++;
                continue;
            }

            Note::updateOrCreate(
                [
                    'numeroEval' => $numeroEval,
                    'numeroInscription' => $numeroInscription,
                ],
                [
                    'Note' => $valeur,
                    'login' => Auth::user()->login,
                    'loginupdate' => Auth::user()->login,
                    'momentupdate' => now(),
                ]
            );

            $enregistrees++;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'enregistrees' => $enregistrees,
                'ignorees' => $ignorees,
                'message' => $enregistrees.' note(s) enregistrée(s)'
                    .($ignorees ? ', '.$ignorees.' valeur(s) ignorée(s)' : '').'.',
            ]);
        }

        return back()->with('succes', $enregistrees.' note(s) enregistrée(s).');
    }
}
