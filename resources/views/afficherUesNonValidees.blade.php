@extends('layout')
@section('title')
Affichage des UEs non validées
@endsection
@section('content')
<section class="section">
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success">
            {{session('succes')}}
        </div>
    </div>
    @endif
    <div class="row">
        <div class="p-4 card">
            <h5 class="fw-bold">Affichage des UEs non validées</h5>
            <form action="{{ route('afficherUesNonValidees') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="anneeacademique">Année académique</label>
                            <select name="anneeacademique" id="anneeacademique" class="form-select form-control">
                                <option value="">Sélectionner une année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anAc)
                                <option value="{{$anAc->id_Annee_Acad}}">{{$anAc->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="anneeEtude">Année d'étude</label>
                            <select name="anneeEtude" id="anneeEtude" class="form-select form-control">
                                <option value="">Sélectionner une année d'étude...</option>
                                @foreach (\App\Models\AnneeEtude::all() as $anneeEtude)
                                <option value="{{$anneeEtude->Code_Annee_Etude}}">{{$anneeEtude->Libelle_AnEtude}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary">Afficher</button>
                </div>
            </form>
        </div>
        @isset($items)
        <div class="card p-4">
            <div class="text-center h4 mt-2">{{$annee_etude}} | {{$anneeAcademique}} <a href="{{ route('impression.ues-nv') }}?annee_acad_non_val={{\App\Models\AnneeAcademique::where('ANNEE_ACAD',$anneeAcademique)->first()->id_Annee_Acad}}&annee_etude_non_val={{\App\Models\AnneeEtude::where('Libelle_AnEtude',$annee_etude)->first()->Code_Annee_Etude}}" class="btn btn-dark"><i class="bx bx-printer"></i> Imprimer</a></div>
            <table class="table table-striped datatable">
                <thead>
                    <th>Matricule</th>
                    <th>Nom complet</th>
                    <th>Action</th>
                </thead>
                <tbody>
                    @foreach ($etudiants as $etudiant)
                    <tr>
                        <td>{{$etudiant->etu->MATRICULE}}</td>
                        <td>{{$etudiant->etu->nomEtu}} {{$etudiant->etu->prenomsEtu}}</td>
                        <td><button type="button" class="btn btn-outline-dark" title="Voir" data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"><i class="bi bi-eye"></i></button></td>

                        <!-- Modal Body -->
                        <!-- if you want to close by clicking outside the modal, delete the last endpoint:data-bs-backdrop and data-bs-keyboard -->
                        <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">UEs non validées</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="container-fluid">
                                            <div class="row text-center">
                                                <div class="col-6 border border-0 border-bottom border-end border-light fw-bolder text-bg-dark">
                                                    UE
                                                </div>
                                                <div class="col-6 border border-0 border-bottom border-start border-light fw-bolder text-bg-dark">
                                                    Note
                                                </div>
                                            </div>
                                            @foreach ($items->where('MATRICULE',$etudiant->MATRICULE) as $item)
                                            <div class="row text-center">
                                                <div class="col-6 border border-dark">
                                                    {{$item->ueSem->ue->libelleUe}}
                                                </div>
                                                <div class="col-6 border border-dark">
                                                    {{$item->Moyenne_UE}}
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="text-end my-2">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endisset
    </div>
</div>
</section>
@endsection
