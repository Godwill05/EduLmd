<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Nationalite;
use App\Models\Statut;
use App\Models\TypeEtudiant;
use Illuminate\Http\Request;

/**
 * Import Excel/CSV en deux temps.
 *
 *   1. POST /imports/analyser  → le fichier est stocké, analysé ligne par
 *      ligne, et un rapport est renvoyé (valides / erreurs / avertissements).
 *      RIEN n'est encore écrit en base.
 *   2. POST /imports/confirmer → seules les lignes valides sont insérées.
 *
 * Cela évite l'écueil de l'import actuel : un fichier partiellement
 * incorrect s'écrit silencieusement, sans que personne ne sache quelles
 * lignes ont été perdues.
 */
class ImportController extends Controller
{
    /** Types d'import reconnus et leur libellé. */
    private const TYPES = [
        'inscriptions' => 'Inscriptions + étudiants',
        'etudiants' => 'Étudiants',
        'ues' => 'Unités d\'enseignement (UE)',
    ];

    public function index()
    {
        return view('imports', ['types' => self::TYPES]);
    }

    /**
     * Analyse le fichier sans rien écrire en base.
     */
    public function analyser(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
        ]);

        $type = $request->input('type');
        $fichier = $request->file('fichier');

        /* On stocke le fichier pour pouvoir le relire à la confirmation,
           sans dépendre du contenu du formulaire (taille, encodage). */
        $cle = 'imports/' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $fichier->getClientOriginalExtension();
        $fichier->storeAs('public', $cle);

        $chemin = storage_path('app/public/' . $cle);

        try {
            $lignes = $this->lireFeuille($chemin, $fichier->getClientOriginalExtension());
        } catch (\Throwable $e) {
            return back()->with('echec', "Fichier illisible : " . $e->getMessage());
        }

        if (count($lignes) < 2) {
            return back()->with('echec', "Le fichier ne contient aucune donnée (au moins un en-tête et une ligne attendus).");
        }

        $entetes = array_map(fn($h) => trim((string) $h), array_shift($lignes));

        $rapport = match ($type) {
            'inscriptions' => $this->analyserInscriptions($entetes, $lignes),
            'etudiants' => $this->analyserEtudiants($entetes, $lignes),
            'ues' => $this->analyserUes($entetes, $lignes),
        };

        /* Le rapport est conservé en session : la confirmation ne fait que
           le relire, ce qui évite tout écart entre l'aperçu et l'exécution. */
        session()->put('import.rapport', [
            'cle' => $cle,
            'type' => $type,
            'entetes' => $entetes,
            'valides' => $rapport['valides'],
            'erreurs' => $rapport['erreurs'],
            'avertissements' => $rapport['avertissements'],
            'total' => count($lignes),
        ]);

        return redirect()->route('imports.apercu');
    }

    /**
     * Affiche l'aperçu du rapport d'analyse.
     */
    public function apercu()
    {
        $rapport = session('import.rapport');
        if (!$rapport) {
            return redirect()->route('imports.index')
                ->with('echec', "Aucun fichier analysé. Choisissez un fichier à importer.");
        }

        return view('imports-apercu', [
            'rapport' => $rapport,
            'typeLibelle' => self::TYPES[$rapport['type']] ?? $rapport['type'],
        ]);
    }

    /**
     * Écrit en base les lignes validées lors de l'analyse.
     */
    public function confirmer(Request $request)
    {
        $rapport = session('import.rapport');
        if (!$rapport) {
            return redirect()->route('imports.index')
                ->with('echec', "Session d'import expirée. Recommencez l'analyse du fichier.");
        }

        if ($rapport['valides'] === []) {
            return redirect()->route('imports.index')
                ->with('echec', "Aucune ligne valide : rien n'a été importé.");
        }

        $nb = 0;
        $echecs = [];

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($rapport, &$nb, &$echecs) {
                foreach ($rapport['valides'] as $i => $ligne) {
                    try {
                        match ($rapport['type']) {
                            'inscriptions' => $this->ecrireInscription($ligne),
                            'etudiants' => $this->ecrireEtudiant($ligne),
                            'ues' => $this->ecrireUe($ligne),
                        };
                        $nb++;
                    } catch (\Throwable $e) {
                        $echecs[] = 'Ligne ' . ($ligne['_ligne'] ?? ($i + 2)) . ' : ' . $e->getMessage();
                    }
                }
            });
        } catch (\Throwable $e) {
            return redirect()->route('imports.index')
                ->with('echec', "L'import a échoué et a été annulé : " . $e->getMessage());
        }

        session()->forget('import.rapport');

        $message = $nb . ' ligne(s) importée(s) avec succès.';
        if ($echecs) {
            $message .= ' ' . count($echecs) . ' ligne(s) en échec.';
        }

        return redirect()->route('imports.index')
            ->with('succes', $message)
            ->with('import.echecs', $echecs);
    }

    /* ══════════════════ Lecture du fichier ══════════════════ */

    /**
     * Lit la première feuille d'un classeur Excel ou d'un CSV.
     *
     * PhpSpreadsheet est utilisé directement (il est installé comme
     * dépendance de maatwebsite/excel) : cela évite de dépendre de la
     * signature exacte de `Excel::toArray`, qui varie selon les versions.
     *
     * @return array<int, array<int, string>>
     */
    private function lireFeuille(string $chemin, string $extension): array
    {
        $extension = mb_strtolower($extension);

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->lireCsv($chemin);
        }

        $classeur = \PhpOffice\PhpSpreadsheet\IOFactory::load($chemin);
        $feuille = $classeur->getSheet(0);

        $lignes = [];
        foreach ($feuille->toArray(null, true, true, false) as $row) {
            $lignes[] = array_map(function ($v) {
                if ($v instanceof \DateTimeInterface) {
                    return $v->format('Y-m-d');
                }
                return (string) $v;
            }, $row);
        }

        $classeur->disconnectWorksheets();

        return $lignes;
    }

    /**
     * Lecture CSV avec détection d'encodage : les fichiers exportés depuis
     * Excel francophone sont souvent en Windows-1252, ce qui casse les
     * accents si on lit en UTF-8.
     */
    private function lireCsv(string $chemin): array
    {
        $contenu = file_get_contents($chemin);

        if (!mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
        }

        /* Retire le BOM éventuel. */
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);

        $lignes = [];
        $flux = fopen('php://temp', 'r+');
        fwrite($flux, $contenu);
        rewind($flux);

        /* Détection du séparateur : point-virgule (Excel FR) ou virgule. */
        $premiere = fgets($flux);
        rewind($flux);
        $sep = substr_count((string) $premiere, ';') >= substr_count((string) $premiere, ',') ? ';' : ',';

        while (($row = fgetcsv($flux, 0, $sep)) !== false) {
            $lignes[] = array_map(fn($v) => (string) $v, $row);
        }

        fclose($flux);

        return $lignes;
    }

    /* ══════════════════ Analyse ══════════════════ */

    /**
     * Colonnes attendues pour les inscriptions (mêmes positions que
     * l'import historique, pour ne pas casser les fichiers existants) :
     *  0 MATRICULE · 1 NOM · 2 PRENOMS · 3 DATE NAISS · 4 LIEU NAISS
     *  5 SEXE · 6 TEL · 7 EMAIL · 8 OBSERVATION · 9 TYPE · 10 NATIONALITE
     *  11 ANNEE ACAD · 12 ANNEE ETUDE · 13 STATUT · 14 GROUP · 15 MONTANT
     *  16 S_FINANCE
     */
    private function analyserInscriptions(array $entetes, array $lignes): array
    {
        $valides = [];
        $erreurs = [];
        $avertissements = [];

        /* Référentiels préchargés : une seule requête par table. */
        $annees = AnneeAcademique::all()->keyBy(fn($a) => mb_strtolower(trim((string) $a->ANNEE_ACAD)));
        $statuts = Statut::all()->keyBy(fn($s) => mb_strtolower(trim((string) $s->LibStatut)));
        $statutsCodes = Statut::all()->keyBy(fn($s) => mb_strtolower(trim((string) $s->codeStatut)));
        $types = TypeEtudiant::all()->keyBy(fn($t) => mb_strtolower(trim((string) $t->libTypeEtu)));
        $typesCodes = TypeEtudiant::all()->keyBy(fn($t) => mb_strtolower(trim((string) $t->codeTypeEtu)));
        $nats = Nationalite::all()->keyBy(fn($n) => mb_strtolower(trim((string) $n->libelleNationalite)));
        $natsCodes = Nationalite::all()->keyBy(fn($n) => mb_strtolower(trim((string) $n->codeNationalite)));

        foreach ($lignes as $idx => $row) {
            $num = $idx + 2; // ligne réelle dans le fichier (1 = en-tête)
            $row = array_map(fn($v) => trim((string) $v), (array) $row);

            /* Ligne entièrement vide : on l'ignore sans la signaler. */
            if (implode('', $row) === '') continue;

            $erreursLigne = [];
            $avertLigne = [];

            $matricule = $row[0] ?? '';
            if ($matricule === '') {
                $erreursLigne[] = 'Matricule manquant.';
            }

            $nom = $row[1] ?? '';
            if ($nom === '') $erreursLigne[] = 'Nom manquant.';

            $dateBrute = $row[3] ?? '';
            $dateNaiss = null;
            if ($dateBrute !== '') {
                try {
                    $dateNaiss = \Carbon\Carbon::parse($dateBrute)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $erreursLigne[] = "Date de naissance illisible (« {$dateBrute} »).";
                }
            } else {
                $avertLigne[] = 'Date de naissance absente.';
            }

            $anneeAcad = mb_strtolower(trim((string) ($row[11] ?? '')));
            $annee = $annees->get($anneeAcad);
            if (!($row[11] ?? '')) {
                $erreursLigne[] = 'Année académique manquante.';
            } elseif (!$annee) {
                $erreursLigne[] = "Année académique inconnue (« {$row[11]} »).";
            }

            $anneeEtude = (string) ($row[12] ?? '');
            if ($anneeEtude === '') $erreursLigne[] = "Année d'étude manquante.";

            $statutBrut = mb_strtolower(trim((string) ($row[13] ?? '')));
            $statut = $statuts->get($statutBrut) ?? $statutsCodes->get($statutBrut);
            if (!($row[13] ?? '')) {
                $erreursLigne[] = 'Statut manquant.';
            } elseif (!$statut) {
                $erreursLigne[] = "Statut inconnu (« {$row[13]} »).";
            }

            $typeBrut = mb_strtolower(trim((string) ($row[9] ?? '')));
            $type = $types->get($typeBrut) ?? $typesCodes->get($typeBrut);
            if (!($row[9] ?? '')) {
                $avertLigne[] = "Type d'étudiant absent.";
            } elseif (!$type) {
                $erreursLigne[] = "Type d'étudiant inconnu (« {$row[9]} »).";
            }

            $natBrute = mb_strtolower(trim((string) ($row[10] ?? '')));
            $nat = $nats->get($natBrute) ?? $natsCodes->get($natBrute);
            if (!($row[10] ?? '')) {
                $avertLigne[] = 'Nationalité absente.';
            } elseif (!$nat) {
                $erreursLigne[] = "Nationalité inconnue (« {$row[10]} »).";
            }

            $montant = (int) preg_replace('/[^0-9]/', '', (string) ($row[15] ?? ''));
            if ($montant <= 0) $avertLigne[] = 'Montant à payer nul ou absent.';

            /* Doublon dans la base : on le signale sans bloquer. */
            if ($matricule !== '' && $annee) {
                $deja = Inscription::where('MATRICULE', $matricule)
                    ->where('Id_Annee', $annee->id_Annee_Acad)
                    ->where('ID_FILIERE', $anneeEtude)
                    ->exists();
                if ($deja) {
                    $erreursLigne[] = 'Inscription déjà existante : la ligne sera ignorée.';
                }
            }

            if ($erreursLigne) {
                $erreurs[] = [
                    'ligne' => $num,
                    'matricule' => $matricule,
                    'nom' => $nom,
                    'motifs' => $erreursLigne,
                ];
                continue;
            }

            if ($avertLigne) {
                $avertissements[] = [
                    'ligne' => $num,
                    'matricule' => $matricule,
                    'nom' => $nom,
                    'motifs' => $avertLigne,
                ];
            }

            $valides[] = [
                '_ligne' => $num,
                'matricule' => $matricule,
                'nom' => $nom,
                'prenoms' => $row[2] ?? '',
                'dateNais' => $dateNaiss,
                'lieuNais' => $row[4] ?? '',
                'sexe' => $row[5] ?? '',
                'tel' => $row[6] ?? '',
                'email' => $row[7] ?? '',
                'observation' => $row[8] ?? '',
                'codeType' => $type?->codeTypeEtu,
                'codeNat' => $nat?->codeNationalite,
                'idAnnee' => $annee?->id_Annee_Acad,
                'anneeEtude' => $anneeEtude,
                'codeStatut' => $statut?->codeStatut,
                'group' => $row[14] ?? '',
                'montant' => $montant,
                'sFinance' => $row[16] ?? 'NON',
            ];
        }

        return compact('valides', 'erreurs', 'avertissements');
    }

    private function analyserEtudiants(array $entetes, array $lignes): array
    {
        $valides = [];
        $erreurs = [];
        $avertissements = [];

        foreach ($lignes as $idx => $row) {
            $num = $idx + 2;
            $row = array_map(fn($v) => trim((string) $v), (array) $row);
            if (implode('', $row) === '') continue;

            $erreursLigne = [];
            $avertLigne = [];

            $matricule = $row[0] ?? '';
            if ($matricule === '') {
                $erreursLigne[] = 'Matricule manquant.';
            } elseif (Etudiant::find($matricule)) {
                $erreursLigne[] = 'Étudiant déjà existant : la ligne sera ignorée.';
            }

            if (!($row[1] ?? '')) $erreursLigne[] = 'Nom manquant.';
            if (!($row[3] ?? '')) $avertLigne[] = 'Date de naissance absente.';
            if (!($row[6] ?? '')) $avertLigne[] = 'Téléphone absent.';
            if (!($row[7] ?? '')) $avertLigne[] = 'Email absent.';

            $sexe = strtoupper(trim((string) ($row[5] ?? '')));
            if ($sexe !== '' && !in_array($sexe, ['M', 'F'], true)) {
                $erreursLigne[] = "Sexe invalide (« {$sexe} ») : attendu M ou F.";
            }

            if ($erreursLigne) {
                $erreurs[] = ['ligne' => $num, 'matricule' => $matricule, 'nom' => $row[1] ?? '', 'motifs' => $erreursLigne];
                continue;
            }

            if ($avertLigne) {
                $avertissements[] = ['ligne' => $num, 'matricule' => $matricule, 'nom' => $row[1] ?? '', 'motifs' => $avertLigne];
            }

            $valides[] = [
                '_ligne' => $num,
                'matricule' => $matricule,
                'nom' => $row[1] ?? '',
                'prenoms' => $row[2] ?? '',
                'dateNais' => $row[3] ?? null,
                'lieuNais' => $row[4] ?? '',
                'sexe' => $sexe,
                'tel' => $row[6] ?? '',
                'email' => $row[7] ?? '',
                'observation' => $row[8] ?? '',
            ];
        }

        return compact('valides', 'erreurs', 'avertissements');
    }

    private function analyserUes(array $entetes, array $lignes): array
    {
        $valides = [];
        $erreurs = [];
        $avertissements = [];
        $vus = [];

        foreach ($lignes as $idx => $row) {
            $num = $idx + 2;
            $row = array_map(fn($v) => trim((string) $v), (array) $row);
            if (implode('', $row) === '') continue;

            $code = $row[0] ?? '';
            $libelle = $row[1] ?? '';
            $erreursLigne = [];

            if ($code === '') {
                $erreursLigne[] = 'Code UE manquant.';
            } elseif (isset($vus[mb_strtoupper($code)])) {
                $erreursLigne[] = 'Code UE présent deux fois dans le fichier.';
            } elseif (\App\Models\Ue::find($code)) {
                $erreursLigne[] = 'Cette UE existe déjà : la ligne sera ignorée.';
            }

            if ($libelle === '') $erreursLigne[] = 'Libellé manquant.';

            if ($erreursLigne) {
                $erreurs[] = ['ligne' => $num, 'matricule' => $code, 'nom' => $libelle, 'motifs' => $erreursLigne];
                continue;
            }

            $vus[mb_strtoupper($code)] = true;
            $valides[] = ['_ligne' => $num, 'code' => $code, 'libelle' => $libelle];
        }

        return compact('valides', 'erreurs', 'avertissements');
    }

    /* ══════════════════ Écriture ══════════════════ */

    private function ecrireInscription(array $l): void
    {
        if (!Etudiant::find($l['matricule'])) {
            Etudiant::create([
                'MATRICULE' => $l['matricule'],
                'nomEtu' => $l['nom'],
                'prenomsEtu' => $l['prenoms'],
                'dateNaisEtu' => $l['dateNais'],
                'lieuNaisEtu' => $l['lieuNais'],
                'sexeEtu' => $l['sexe'],
                'telEtu' => $l['tel'],
                'emailEtu' => $l['email'],
                'Observation' => $l['observation'],
                'codeTypeEtu' => $l['codeType'],
                'codeNationalite' => $l['codeNat'],
                'Auteur' => auth()->user()->login ?? (auth()->user()->name ?? 'système'),
            ]);
        }

        Inscription::create([
            'MATRICULE' => $l['matricule'],
            'Id_Annee' => $l['idAnnee'],
            'ID_FILIERE' => $l['anneeEtude'],
            'codeStatut' => $l['codeStatut'],
            'Group_PDGQ' => $l['group'],
            'frais_paye' => $l['montant'],
            'reste_paye' => $l['montant'],
            'S_Finance' => $l['sFinance'] ?: 'NON',
            'Observations' => $l['observation'],
            'Auteur' => auth()->user()->login ?? (auth()->user()->name ?? 'système'),
        ]);
    }

    private function ecrireEtudiant(array $l): void
    {
        $date = null;
        if ($l['dateNais']) {
            try { $date = \Carbon\Carbon::parse($l['dateNais'])->format('Y-m-d'); } catch (\Throwable $e) { $date = null; }
        }

        Etudiant::create([
            'MATRICULE' => $l['matricule'],
            'nomEtu' => $l['nom'],
            'prenomsEtu' => $l['prenoms'],
            'dateNaisEtu' => $date,
            'lieuNaisEtu' => $l['lieuNais'],
            'sexeEtu' => $l['sexe'],
            'telEtu' => $l['tel'],
            'emailEtu' => $l['email'],
            'Observation' => $l['observation'],
            'Auteur' => auth()->user()->login ?? (auth()->user()->name ?? 'système'),
        ]);
    }

    private function ecrireUe(array $l): void
    {
        \App\Models\Ue::create([
            'codeUe' => $l['code'],
            'libelleUe' => $l['libelle'],
            'Auteur' => auth()->user()->login ?? (auth()->user()->name ?? 'système'),
        ]);
    }
}
