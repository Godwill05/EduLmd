<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('inscriptions',['items'=>Inscription::paginate(5),'etuds'=>Etudiant::all()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'etudiant'=>'required',
            'anneeAcademique'=>'required',
            'montantAPayer'=>'required',
            'anneeEtude'=>'required',
            'situationFinanciere'=>'required',
        ]);
        $inscription=new Inscription();
        $inscription->MATRICULE=$request->etudiant;
        $inscription->Id_Annee=$request->anneeAcademique;
        $inscription->ID_FILIERE=$request->anneeEtude;
        $inscription->codeStatut=$request->statut;
        $inscription->Group_PDGQ=$request->groupePedagogique;
        $inscription->frais_paye=$request->montantAPayer;
        $inscription->Observations=$request->observations;

        // reste_paye et S_Finance sont dérivés du montant à payer, jamais du select
        // situationFinanciere saisi par l'utilisateur (source d'incohérence bdd/affichage :
        // ex. S_Finance='OUI' enregistré alors que reste_paye>0, ce qui affiche
        // "Soldé !" à tort côté vue).
        $inscription->reste_paye=$inscription->frais_paye;
        $inscription->S_Finance=$inscription->reste_paye<=0 ? 'OUI' : 'NON';

        $inscription->Auteur=Auth::user()->login;
        $inscription->save();
        return to_route('inscriptions.index')->with('succes','Inscription enregistrée');
    }

    public function edit()
    {
        return view('modifInscription',['item'=>Inscription::where('MATRICULE',request()->m)
        ->where('Id_Annee',request()->aa)
        ->where('ID_FILIERE',request()->ae)->first()]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Inscription  $inscription
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $matricule = (string)$request->matricule;
        $anneeOld = $request->anneeAcademique_old;
        $filiereOld = (string)$request->anneeEtude_old;

        $inscriptionQuery = Inscription::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeOld)
            ->where('ID_FILIERE', $filiereOld);

        $inscription = $inscriptionQuery->first();

        if (!$inscription) {
            return to_route('inscriptions.index')->with('echec', 'Inscription introuvable !');
        }

        $totalPaye = (int) Paiement::where('MATRICULE', $matricule)
            ->where('Id_Annee', $anneeOld)
            ->where('ID_FILIERE', $filiereOld)
            ->sum('montantPaye');

        $nouveauFrais = (int) $request->montantAPayer;
        $nouveauReste = $nouveauFrais - $totalPaye;

        if ($nouveauReste < 0) {
            return to_route('inscriptions.index')->with('echec', 'Le total des paiements déjà effectués ('.number_format($totalPaye, 0, ',', ' ').' F) dépasse le nouveau montant des frais !');
        }

        $statutFinance = $nouveauReste <= 0 ? 'OUI' : 'NON';

        $anneeNew = $request->anneeAcademique;
        $filiereNew = (string)$request->anneeEtude;

        $inscriptionQuery->update([
            'Id_Annee' => $anneeNew,
            'ID_FILIERE' => $filiereNew,
            'codeStatut' => $request->statut,
            'Group_PDGQ' => $request->groupePedagogique,
            'frais_paye' => $nouveauFrais,
            'reste_paye' => $nouveauReste,
            'S_Finance' => $statutFinance,
            'Observations' => $request->observations,
        ]);

        if ($anneeOld != $anneeNew || $filiereOld != $filiereNew) {
            Paiement::where('MATRICULE', $matricule)
                ->where('Id_Annee', $anneeOld)
                ->where('ID_FILIERE', $filiereOld)
                ->update([
                    'Id_Annee' => $anneeNew,
                    'ID_FILIERE' => $filiereNew,
                ]);
        }

        return to_route('inscriptions.index')->with('succes', 'Inscription modifiée avec succès !');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Inscription::where('MATRICULE',$request->matricule)
                   ->where('Id_Annee',$request->anneeAcademique)
                   ->where('ID_FILIERE',$request->anneeEtude)
                   ->delete();
        return to_route('inscriptions.index')->with('succes','Inscription supprimée');
    }
}