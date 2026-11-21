<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EtudiantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /* Sans pagination, la page renvoyait 5 Mo de HTML et 603 requêtes
           SQL pour 297 étudiants : chaque ligne embarque plusieurs modals.
           À 3 000 étudiants la page devenait inutilisable.
           La recherche reste possible via ?recherche=… */
        $recherche = trim((string) request('recherche', ''));

        $requete = Etudiant::query()
            ->with(['typeEtudiant', 'nationalite'])
            ->orderBy('nomEtu')
            ->orderBy('prenomsEtu');

        if ($recherche !== '') {
            $terme = '%' . addcslashes($recherche, '%_\\') . '%';
            $requete->where(function ($q) use ($terme) {
                $q->where('nomEtu', 'like', $terme)
                    ->orWhere('prenomsEtu', 'like', $terme)
                    ->orWhere('MATRICULE', 'like', $terme);
            });
        }

        $items = $requete->paginate(50)->withQueryString();

        /* Les listes déroulantes étaient chargées par la vue elle-même
           (AnneeAcademique::all() dans une boucle), soit des dizaines de
           requêtes SQL par page. Elles sont préparées une seule fois ici. */
        return view('etudiants', [
            'items' => $items,
            'recherche' => $recherche,
            'anneesAcademiques' => \App\Models\AnneeAcademique::orderByDesc('id_Annee_Acad')->get(),
            /* La colonne s'appelle libelleNationalite : `libNationalite`
               provoquait un 500 sur /etudiants (SQLSTATE 42S22). */
            'nationalites' => \App\Models\Nationalite::orderBy('libelleNationalite')->get(),
            'typesEtudiant' => \App\Models\TypeEtudiant::orderBy('libTypeEtu')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $lastMatricule=Etudiant::max('MATRICULE');
        $mat=(substr($lastMatricule,0,1)==2)?(substr($lastMatricule,0,6)+1).substr($request->anneeAcademique,-2,2):'200000'.substr($request->anneeAcademique,-2,2);
        $request->validate([
            'nom'=>'required',
            'prenom'=>'required',
            'dateNaissance'=>'required',
            'lieuNaissance'=>'required',
            'sexe'=>'required',
            'telephone'=>'required',
            'email'=>'required',
            'typeEtudiant'=>'required',
            'nationalite'=>'required',
        ]);
        /* La photo n'est pas un champ obligatoire du formulaire : sans ce
           test, créer un étudiant sans photo provoquait
           « Call to a member function extension() on null » (erreur 500). */
        $photo = $request->file('photo');
        $nomPhoto = null;
        if ($photo && $photo->isValid()) {
            $extension = strtolower($photo->extension() ?: 'jpg');
            $nomPhoto = $mat . '.' . $extension;
            /* Écriture dans public/assets/img/etudiants : c'est le chemin que
               lit la fiche (/assets/img/etudiants/…). L'ancien
               storeAs('public/…') déposait le fichier dans storage/app/public,
               servi nulle part sur ce déploiement. */
            $dossier = public_path('assets/img/etudiants');
            if (! is_dir($dossier)) {
                @mkdir($dossier, 0755, true);
            }
            $photo->move($dossier, $nomPhoto);
        }

        Etudiant::create([
            'MATRICULE'=>$mat,
            'nomEtu'=>$request->nom,
            'prenomsEtu'=>$request->prenom,
            'dateNaisEtu'=>$request->dateNaissance,
            'lieuNaisEtu'=>$request->lieuNaissance,
            'sexeEtu'=>$request->sexe,
            'telEtu'=>$request->telephone,
            'emailEtu'=>$request->email,
            'Observation'=>($request->observations??'Aucune'),
            'codeTypeEtu'=>$request->typeEtudiant,
            'codeNationalite'=>$request->nationalite,
            'photo'=>$nomPhoto,
            'Auteur'=>Auth::user()->login
        ]);
        return to_route('etudiants.index')->with('succes','Etudiant enregistré !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'matricule'=>'required',
            'nom'=>'required',
            'prenom'=>'required',
            'dateNaissance'=>'required',
            'lieuNaissance'=>'required',
            'sexe'=>'required',
            'telephone'=>'required',
            'email'=>'required',
            'typeEtudiant'=>'required',
            'nationalite'=>'required',
        ]);
        Etudiant::find($id)->update([
            'MATRICULE'=>$request->matricule,
            'nomEtu'=>$request->nom,
            'prenomsEtu'=>$request->prenom,
            'dateNaisEtu'=>$request->dateNaissance,
            'lieuNaisEtu'=>$request->lieuNaissance,
            'sexeEtu'=>$request->sexe,
            'telEtu'=>$request->telephone,
            'emailEtu'=>$request->email,
            'Observation'=>$request->observations,
            'codeTypeEtu'=>$request->typeEtudiant,
            'codeNationalite'=>$request->nationalite,
        ]);
        return to_route('etudiants.index')->with('succes','Etudiant modifié !');
    }

    public function managePhoto($id) {
        $etudiant=Etudiant::find($id);
        if(request('btn')=="Supprimer l'image")
        {
            if(Storage::exists('public/assets/img/etudiants/'.$etudiant->photo))
            {
                Storage::delete('public/assets/img/etudiants/'.$etudiant->photo);
                $etudiant->photo=NULL;
                $etudiant->save();
                return to_route('etudiants.index')->with('succes','Photo supprimée !');
            }
            $etudiant->photo=NULL;
            $etudiant->save();
            return to_route('etudiants.index')->with('succes','Photo supprimée !');
        }
        if(request('btn')=='Enregistrer')
        {
            $photo=request()->file('photo');
            $extension=$photo->extension();
            $photo->storeAs('public/assets/img/etudiants',$etudiant->MATRICULE.'.'.$extension);
            $etudiant->photo=$etudiant->MATRICULE.'.'.$extension;
            $etudiant->save();
            return to_route('etudiants.index')->with('succes','Photo enregistrée !');
        }
        if (request('btn')=='Modifier') {
            if(Storage::exists('public/assets/img/etudiants/'.$etudiant->photo))
            {
                Storage::delete('public/assets/img/etudiants/'.$etudiant->photo);
                $photo=request()->file('photo');
                $extension=$photo->extension();
                $photo->storeAs('public/assets/img/etudiants',$etudiant->MATRICULE.'.'.$extension);
                $etudiant->photo=$etudiant->MATRICULE.'.'.$extension;
                $etudiant->save();
                return to_route('etudiants.index')->with('succes','Photo modifiée !');
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Etudiant::find($id)->delete();
        return to_route('etudiants.index')->with('succes','Etudiant supprimé !');
    }
}
