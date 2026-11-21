<?php

use App\Exports\NotesExport;
use App\Http\Controllers\AnneeAcademiqueController;
use App\Http\Controllers\AnneeEtudeController;
use App\Http\Controllers\AttribuerEcueUeController;
use App\Http\Controllers\AttribuerUeSemestreController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ConstanteController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BulkActionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\DataQualityController;
use App\Http\Controllers\QuickPaymentController;
use App\Http\Controllers\CoursController;
use App\Http\Controllers\DomaineResaoController;
use App\Http\Controllers\ECUEController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\EtablissementController;
use App\Http\Controllers\EtudiantController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\FiliereCompletesController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ImpressionController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\MentionController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PassagePromotionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PrevisionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SemestreController;
use App\Http\Controllers\SemestresAnneeEtudeController;
use App\Http\Controllers\SemestresEtudesController;
use App\Http\Controllers\SpecialiteController;
use App\Http\Controllers\UEController;
use App\Http\Controllers\UniversiteController;
use App\Http\Controllers\UserController;
use App\Imports\AttrEcueUeImport;
use App\Imports\AttrUeSemestreImport;
use App\Imports\ECUEImport;
use App\Imports\InscriptionImport;
use App\Imports\NotesImport;
use App\Imports\UEImport;
use App\Models\AnneeAcademique;
use App\Models\AnneeEtude;
use App\Models\AttrEcueUe;
use App\Models\AttrUeClasse;
use App\Models\Etudiant;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\Prevision;
use App\Models\RecapAnnuel;
use App\Models\RecapUe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/home', [DashboardController::class, 'index'])->middleware('auth')->name('home');

// Import Excel/CSV : analyse, aperçu, puis confirmation
Route::get('/imports', [ImportController::class, 'index'])
    ->middleware('auth')->name('imports.index');
Route::post('/imports/analyser', [ImportController::class, 'analyser'])
    ->middleware('auth')->name('imports.analyser');
Route::get('/imports/apercu', [ImportController::class, 'apercu'])
    ->middleware('auth')->name('imports.apercu');
Route::post('/imports/confirmer', [ImportController::class, 'confirmer'])
    ->middleware('auth')->name('imports.confirmer');

// Actions groupées (changer statut / exporter / imprimer)
Route::post('/api/actions-groupees/statut', [BulkActionController::class, 'changerStatut'])
    ->middleware('auth')->name('actions-groupees.statut');
Route::post('/api/actions-groupees/export', [BulkActionController::class, 'exporter'])
    ->middleware('auth')->name('actions-groupees.export');
Route::post('/api/actions-groupees/impression', [BulkActionController::class, 'impression'])
    ->middleware('auth')->name('actions-groupees.impression');

// Paiement en un clic — situation et encaissement (JSON)
Route::get('/api/paiement-rapide/{matricule}', [QuickPaymentController::class, 'situation'])
    ->middleware('auth')->name('paiement-rapide.situation');
Route::post('/api/paiement-rapide', [QuickPaymentController::class, 'store'])
    ->middleware('auth')->name('paiement-rapide.store');

// Qualité des données : doublons, champs manquants, inscriptions sans paiement
Route::get('/qualite-donnees', [DataQualityController::class, 'index'])
    ->middleware('auth')
    ->name('qualite-donnees.index');

// Journal d'activité lisible (table `audits`)
Route::get('/journal-activite', [ActivityLogController::class, 'index'])
    ->middleware(['auth', 'can:Gérer les utilisateurs'])
    ->name('journal-activite.index');

// Recherche globale (palette Ctrl+K) — JSON
Route::get('/api/recherche', [SearchController::class, 'etudiants'])
    ->middleware('auth')
    ->name('recherche.etudiants');
Route::view('/not-found', 'errors.not-found')->name('not-found');
Route::view('/','login')->name('login')->middleware('guest');
Route::post('/',function(Request $rq){
    $credentials=$rq->validate([
        'email'=>'required',
        'password'=>'required'
    ]);

    if(Auth::attempt($credentials,$rq->remember)):
        $rq->session()->regenerate();
        return redirect()->intended(route('home'))->with('greetings','Content de vous revoir !');
    endif;
    return back()->with('errorCredentials' , 'Email ou mot de passe invalide')->onlyInput('email');
})->name('login.post');
Route::middleware('auth')->group(function(){
    Route::view('/about-us', 'about')->name('about-us');
    Route::name('affichage-recap-')->group(function(){
        Route::post('/affichage-recap-ues', '\App\Http\Controllers\UEController@recapitulatif')->name('ues');
        Route::post('/affichage-recap-semestre', '\App\Http\Controllers\SemestresEtudesController@recapitulatif')->name('semestre');
        Route::post('/affichage-recap-annuel', '\App\Http\Controllers\AnneeEtudeController@recapitulatif')->name('annuel');
        Route::post('/affichage-recap-grade', '\App\Http\Controllers\GradeController@recapitulatif')->name('grade');
        Route::post('/affichage-recap-grade-solo', '\App\Http\Controllers\GradeController@recapSolo')->name('gradeSolo');
    });
    Route::view('/profile','profile')->name('profile');
    Route::name('impression.')->controller(ImpressionController::class)->group(function(){
        Route::post('/impression-ue', 'impressionUe')->name('ue');
        Route::get('/impression-ues-nv', 'impressionUesNonValidees')->name('ues-nv');
        Route::post('/impression-ecues-semestre', 'impressionEcuesSemestre')->name('ecues-semestre');
        Route::match(['get', 'post'], '/impression-semestre', 'impressionSemestre')->name('semestre');
        Route::post('/impression-semestre-selection', 'impressionSemestre')->name('semestre-selection');
        Route::post('/impression-grade', 'impressionGrade')->name('grade');
        Route::post('/impression-rang', 'impressionAttestationRang')->name('rang');
        Route::post('/impression-attestation', 'impressionAttestation')->name('attestation');
        Route::post('/impression-diplome', 'impressionDiplome')->name('diplome');
        Route::match(['get', 'post'], '/impression-annuel', 'impressionAnnuel')->name('annuel');
        Route::post('/impression-releveNotes', 'releveNotes')->name('releveNotes');
        Route::post('/impression-paiement', 'impressionPaiement')->name('paiement');
        Route::post('/impression-situation-echeance', 'impressionSituationEcheance')->name('situation-echeance');
        Route::post('/impression-journal-encaissement', 'impressionJournalEncaissement')->name('journal-encaissement');
        Route::post('/impression-previsions', 'impressionPrevision')->name('prevision');
        Route::post('/impression-evaluation', 'impressionEvaluation')->name('evaluation');
        Route::post('/impression-cours', 'impressionCours')->name('cours');
        Route::post('/impression-repartir', 'impressionRepartir')->name('repartir');
        Route::post('/impression-emargement', 'impressionEmargement')->name('listeEmargement');
    });
    Route::get('/carte-etudiant/{mt}/{anAc}/{anEtd}',function($mt,$anAc,$anEtd){
        return view('carte-etudiant',['inscription'=>Inscription::where('MATRICULE',$mt)->where('Id_Annee',$anAc)->where('ID_FILIERE',$anEtd)->first()]);
    })->name('carte-etudiant');
    Route::view('/recapitulatif-ues','recapitulatifUe')->name('recapitulatifUes');
    Route::view('/recapitulatif-semestre','recapitulatifSemestre')->name('recapitulatifSemestre');
    Route::view('/recapitulatif-annuel','recapitulatifAnnuel')->name('recapitulatifAnnuel');
    Route::view('/recapitulatif-grade','recapitulatifGrade')->name('recapitulatifGrade');
    Route::get('/attestations-diplomes', [\App\Http\Controllers\AttestationDiplomeController::class, 'index'])->name('attestations-diplomes.index');
    Route::post('/attestations-diplomes/imprimer-attestation', [\App\Http\Controllers\AttestationDiplomeController::class, 'imprimerAttestation'])->name('attestations-diplomes.imprimer-attestation');
    Route::post('/attestations-diplomes/imprimer-certificat', [\App\Http\Controllers\AttestationDiplomeController::class, 'imprimerCertificat'])->name('attestations-diplomes.imprimer-certificat');
    Route::post('/attestations-diplomes/imprimer-pack', [\App\Http\Controllers\AttestationDiplomeController::class, 'imprimerPack'])->name('attestations-diplomes.imprimer-pack');
    Route::get('/liste-etudiants-filiere', [\App\Http\Controllers\ListeEtudiantsController::class, 'index'])->name('liste-etudiants.index');
    Route::post('/impression-liste-etudiants', [\App\Http\Controllers\ListeEtudiantsController::class, 'imprimerPdf'])->name('liste-etudiants.pdf');
    Route::view('/releve-notes','releveNotes')->name('releveNotes');
    Route::patch('/edit-profile',[ProfileController::class,'editProfile'])->name('edit-profile');
    Route::patch('/change-password',[ProfileController::class,'changePassword'])->name('change-password');
    Route::resource('/domainesresao',DomaineResaoController::class)->except(['create','edit','update','destroy','store']);
    Route::resource('/domainesresao',DomaineResaoController::class)->only('store')->middleware('can:Créer un domaine');
    Route::resource('/domainesresao',DomaineResaoController::class)->only('update')->middleware('can:Modifier un domaine');
    Route::resource('/domainesresao',DomaineResaoController::class)->only('destroy')->middleware('can:Supprimer un domaine');
    Route::resource('/mentions',MentionController::class)->except(['create','edit']);
    Route::resource('/specialites',SpecialiteController::class)->except(['create','edit']);
    Route::resource('/grades',GradeController::class)->except(['create','edit']);
    Route::resource('/semestres',SemestreController::class)->except(['create','edit']);
    Route::resource('/constantes',ConstanteController::class)->except(['create','edit']);
    Route::resource('/annees-etudes',AnneeEtudeController::class)->except(['create','edit']);
    Route::resource('/filieres',FiliereCompletesController::class)->except(['create','edit']);
    Route::resource('/utilisateurs',UserController::class)->except(['create','edit'])->middleware('can:Gérer les utilisateurs');
    Route::get('/utilisateurs/{utilisateur}/permissions',fn($utilisateur) => view('user-permissions',['user'=>User::find($utilisateur),'permissions'=>Permission::orderBy('type')->orderBy('name')->get()]))->name('utilisateurs.permissions')->middleware('can:Gérer les utilisateurs');
    Route::post('/utilisateurs-permissions',function () {
        $user=User::find(request('user'));
        $permissions=[];
        if(!request('permissions') || request('retirer')){
            $user->syncPermissions();
            if(request('retirer')){return to_route('utilisateurs.index')->with('succes','Permissions retirées !');}
            if(!request('permissions')){return to_route('utilisateurs.index')->with('warning','Aucune permission accordée !');}

        }
        foreach (request('permissions') as $key => $value) {
            array_push($permissions,$key);
        }
        $user->syncPermissions($permissions);
        return to_route('utilisateurs.index')->with('succes','Permissions accordées !');

    })->name('utilisateurs.permissions_post')->middleware('can:Gérer les utilisateurs');
    Route::resource('/permissions',PermissionController::class)->except(['create','edit'])->middleware('can:Gérer les utilisateurs');
    Route::resource('/semestres-etudes',SemestresEtudesController::class)->except(['create','edit']);
    Route::resource('/attribuer-semestres-anneeEtude',SemestresAnneeEtudeController::class)->except(['create','edit','update','show']);
    Route::resource('/attribuer-ue-semestre',AttribuerUeSemestreController::class)->except(['create','edit','show']);
    Route::get('/modifier-attribution-ue/{ue}',[AttribuerUeSemestreController::class,'edit'])->name('attribuer-ue-semestre.edit');
    Route::resource('/attribuer-ecue-ue',AttribuerEcueUeController::class)->except(['create','edit','show','destroy','update']);
    Route::delete('/attribuer-ecue-ue/destroy',[AttribuerEcueUeController::class,'destroy'])->name('attribuer-ecue-ue.destroy');
    Route::post('/modifier-attribution-ecue',[AttribuerEcueUeController::class,'edit'])->name('attribuer-ecue-ue.edit');
    Route::post('/attribuer-ecue-ue/update',[AttribuerEcueUeController::class,'update'])->name('attribuer-ecue-ue.update');
    Route::match(['get', 'post'], '/afficher-ues-semestre', function () {
        if(request()->isMethod('post') && request()->ajax()){
            $items=AttrUeClasse::where('id_Annee_Acad',request()->anneeacademique)
            ->where('id_S_Gr_Spe',request()->semestre)
            ->get();
            return view('displayUS',['items'=>$items]);
        }
        return view('afficherUeSemestre');
    })->name('afficherUeSemestre');
    Route::match(['get', 'post'], '/afficher-ecues-semestre', function () {
        if(request()->isMethod('post') && request()->ajax()){
            $items = AttrEcueUe::whereHas('ueSemestre', function (Builder $query) {
                $query->where('id_Annee_Acad',request()->anneeacademique)
                ->where('id_S_Gr_Spe',request()->semestre);
            })->get();
            return view('displayES',['items'=>$items]);
        }
        return view('afficherECUESemestre');
    })->name('afficherECUEsSemestre');
    Route::resource('/annees-academiques',AnneeAcademiqueController::class);
    Route::get('/passage-promotion', [PassagePromotionController::class, 'index'])->name('passage-promotion.index');
    Route::post('/passage-promotion/transferer', [PassagePromotionController::class, 'transferer'])->name('passage-promotion.transferer');
    Route::post('/passage-promotion/reconduire-ues', [PassagePromotionController::class, 'reconduireUesAction'])->name('passage-promotion.reconduire-ues');
    Route::post('/passage-promotion/annuler', [PassagePromotionController::class, 'annuler'])->name('passage-promotion.annuler');
    Route::resource('/salles',SalleController::class);
    Route::resource('/ue',UEController::class);
    Route::resource('/universites',UniversiteController::class)->middleware('can:Gérer les universités');
    Route::post('/manage-logo_univ/{id}',[UniversiteController::class,'manageLogo'])->name('manageLogo_univ')->middleware('can:Gérer les universités');
    Route::post('/manage-logo_ufr/{id}',[EtablissementController::class,'manageLogo'])->name('manageLogo_ufr')->middleware('can:Gérer les établissements');
    Route::post('/manage-photo-etudiant/{id}',[EtudiantController::class,'managePhoto'])->name('managePhotoEtudiant');
    Route::resource('/etablissements',EtablissementController::class)->middleware('can:Gérer les établissements');
    // L'ancien lien de menu pointe désormais vers le journal d'activité lisible
    Route::get('/audits', fn() => redirect()->route('journal-activite.index'))->name('audits')->middleware('can:Gérer les utilisateurs');
    Route::resource('/ecue',ECUEController::class);
    Route::resource('/evaluations',EvaluationController::class);
    Route::resource('/cours',CoursController::class);
    Route::post('/evaluations/insererNotes',[EvaluationController::class,'insererNotes'])->name('evaluations.insererNotes');
    Route::post('/notes/insererNotes',[NoteController::class,'insererNotes'])->name('notes.insererNotes');
    Route::match(['get','post'],'/notes',[NoteController::class,'index'])->name('afficherNotes');
    Route::match(['get','post'],'/parcours',function () {
        if(request()->isMethod('post')){
            $etud=Etudiant::find(request()->etudiant);
            return view('parcours',['etud'=>$etud]);
        }
        return view('parcours');
    })->name('parcours');
    Route::match(['get','post'],'/afficher-ues-nonValidees',function(){
        if(request()->isMethod('post')){
            $items=RecapUe::whereHas('ueSem',function(Builder $q){
                $q->where('id_Annee_Acad',request()->anneeacademique);
            })->where('Code_Annee_Etude',request()->anneeEtude)
            ->where('Etat_UE','NV')->get();
            $annee_etude=AnneeEtude::find(request()->anneeEtude)->Libelle_AnEtude;
            $etudiants=RecapAnnuel::where('Code_Annee_Etude',request()->anneeEtude)
                                    ->where('id_Annee_Acad',request()->anneeacademique)
                                    ->get();
            $anneeAcademique=AnneeAcademique::find(request()->anneeacademique)->ANNEE_ACAD;
            return view('afficherUesNonValidees',compact('items','anneeAcademique','annee_etude','etudiants'));
        }
        return view('afficherUesNonValidees');
    })->name('afficherUesNonValidees');
    Route::view('/repartition-salle','repartitionSalle')->name('repartition-salle');
    Route::post('/repartition-salle-post',function(){
        extract(request()->except('_token'));
        $request=request();
        $lgn=Auth::user()->login;
        for ($i = 1; $i <= $pld_Sal; $i++) {
            $matr = trim($_POST['Matricule' . $i]);
            DB::insert("INSERT INTO `repartir` (`Matricule`, `annee_etude`,`grpe_P`, `annee_acad`, `codeSalle`, `datedebut`, `datefin`, `login`, `created_at`, `version`) VALUES ('" . $matr . "', '" . $request->anetud . "', '" . $request->grpee . "', '" . $request->anacad . "', '" . $request->salleCompo . "', '" . $request->debut . "', '" . $request->fin . "','" . $lgn . "',CURRENT_TIMESTAMP,'1');");
        }
        return to_route('repartition-salle',[
            'Debut'=>$debut,
            'Fin'=>$fin,
            'id_aa'=>AnneeAcademique::where('ANNEE_ACAD',$anacad)->first()->id_Annee_Acad,
            'id_ae'=>$anetud,
            'id_grpe'=>$grpee,
            '&id_ane'=>$anacad
        ])->withFragment('#openModal');
    })->name('repartition-salle-post');
    Route::post('/repartitionSalle',function(){
        extract(request()->except('_token'));
        $d1 = \Carbon\Carbon::parse($dateDebut)->format('Y-m-d');
        $d2 = \Carbon\Carbon::parse($dateFin)->format('Y-m-d');
        $rc = DB::select("SELECT DISTINCT(annee_etude.Code_Annee_Etude) as anneeetude,evaluation.codeGroupe as grpe,attr_ue_classe.`id_Annee_Acad` as an,ANNEE_ACAD, COUNT(annee_etude.Code_Annee_Etude) as nbre FROM annee_etude_semestre,annee_etude,`attr_ue_classe`,anneeacademique,classe,ue,attr_ecue_ue,ecue,professeur,evaluation,typeevaluation WHERE attr_ue_classe.`id_Annee_Acad`=anneeacademique.`id_Annee_Acad` AND attr_ue_classe.`id_S_Gr_Spe`=classe.`id_S_Gr_Spe`AND attr_ue_classe.`codeUe`= ue.`codeUe`AND attr_ecue_ue.Id_Attr_ue_Sem=attr_ue_classe.Id_Attr_ue_Sem and attr_ecue_ue.codeEcu=ecue.codeEcu and attr_ecue_ue.numeroProf=professeur.numeroProf and annee_etude.Code_Annee_Etude=annee_etude_semestre.Code_Annee_Etude AND classe.id_S_Gr_Spe=annee_etude_semestre.id_S_Gr_Spe AND evaluation.Id_Attr_ue_Sem=attr_ecue_ue.Id_Attr_ue_Sem AND evaluation.codeEcu=attr_ecue_ue.codeEcu AND evaluation.codTypEval=typeevaluation.codTypEval AND `dateEval` BETWEEN '$d1' AND '$d2' GROUP BY attr_ue_classe.`id_Annee_Acad`, ANNEE_ACAD,annee_etude.Code_Annee_Etude,grpe ORDER BY annee_etude.Code_Annee_Etude ASC");
        $rc=array_map(function ($value) {
            return (array)$value;
        }, $rc);
        ?>
        <table id="liste" class="display table table-striped" style="font-family: 'Times New Roman';">
            <thead class="thead">
                <tr>
                    <th hidden="hidden">CODE ANNEE</th>
                    <th>ANNEE ACADEMIQUE</th>
                    <th>ANNEE ETUDE</th>
                    <th>GROUPE PEDAGOGIQUE</th>
                    <th>NOMBRE DE COMPOSITION</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < count($rc); $i++) { ?>

                    <tr>
                        <td hidden="hidden"><?php echo $rc[$i]["an"] ?></td>
                        <td style="text-align: center"><?php echo $rc[$i]["ANNEE_ACAD"] ?></td>
                        <td><?php echo $rc[$i]["anneeetude"] ?></td>
                        <td><?php echo $rc[$i]["grpe"] ?></td>
                        <td style="text-align: center"><?php echo $rc[$i]["nbre"] ?></td>
                        <td align="center"><a href="?Debut=<?php echo $d1 ?>&Fin=<?php echo $d2 ?>&id_aa=<?php echo $rc[$i]["an"] ?>&id_ae=<?php echo $rc[$i]["anneeetude"] ?>&id_grpe=<?php echo $rc[$i]["grpe"] ?>&id_ane=<?php echo $rc[$i]["ANNEE_ACAD"] ?>#openModal"><i class="bi bi-pencil" title="Répartir les étudiants"></i> </a> </td>
                    </tr>

                <?php } ?>
            </tbody>
        </table>
    <?php
    });
    Route::middleware('can:Supprimer les notes')->get('/deleteNote/{note}',function($note){
        Note::destroy($note);
    })->name('delete-notes');
    Route::resource('/etudiants',EtudiantController::class)->except('destroy');
    Route::resource('/etudiants',EtudiantController::class)->only('destroy')->middleware('can:Supprimer les étudiants');
    Route::resource('/enseignants',EnseignantController::class)->except('destroy');
    Route::resource('/enseignants',EnseignantController::class)->only('destroy')->middleware('can:Supprimer les enseignants');
    Route::view('/liste-emargement', 'listeEmargement')->name('listeEmargement');
    Route::get('/dEtu', function(){
        $rech=request()->recherche;
        return view('rechercheEtudiants',['items'=>Etudiant::orderBy('nomEtu')->where('nomEtu','like',"%$rech%")->orWhere('prenomsEtu','like',"%$rech%")->orWhere('MATRICULE','like',"%$rech%")->get()]);
    })->name('afficherEtudiants');
    Route::get('/dEva', function(){
        $rech=request()->recherche;
        return view('rechercheEvaluations',['items1'=>Evaluation::orderBy('dateEval')->where('heureDebutEval','like',"%$rech%")->orWhereRelation('ueSem',function(Builder $query){$rch=request('recherche');$query->whereRelation('ue','libelleUe','like',"%$rch%")->orWhereRelation('semestre','Nom_Classe','like',"%$rch%")->orWhereRelation('anneeacademique','ANNEE_ACAD','like',"%$rch%");})->orWhereRelation('ecue','libelleEcu','like',"%$rech%")->orWhereRelation('typeEvaluation','libTypEval','like',"%$rech%")->get()]);
    })->name('afficherEvaluations');
    Route::get('/dAttrEcUe', function(){
        $rech=request()->recherche;
        return view('rechercheAttrEcueUe',['items'=>AttrEcueUe::whereRelation('ueSemestre',function(Builder $query){$rch=request('recherche');$query->whereRelation('ue','libelleUe','like',"%$rch%")->orWhereRelation('semestre','Nom_Classe','like',"%$rch%")->orWhereRelation('anneeacademique','ANNEE_ACAD','like',"%$rch%");})->orWhereRelation('ecue','libelleEcu','like',"%$rech%")->orWhereRelation('professeur','nomProf','like',"%$rech%")->orWhereRelation('professeur','prenomsProf','like',"%$rech%")->get()]);
    })->name('afficherAttrEcueUe');
    Route::get('/dIns',function(){
        $rech=request()->recherche;
        return view('displayInscription',['items'=>Inscription::orderBy('MATRICULE')->where('MATRICULE','like',"%$rech%")->orWhereRelation('etudiant','nomEtu','like',"%$rech%")->orWhereRelation('etudiant','prenomsEtu','like',"%$rech%")->orWhereRelation('anneeacademique','ANNEE_ACAD','like',"%$rech%")->orWhereRelation('anneeEtude','Libelle_AnEtude','like',"%$rech%")->get()]);
    })->name('afficherInscription');
    Route::post('/dP', function () {
        $query = Paiement::where('Id_Annee', request()->anneeAcademique);

        if (request()->filled('anneeEtude')) {
            $query->where('ID_FILIERE', request()->anneeEtude);
        }

        if (request()->filled(['dateDebut', 'dateFin'])) {
            $dateDebut = \Carbon\Carbon::parse(request()->dateDebut)->format('Y-m-d');
            $dateFin   = \Carbon\Carbon::parse(request()->dateFin)->format('Y-m-d');
            $query->whereBetween('datePaiement', [$dateDebut, $dateFin]);
        }

        $paiements = $query->orderBy('datePaiement', 'desc')->orderBy('id', 'desc')->get();
        return view('displayPaiement', compact('paiements'));
    });
    Route::post('/dPrev',function(){
        $previsions=Prevision::whereBetween('echeance',[\Carbon\Carbon::parse(request()->dateDebut)->format('Y-m-d'),\Carbon\Carbon::parse(request()->dateFin)->format('Y-m-d')])->get();
        return view('displayPrevisions',compact('previsions'));
    });
    Route::resource('/inscriptions',InscriptionController::class)->except(['show','destroy','update','edit']);
    Route::post('/situation-paiements-echeance', [PaiementController::class, 'situationEcheance'])->name('situation-echeance.display');
    Route::post('/journal-encaissement', [PaiementController::class, 'journalEncaissement'])->name('journal-encaissement.display');
    Route::resource('/paiements',PaiementController::class);
    Route::post('/previsions/generer-classe', [PrevisionController::class, 'genererClasse'])->name('previsions.generer-classe');
    Route::get('/previsions/etudiant-echeancier', [PrevisionController::class, 'getEcheancierEtudiant'])->name('previsions.etudiant-echeancier');
    Route::post('/previsions/enregistrer-echeancier', [PrevisionController::class, 'enregistrerEcheancierEtudiant'])->name('previsions.enregistrer-echeancier');
    Route::resource('/previsions',PrevisionController::class);
    Route::view('/afficher-previsions', 'afficher-previsions')->name('afficher-previsions');
    Route::get('/fiches-engagement', [EngagementController::class, 'index'])->name('engagement.index');
    Route::get('/fiche-engagement/{matricule}/{annee}/{filiere}', [EngagementController::class, 'imprimer'])->name('engagement.imprimer');
    Route::post('/fiches-engagement/classe', [EngagementController::class, 'imprimerClasse'])->name('engagement.imprimer-classe');
    Route::delete('/inscriptions/destroy',[InscriptionController::class,'destroy'])->name('inscriptions.destroy')->middleware('can:Supprimer les inscriptions');
    Route::post('/inscriptions/update',[InscriptionController::class,'update'])->name('inscriptions.update')->middleware('can:Modifier les inscriptions');
    Route::get('/inscriptions/edit',[InscriptionController::class,'edit'])->name('inscriptions.edit')->middleware('can:Modifier les inscriptions');
    Route::post('/export-resultats-annuel','\App\Http\Controllers\AnneeEtudeController@exportExcel')->name('exportRecapAnnuel');
    Route::post('/import-notes',function(){
        $import=new NotesImport(request('id_eval'));
        $import->import(request()->file('excel'));
        if(empty($import->erreurs))
            return redirect()->back()->with('succes','Données importées');
        else
            return redirect()->back()->with(['succes'=>'Données importées','erreurs'=>$import->erreurs]);
    })->name('import-notes');
    Route::get('/export-notes',function(){
        return Excel::download(new NotesExport(request('id_annee'),request('id_an_etude'),request('id_eval')),'notes_'.request('id_an_etude').'_'.Evaluation::find(request('id_eval'))->codeEcu.'_'.request('id_annee').'.xlsx');
    })->name('export-notes');
    Route::post('/import-ue',function(){
        Excel::import(new UEImport, request()->file('csv'));
        return redirect()->back()->with('succes','Données importées');
    })->name("importUE");
    Route::post('/import-ecue',function(){
        Excel::import(new ECUEImport, request()->file('csv'));
        return redirect()->back()->with('succes','Données importées');
    })->name("importECUE");
    Route::post('/inscription',function(){
        Excel::import(new InscriptionImport, request()->file('csv'));
        return redirect()->back()->with('succes','Données importées');
    })->name("importInscription");

    Route::post('/import-attribuer-ue-semestre',function(){
        Excel::import(new AttrUeSemestreImport, request()->file('csv'));
        return redirect()->back()->with('succes','Données importées');
    })->name("importAttrUeSemestre");

    Route::post('/import-attribuer-ecue-ue',function(){
        Excel::import(new AttrEcueUeImport, request()->file('csv'));
        return redirect()->back()->with('succes','Données importées');
    })->name("importAttrEcueUe");
});
Route::get('/logout',function(){
    Auth::logout();
    return to_route('login');
});
