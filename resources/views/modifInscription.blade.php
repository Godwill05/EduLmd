@extends('layout')
@section('title')
Modification d'inscription
@endsection
@section('content')
<section class="section">
<div class="container-fluid">
    <div class="row">
        <div class="card p-4">
            <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier les informations d'une UE</h1>
            <form method="POST" action="{{ route('inscriptions.update') }}" id="modalUpd">
                @csrf
                <div class="row g-3">
                    <input type="hidden" name="matricule" value="{{$item->MATRICULE}}">
                    <input type="hidden" name="anneeAcademique_old" value="{{$item->Id_Annee}}">
                    <input type="hidden" name="anneeEtude_old" value="{{$item->ID_FILIERE}}">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="etudiant">Etudiant <span class="text-danger">*</span></label>
                            <label class="form-control bg-secondary bg-opacity-10">{{$item->MATRICULE}} ({{\App\Models\Etudiant::find($item->MATRICULE)->nomEtu}} {{\App\Models\Etudiant::find($item->MATRICULE)->prenomsEtu}})</label>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeAcademique">Année académique <span class="text-danger">*</span></label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                <option value="">Séléctionner une année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anAc)
                                <option value="{{$anAc->id_Annee_Acad}}" @selected($anAc->id_Annee_Acad==$item->Id_Annee)>{{$anAc->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="groupePedagogique">Groupe pédagogique <span class="text-danger">*</span></label>
                            <select name="groupePedagogique" id="groupePedagogique" class="form-control form-select">
                                <option value="" @selected($item->Group_PDGQ=="")>Aucun</option>
                                <option value="A" @selected($item->Group_PDGQ=="A")>A</option>
                                <option value="B" @selected($item->Group_PDGQ=="B")>B</option>
                                <option value="C" @selected($item->Group_PDGQ=="C")>C</option>
                                <option value="D" @selected($item->Group_PDGQ=="D")>D</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="montantAPayer">Montant à payer (en FCFA)<span class="text-danger">*</span></label>
                            <input type="number" name="montantAPayer" id="montantAPayer" class="form-control" placeholder="10000" value="{{$item->frais_paye}}">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeEtude">Année d'étude <span class="text-danger">*</span></label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-select">
                                <option value="">Séléctionner une année d'étude...</option>
                                @foreach (\App\Models\AnneeEtude::all() as $anEt)
                                <option value="{{$anEt->Code_Annee_Etude}}" @selected($anEt->Code_Annee_Etude==$item->ID_FILIERE)> {{$anEt->Libelle_AnEtude}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="situationFinanciere">Situation financière <span class="text-danger">*</span></label>
                            <select name="situationFinanciere" id="situationFinanciere" class="form-control form-select">
                                <option value="OUI" @selected($item->S_Finance=="OUI")>Inscrit</option>
                                <option value="NON" @selected($item->S_Finance=="NON")>Non inscrit</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="statut">Statut</label>
                            <select name="statut" id="statut" class="form-control form-select">
                                <option value="">Séléctionner un statut...</option>
                                @foreach (\App\Models\Statut::all() as $statut)
                                <option value="{{$statut->codeStatut}}" @selected($statut->codeStatut==$item->statut->codeStatut)>{{$statut->LibStatut}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="observations">Observations</label>
                            <textarea name="observations" id="observations" class="form-control" placeholder="Aucune"></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-end">
                    <button class="btn-soft-primary">Modifier</button>
                </div>

            </form>
        </div>
    </div>
</div>
</section>
@endsection
