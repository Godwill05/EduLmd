<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Qualité des données.
 *
 * Détecte les problèmes qui coûtent du temps au quotidien :
 *   - doublons d'étudiants probables (même nom + prénoms proches)
 *   - champs obligatoires manquants (photo, téléphone, email, date de naissance)
 *   - inscriptions sans aucun paiement
 *
 * Toutes les détections sont faites en SQL et bornées, pour rester
 * exécutables sur une base de plusieurs dizaines de milliers de lignes.
 */
class DataQualityController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'doublons');

        // ── Statistiques de complétude (une seule passe) ────────────────
        $total = (int) Etudiant::count();

        /* Attention aux types : `dateNaisEtu` est une colonne DATE.
           La comparer à '' déclenche « Incorrect DATE value » en mode strict,
           d'où le test NULL seul pour cette colonne. Les autres sont des
           chaînes : NULL et '' doivent tous deux être comptés. */
        $completude = Etudiant::query()->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN photo IS NULL OR photo = '' THEN 1 ELSE 0 END) as sans_photo,
            SUM(CASE WHEN telEtu IS NULL OR telEtu = '' THEN 1 ELSE 0 END) as sans_tel,
            SUM(CASE WHEN emailEtu IS NULL OR emailEtu = '' THEN 1 ELSE 0 END) as sans_email,
            SUM(CASE WHEN dateNaisEtu IS NULL THEN 1 ELSE 0 END) as sans_date,
            SUM(CASE WHEN sexeEtu IS NULL OR sexeEtu = '' THEN 1 ELSE 0 END) as sans_sexe
        ")->first();

        $stats = [
            'total' => $total,
            'sans_photo' => (int) ($completude->sans_photo ?? 0),
            'sans_tel' => (int) ($completude->sans_tel ?? 0),
            'sans_email' => (int) ($completude->sans_email ?? 0),
            'sans_date' => (int) ($completude->sans_date ?? 0),
            'sans_sexe' => (int) ($completude->sans_sexe ?? 0),
        ];

        $stats['tauxComplet'] = $total > 0
            ? round((1 - ($stats['sans_photo'] + $stats['sans_tel'] + $stats['sans_email']) / ($total * 3)) * 100, 1)
            : 100;

        // ── Doublons probables ──────────────────────────────────────────
        // On regroupe sur (nom normalisé, prénoms normalisés) : deux étudiants
        // qui partagent exactement ces deux chaînes sont très probablement
        // une double saisie. On exclut les groupes d'une seule ligne.
        /* GROUP_CONCAT(MATRICULE ORDER BY … SEPARATOR …) n'existe que chez
           MySQL. SQLite n'accepte que group_concat(x, sep), et la clause
           ORDER BY y est interdite : la requête portable diffère donc selon
           le pilote. En production (MySQL) c'est la première forme qui part. */
        $concat = DB::connection()->getDriverName() === 'sqlite'
            ? "group_concat(MATRICULE, ', ') as matricules,
               group_concat(COALESCE(dateNaisEtu, '—'), ' | ') as dates"
            : "GROUP_CONCAT(MATRICULE ORDER BY MATRICULE SEPARATOR ', ') as matricules,
               GROUP_CONCAT(COALESCE(dateNaisEtu, '—') ORDER BY MATRICULE SEPARATOR ' | ') as dates";

        $doublons = Etudiant::query()
            ->selectRaw("
                UPPER(TRIM(nomEtu)) as nom_norm,
                UPPER(TRIM(prenomsEtu)) as prenoms_norm,
                COUNT(*) as occurrences,
                " . $concat . "
            ")
            ->whereNotNull('nomEtu')
            ->where('nomEtu', '!=', '')
            ->whereNotNull('prenomsEtu')
            ->where('prenomsEtu', '!=', '')
            ->groupByRaw('UPPER(TRIM(nomEtu)), UPPER(TRIM(prenomsEtu))')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('occurrences')
            ->limit(80)
            ->get();

        // Détail des étudiants appartenant aux groupes détectés
        $doublonsDetail = [];
        if ($doublons->isNotEmpty() && $tab === 'doublons') {
            $paires = $doublons
                ->map(fn($d) => [$d->nom_norm, $d->prenoms_norm])
                ->all();

            $matricules = [];
            foreach ($doublons as $d) {
                foreach (explode(', ', $d->matricules) as $m) {
                    $matricules[] = $m;
                }
            }

            $etudiantsEnDouble = Etudiant::query()
                ->whereIn('MATRICULE', array_slice($matricules, 0, 300))
                ->get()
                ->keyBy('MATRICULE');

            foreach ($doublons as $d) {
                $liste = [];
                foreach (explode(', ', $d->matricules) as $m) {
                    $e = $etudiantsEnDouble->get($m);
                    if (!$e) continue;
                    $liste[] = [
                        'matricule' => $e->MATRICULE,
                        'nom' => trim($e->nomEtu . ' ' . $e->prenomsEtu),
                        'dateNais' => $e->dateNaisEtu,
                        'sexe' => $e->sexeEtu,
                        'tel' => $e->telEtu,
                        'email' => $e->emailEtu,
                        'inscriptions' => (int) Inscription::where('MATRICULE', $e->MATRICULE)->count(),
                    ];
                }
                $doublonsDetail[] = [
                    'nom' => $d->nom_norm,
                    'prenoms' => $d->prenoms_norm,
                    'occurrences' => (int) $d->occurrences,
                    'etudiants' => $liste,
                ];
            }
        }

        // ── Champs manquants (échantillon paginé) ──────────────────────
        $champ = $request->input('champ', 'photo');
        $champsValides = ['photo', 'telEtu', 'emailEtu', 'dateNaisEtu'];
        if (!in_array($champ, $champsValides, true)) {
            $champ = 'photo';
        }

        /* Même précaution : sur une colonne DATE, `= ''` est rejeté par MySQL. */
        $colonneDate = $champ === 'dateNaisEtu';

        $incomplets = Etudiant::query()
            ->where(function ($q) use ($champ, $colonneDate) {
                $q->whereNull($champ);
                if (!$colonneDate) {
                    $q->orWhere($champ, '');
                }
            })
            ->orderBy('nomEtu')
            ->paginate(25)
            ->withQueryString();

        // ── Inscriptions sans aucun paiement ───────────────────────────
        $avecPaiement = DB::table('paiements')->select('MATRICULE')->distinct();

        $sansPaiement = Inscription::query()
            ->leftJoin('etudiant', 'inscriptions.MATRICULE', '=', 'etudiant.MATRICULE')
            ->leftJoin('anneeacademique', 'inscriptions.Id_Annee', '=', 'anneeacademique.id_Annee_Acad')
            ->leftJoin('annee_etude', 'inscriptions.ID_FILIERE', '=', 'annee_etude.Code_Annee_Etude')
            ->whereNotIn('inscriptions.MATRICULE', $avecPaiement)
            ->where('inscriptions.reste_paye', '>', 0)
            ->orderBy('etudiant.nomEtu')
            ->paginate(25, [
                'inscriptions.MATRICULE',
                'etudiant.nomEtu',
                'etudiant.prenomsEtu',
                'inscriptions.reste_paye',
                'anneeacademique.ANNEE_ACAD',
                'annee_etude.Libelle_AnEtude',
            ], 'page_sans_paiement')
            ->withQueryString();

        return view('qualite-donnees', compact(
            'tab', 'stats', 'doublons', 'doublonsDetail',
            'champ', 'incomplets', 'sansPaiement'
        ));
    }
}
