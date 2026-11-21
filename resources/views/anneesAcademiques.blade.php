@extends('layout')
@section('title')
Années académiques
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
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger">
            {{session('echec')}}
        </div>
    </div>
    @endif
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0">Gestion des années académiques</h4>
            <a href="{{ route('passage-promotion.index') }}" class="btn btn-success shadow-sm">
                <i class=" ri-arrow-right-up-line me-1"></i> Passage de promotion des étudiants
            </a>
        </div>
    </div>
    <div class="row">
        <div class="p-4 card">
            <h5 class="fw-bold">Nouvelle année académique</h5>
            <form action="{{ route('annees-academiques.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="annee">Année académique <span class="text-danger">*</span></label>
                            <input type="text" name="annee" id="annee" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="dateDebut">Date début <span class="text-danger">*</span></label>
                            <input type="date" name="dateDebut" id="dateDebut" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="dateFin">Date fin <span class="text-danger">*</span></label>
                            <input type="date" name="dateFin" id="dateFin" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="pourcentage">% de validation (en %) <span class="text-danger">*</span></label>
                            <input type="number" name="pourcentage" id="pourcentage" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="moyenne">Moyenne <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" max="20" min="0" name="moyenne" id="moyenne" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="moyenneUE">Moyenne UE <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" max="20" min="0" name="moyenneUE" id="moyenneUE" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="moyenneECUE">Moyenne ECUE <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" max="20" min="0" name="moyenneECUE" id="moyenneECUE" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Année académique</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>% de validation</th>
                    <th>Moyenne de validation</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{$item->ANNEE_ACAD}}</td>
                            <td>{{$item->Date_Debut}}</td>
                            <td>{{$item->Date_Fin}}</td>
                            <td>{{$item->Ptge_Validation*100}}</td>
                            <td>{{$item->Moyenne}}</td>
                            <td>
                                <form action="{{ route('annees-academiques.destroy', $item->id_Annee_Acad) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$item->lib_domaine}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier un domaine</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('annees-academiques.update', $item->id_Annee_Acad) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-group my-2">
                                            <label class="form-label" for="annee">Année académique <span class="text-danger">*</span></label>
                                            <input type="text" name="annee" id="annee" class="form-control" value="{{old('annee',$item->ANNEE_ACAD)}}">
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="dateDebut">Date début <span class="text-danger">*</span></label>
                                                    <input type="date" name="dateDebut" id="dateDebut" class="form-control" value="{{old('dateDebut',$item->Date_Debut)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="dateFin">Date fin <span class="text-danger">*</span></label>
                                                    <input type="date" name="dateFin" id="dateFin" class="form-control" value="{{old('dateFin',$item->Date_Fin)}}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group my-2">
                                            <label class="form-label" for="pourcentage">% de validation <span class="text-danger">*</span></label>
                                            <input type="number" name="pourcentage" id="pourcentage" class="form-control" value="{{old('pourcentage',$item->Ptge_Validation*100)}}">
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-12 col-sm-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="moyenne">Moyenne <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" max="20" min="0" name="moyenne" id="moyenne" class="form-control" value="{{old('moyenne',$item->Moyenne)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-12 col-sm-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="moyenneUE">Moyenne UE <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" max="20" min="0" name="moyenneUE" id="moyenneUE" class="form-control" value="{{old('moyenneUE',$item->MoyenneUE)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-12 col-sm-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="moyenneECUE">Moyenne ECUE <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" max="20" min="0" name="moyenneECUE" id="moyenneECUE" class="form-control" value="{{old('moyenneECUE',$item->MoyenneECUE)}}">
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <div class="mt-3 text-end">
                                        <a onclick="$('#modalUpd{{$loop->iteration}}').submit();" class="btn btn-primary">Modifier</a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>
@endsection
