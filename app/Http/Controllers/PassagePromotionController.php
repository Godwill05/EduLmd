<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\AttrEcueUe;
use App\Models\AttrUeClasse;
use App\Models\FiliereCpl;
use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PassagePromotionController extends Controller
{
    /**
     * Obtenir la classe supérieure d'une année d'étude.
     * Ex: LP1,2-GC -> LP3,4-GC -> LP5,6-GC
     */
    public static function getNextAnneeEtude(string $codeAnneeEtude): ?string
    {
        // 1. Recherche via la table des filières complètes si configurée
        $filiere1 = FiliereCpl::where('annee1', $codeAnneeEtude)->first();
        if ($filiere1 && !empty($filiere1->annee2)) {
            return $filiere1->annee2;
        }

        $filiere2 = FiliereCpl::where('annee2', $codeAnneeEtude)->first();
        if ($filiere2 && !empty($filiere2->annee3)) {
            return $filiere2->annee3;
        }

        // 2. Recherche automatique par motif de code (ex: 1,2-GC -> 3,4-GC -> 5,6-GC)
        if (preg_match('/1,2-([A-Za-z0-9_]+)$/', $codeAnneeEtude, $matches)) {
            $specialite = $matches[1];
            $next = AnneeEtude::where(function ($q) use ($specialite) {
                $q->where('Code_Annee_Etude', 'like', "%3,4%{$specialite}")
                    ->orWhere('Libelle_AnEtude', 'like', "%{$specialite}%2%");
            })->first();

            if ($next) {
                return $next->Code_Annee_Etude;
            }
        } elseif (preg_match('/3,4-([A-Za-z0-9_]+)$/', $codeAnneeEtude, $matches)) {
            $specialite = $matches[1];
            $next = AnneeEtude::where(function ($q) use ($specialite) {
                $q->where('Code_Annee_Etude', 'like', "%5,6%{$specialite}")
                    ->orWhere('Libelle_AnEtude', 'like', "%{$specialite}%3%");
            })->first();

            if ($next) {
                return $next->Code_Annee_Etude;
            }
        }

        // 3. Recherche par libellé (ex: "... 1" -> "... 2", "... 2" -> "... 3")
        $current = AnneeEtude::find($codeAnneeEtude);
        if ($current) {
            $libelle = trim($current->Libelle_AnEtude);
            if (preg_match('/^(.*?)(?:\s+)?1$/', $libelle, $m)) {
                $base = trim($m[1]);
                $next = AnneeEtude::where('Libelle_AnEtude', 'like', "{$base}%2")->first();
                if ($next) {
                    return $next->Code_Annee_Etude;
                }
            } elseif (preg_match('/^(.*?)(?:\s+)?2$/', $libelle, $m)) {
                $base = trim($m[1]);
                $next = AnneeEtude::where('Libelle_AnEtude', 'like', "{$base}%3")->first();
                if ($next) {
                    return $next->Code_Annee_Etude;
                }
            }
        }

        return null; // Fin de cycle Licence ou pas de niveau supérieur
    }

    /**
     * Reconduire / Attribuer automatiquement les UEs et ECUEs de l'année source vers l'année cible.
     */
    public static function reconduireUes($sourceYearId, $targetYearId, $author = null): array
    {
        $author = $author ?? (Auth::user()->login ?? 'Système');

        $ueSource = AttrUeClasse::with('attrEcueUes')
            ->where('id_Annee_Acad', $sourceYearId)
            ->get();

        $copiedUe = 0;
        $copiedEcue = 0;
        $skippedUe = 0;

        foreach ($ueSource as $ue) {
            // Vérifier si cette UE est déjà attribuée à cette classe pour l'année cible
            $targetUe = AttrUeClasse::where('id_Annee_Acad', $targetYearId)
                ->where('id_S_Gr_Spe', $ue->id_S_Gr_Spe)
                ->where('codeUe', $ue->codeUe)
                ->first();

            if (!$targetUe) {
                $targetUe = new AttrUeClasse();
                $targetUe->id_Annee_Acad = $targetYearId;
                $targetUe->id_S_Gr_Spe = $ue->id_S_Gr_Spe;
                $targetUe->codeUe = $ue->codeUe;
                $targetUe->Id_Cat_Ue = $ue->Id_Cat_Ue;
                $targetUe->idImportance = $ue->idImportance;
                $targetUe->Seuil_Ue = $ue->Seuil_Ue;
                $targetUe->Credit = $ue->Credit;
                $targetUe->Auteur = $author;
                $targetUe->Moment = now();
                $targetUe->save();

                $copiedUe++;
            } else {
                $skippedUe++;
            }

            // Reconduire les ECUEs associées
            if ($targetUe && $ue->attrEcueUes) {
                foreach ($ue->attrEcueUes as $ecue) {
                    $ecueExists = AttrEcueUe::where('Id_Attr_ue_Sem', $targetUe->Id_Attr_ue_Sem)
                        ->where('codeEcu', $ecue->codeEcu)
                        ->exists();

                    if (!$ecueExists) {
                        $newEcue = new AttrEcueUe();
                        $newEcue->Id_Attr_ue_Sem = $targetUe->Id_Attr_ue_Sem;
                        $newEcue->pedagogique = $ecue->pedagogique;
                        $newEcue->codeEcu = $ecue->codeEcu;
                        $newEcue->Seuil_Ecu = $ecue->Seuil_Ecu;
                        $newEcue->Ponderation = $ecue->Ponderation;
                        $newEcue->Eva_Mode = $ecue->Eva_Mode;
                        $newEcue->PoidsDev = $ecue->PoidsDev;
                        $newEcue->PoidsPart = $ecue->PoidsPart;
                        $newEcue->Observation = $ecue->Observation;
                        $newEcue->numeroProf = $ecue->numeroProf;
                        $newEcue->MasseHoraire = $ecue->MasseHoraire;
                        $newEcue->MasseHoraireExec = $ecue->MasseHoraireExec;
                        $newEcue->Auteur = $author;
                        $newEcue->Moment = now();
                        $newEcue->save();

                        $copiedEcue++;
                    }
                }
            }
        }

        return [
            'copied_ue' => $copiedUe,
            'copied_ecue' => $copiedEcue,
            'skipped_ue' => $skippedUe,
            'total_source' => $ueSource->count(),
        ];
    }

    /**
     * Affichage de la page de passage de promotion.
     */
    public function index(Request $request)
    {
        $annees = AnneeAcademique::orderBy('id_Annee_Acad', 'desc')->get();
        $sourceYearId = $request->get('source_year');
        $targetYearId = $request->get('target_year');

        $sourceYear = null;
        $targetYear = null;
        $students = [];
        $stats = [
            'total_source' => 0,
            'eligible' => 0,
            'already_transferred' => 0,
            'end_of_cycle' => 0,
            'no_next_class' => 0,
        ];
        $ueStats = [
            'source_count' => 0,
            'target_count' => 0,
            'target_ecue_count' => 0,
        ];
        $canRollbackCount = 0;

        if ($sourceYearId && $targetYearId) {
            $sourceYear = AnneeAcademique::find($sourceYearId);
            $targetYear = AnneeAcademique::find($targetYearId);

            if ($sourceYear && $targetYear && $sourceYearId != $targetYearId) {
                // Inscriptions de l'année source avec relations
                $inscriptionsSource = Inscription::with(['etudiant', 'anneeEtude'])
                    ->where('Id_Annee', $sourceYearId)
                    ->orderBy('ID_FILIERE')
                    ->get();

                $stats['total_source'] = $inscriptionsSource->count();

                // Récupérer les inscriptions déjà existantes dans l'année cible pour éviter les doublons
                $inscriptionsTarget = Inscription::where('Id_Annee', $targetYearId)
                    ->get()
                    ->keyBy('MATRICULE');

                // Récupérer le cache de toutes les années d'étude pour les libellés
                $allAnneesEtudes = AnneeEtude::all()->keyBy('Code_Annee_Etude');

                foreach ($inscriptionsSource as $inscr) {
                    $matricule = $inscr->MATRICULE;
                    $currentCode = $inscr->ID_FILIERE;
                    $nextCode = self::getNextAnneeEtude($currentCode);
                    $nextLabel = $nextCode && isset($allAnneesEtudes[$nextCode]) ? $allAnneesEtudes[$nextCode]->Libelle_AnEtude : null;

                    $alreadyInTarget = $inscriptionsTarget->has($matricule);
                    $targetInscription = $alreadyInTarget ? $inscriptionsTarget->get($matricule) : null;

                    $status = 'eligible';
                    $statusBadge = 'success';
                    $statusMessage = 'Prêt pour le passage';

                    if ($alreadyInTarget) {
                        $status = 'already_transferred';
                        $statusBadge = 'info';
                        $statusMessage = 'Déjà inscrit en ' . ($targetInscription ? $targetInscription->ID_FILIERE : 'année cible');
                        $stats['already_transferred']++;
                    } elseif (!$nextCode) {
                        // Est-ce une 3ème année (fin de cycle) ou une filière sans suite ?
                        if (str_contains($currentCode, '5,6') || (isset($inscr->anneeEtude) && str_contains($inscr->anneeEtude->Libelle_AnEtude, '3'))) {
                            $status = 'end_of_cycle';
                            $statusBadge = 'secondary';
                            $statusMessage = 'Fin de cycle Licence (Pas de Master)';
                            $stats['end_of_cycle']++;
                        } else {
                            $status = 'no_next_class';
                            $statusBadge = 'warning';
                            $statusMessage = 'Niveau supérieur non défini';
                            $stats['no_next_class']++;
                        }
                    } else {
                        $stats['eligible']++;
                    }

                    $students[] = [
                        'matricule' => $matricule,
                        'nom' => $inscr->etudiant ? $inscr->etudiant->nomEtu : 'Inconnu',
                        'prenoms' => $inscr->etudiant ? $inscr->etudiant->prenomsEtu : '',
                        'current_code' => $currentCode,
                        'current_label' => $inscr->anneeEtude ? $inscr->anneeEtude->Libelle_AnEtude : $currentCode,
                        'next_code' => $nextCode,
                        'next_label' => $nextLabel ?? $nextCode,
                        'frais_paye' => $inscr->frais_paye,
                        'codeStatut' => $inscr->codeStatut,
                        'status' => $status,
                        'statusBadge' => $statusBadge,
                        'statusMessage' => $statusMessage,
                        'can_transfer' => ($status === 'eligible'),
                    ];
                }

                // Statistiques sur les UEs de l'année cible
                $ueStats['source_count'] = AttrUeClasse::where('id_Annee_Acad', $sourceYearId)->count();
                $ueTargetIds = AttrUeClasse::where('id_Annee_Acad', $targetYearId)->pluck('Id_Attr_ue_Sem');
                $ueStats['target_count'] = $ueTargetIds->count();
                $ueStats['target_ecue_count'] = AttrEcueUe::whereIn('Id_Attr_ue_Sem', $ueTargetIds)->count();

                // Vérifier s'il y a des inscriptions de transfert à annuler dans l'année cible
                $canRollbackCount = Inscription::where('Id_Annee', $targetYearId)
                    ->where(function ($q) use ($sourceYear) {
                        $q->where('Observations', 'like', "%Transfert promotion%")
                            ->orWhere('Observations', 'like', "%{$sourceYear->ANNEE_ACAD}%");
                    })
                    ->count();
            }
        }

        return view('passagePromotion', compact(
            'annees',
            'sourceYearId',
            'targetYearId',
            'sourceYear',
            'targetYear',
            'students',
            'stats',
            'ueStats',
            'canRollbackCount'
        ));
    }

    /**
     * Effectuer le transfert / passage des étudiants.
     */
    public function transferer(Request $request)
    {
        $request->validate([
            'source_year' => 'required|different:target_year',
            'target_year' => 'required',
        ]);

        $sourceYear = AnneeAcademique::findOrFail($request->source_year);
        $targetYear = AnneeAcademique::findOrFail($request->target_year);

        $selectedMatricules = $request->input('selected_students', []);
        $reconduireUes = $request->boolean('reconduire_ues', true);

        // Si aucun étudiant sélectionné explicitement via checkbox, on prend tous les éligibles
        $query = Inscription::where('Id_Annee', $sourceYear->id_Annee_Acad);
        if (!empty($selectedMatricules)) {
            $query->whereIn('MATRICULE', $selectedMatricules);
        }

        $inscriptionsSource = $query->get();

        if ($inscriptionsSource->isEmpty()) {
            return back()->with('echec', 'Aucun étudiant trouvé à transférer.');
        }

        $transferredCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            // 1. Attribution / Reconduction automatique des UEs et ECUEs si demandé
            $ueResult = null;
            if ($reconduireUes) {
                $ueResult = self::reconduireUes($sourceYear->id_Annee_Acad, $targetYear->id_Annee_Acad, Auth::user()->login ?? 'Système');
            }

            // 2. Transfert des inscriptions
            foreach ($inscriptionsSource as $inscr) {
                $matricule = $inscr->MATRICULE;

                // Anti-doublon : déjà inscrit dans la nouvelle année ?
                $alreadyExists = Inscription::where('MATRICULE', $matricule)
                    ->where('Id_Annee', $targetYear->id_Annee_Acad)
                    ->exists();

                if ($alreadyExists) {
                    $skippedCount++;
                    continue;
                }

                // Déterminer la classe suivante
                $nextCode = self::getNextAnneeEtude($inscr->ID_FILIERE);
                if (!$nextCode) {
                    $skippedCount++;
                    continue; // Pas de classe supérieure
                }

                // Créer la nouvelle inscription (l'étudiant ne se duplique pas, seul un lien d'inscription est créé)
                $nouvelleInscription = new Inscription();
                $nouvelleInscription->MATRICULE = $matricule;
                $nouvelleInscription->Id_Annee = $targetYear->id_Annee_Acad;
                $nouvelleInscription->ID_FILIERE = $nextCode;
                $nouvelleInscription->codeStatut = $inscr->codeStatut ?? 'TP';
                $nouvelleInscription->Group_PDGQ = null;
                $nouvelleInscription->frais_paye = 0;  // Scolarité non encore configurée pour la nouvelle année
                $nouvelleInscription->reste_paye = 0;  // Aucun paiement, aucun montant défini
                $nouvelleInscription->S_Finance = 'NON';
                $nouvelleInscription->Observations = "Transfert promotion depuis " . $sourceYear->ANNEE_ACAD;
                $nouvelleInscription->Auteur = Auth::user()->login ?? 'Système';
                $nouvelleInscription->Moment = now();
                $nouvelleInscription->save();

                $transferredCount++;
            }

            DB::commit();

            $msg = "{$transferredCount} étudiant(s) transféré(s) avec succès vers l'année {$targetYear->ANNEE_ACAD} !";
            if ($ueResult && $ueResult['copied_ue'] > 0) {
                $msg .= " + {$ueResult['copied_ue']} UEs et {$ueResult['copied_ecue']} ECUEs attribuées automatiquement.";
            }
            if ($skippedCount > 0) {
                $msg .= " ({$skippedCount} ignoré(s) car déjà inscrits ou en fin de cycle).";
            }

            return redirect()->route('passage-promotion.index', [
                'source_year' => $sourceYear->id_Annee_Acad,
                'target_year' => $targetYear->id_Annee_Acad,
            ])->with('succes', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('echec', 'Erreur lors du transfert : ' . $e->getMessage());
        }
    }

    /**
     * Action dédiée : Reconduire / Attribuer les UEs et ECUEs de l'année source vers l'année cible.
     */
    public function reconduireUesAction(Request $request)
    {
        $request->validate([
            'source_year' => 'required|different:target_year',
            'target_year' => 'required',
        ]);

        $sourceYear = AnneeAcademique::findOrFail($request->source_year);
        $targetYear = AnneeAcademique::findOrFail($request->target_year);

        DB::beginTransaction();
        try {
            $result = self::reconduireUes(
                $sourceYear->id_Annee_Acad,
                $targetYear->id_Annee_Acad,
                Auth::user()->login ?? 'Système'
            );

            DB::commit();

            $msg = "Attribution terminée : {$result['copied_ue']} UEs et {$result['copied_ecue']} ECUEs ont été attribuées à l'année {$targetYear->ANNEE_ACAD}.";
            if ($result['skipped_ue'] > 0) {
                $msg .= " ({$result['skipped_ue']} UEs étaient déjà présentes).";
            }

            return redirect()->route('passage-promotion.index', [
                'source_year' => $sourceYear->id_Annee_Acad,
                'target_year' => $targetYear->id_Annee_Acad,
            ])->with('succes', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('echec', "Erreur lors de l'attribution des UEs : " . $e->getMessage());
        }
    }

    /**
     * Revenir en arrière sur les modifications / Annuler le transfert.
     * Supprime les inscriptions créées par le transfert dans l'année cible,
     * en protégeant les étudiants qui auraient déjà effectué un paiement.
     */
    public function annuler(Request $request)
    {
        $request->validate([
            'target_year' => 'required',
        ]);

        $targetYear = AnneeAcademique::findOrFail($request->target_year);
        $sourceYearId = $request->source_year;

        // Trouver les inscriptions candidates à l'annulation dans l'année cible
        $query = Inscription::where('Id_Annee', $targetYear->id_Annee_Acad)
            ->where(function ($q) {
                $q->where('Observations', 'like', '%Transfert promotion%')
                    ->orWhere('Observations', 'like', '%Transfert%');
            });

        $inscriptions = $query->get();

        if ($inscriptions->isEmpty()) {
            return back()->with('echec', "Aucune inscription de transfert trouvée pour l'année {$targetYear->ANNEE_ACAD}.");
        }

        $deletedCount = 0;
        $protectedCount = 0;
        $matriculesToDelete = [];

        DB::beginTransaction();
        try {
            foreach ($inscriptions as $inscr) {
                // Sécurité : vérifier si un paiement existe déjà pour cette inscription
                $hasPayment = Paiement::where('MATRICULE', $inscr->MATRICULE)
                    ->where('Id_Annee', $inscr->Id_Annee)
                    ->exists();

                if ($hasPayment) {
                    $protectedCount++;
                    continue; // Ne pas supprimer si déjà des paiements réels encaissés
                }

                $matriculesToDelete[] = $inscr->MATRICULE;
            }

            if (!empty($matriculesToDelete)) {
                $deletedCount = Inscription::where('Id_Annee', $targetYear->id_Annee_Acad)
                    ->whereIn('MATRICULE', $matriculesToDelete)
                    ->where(function ($q) {
                        $q->where('Observations', 'like', '%Transfert promotion%')
                            ->orWhere('Observations', 'like', '%Transfert%');
                    })
                    ->delete();
            }

            DB::commit();

            $msg = "Annulation réussie : {$deletedCount} inscription(s) de transfert ont été supprimées de l'année {$targetYear->ANNEE_ACAD}.";
            if ($protectedCount > 0) {
                $msg .= " Attention : {$protectedCount} inscription(s) n'ont pas été supprimées car des paiements ont déjà été enregistrés.";
            }

            return redirect()->route('passage-promotion.index', [
                'source_year' => $sourceYearId,
                'target_year' => $targetYear->id_Annee_Acad,
            ])->with('succes', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('echec', "Erreur lors de l'annulation : " . $e->getMessage());
        }
    }
}
