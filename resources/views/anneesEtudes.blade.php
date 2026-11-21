@extends('layout')
@section('title')
Années Etudes
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
    <div class="row">
        <div class="p-4 card">
            <h5 class="fw-bold">Nouvelle année d'étude</h5>
            <form action="{{ route('annees-etudes.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="codeAnEtd">Code Année Etude</label>
                            <input type="text" name="codeAnEtd" id="codeAnEtd" class="form-control
                            @error('codeAnEtd')
                            is-invalid
                            @enderror">
                            <div class="invalid-feedback">Ce champ est obligatoire !</div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="libelleAnEtd">Libellé Année Etude</label>
                            <input type="text" name="libelleAnEtd" id="libelleAnEtd" class="form-control
                            @error('libelleAnEtd')
                            is-invalid
                            @enderror">
                            <div class="invalid-feedback">Ce champ est obligatoire !</div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Code Année Etude</th>
                    <th>Libellé Année Etude</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($anneesEtudes as $anneeEtude)
                        <tr>
                            <td>{{$anneeEtude->Code_Annee_Etude}}</td>
                            <td>{{$anneeEtude->Libelle_AnEtude}}</td>
                            <td>
                                <form action="{{ route('annees-etudes.destroy', $anneeEtude->Code_Annee_Etude) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$anneeEtude->Libelle_AnEtude}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
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
                                    <form method="POST" action="{{ route('annees-etudes.update', $anneeEtude->Code_Annee_Etude) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-3">
                                            <label for="recipient-nam" class="col-form-label">Code Année Etude:</label>
                                            <input type="text" class="form-control" id="recipient-nam" name="Code_Annee_Etude" value="{{$anneeEtude->Code_Annee_Etude}}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="recipient-name" class="col-form-label">Libellé Année Etude:</label>
                                            <input type="text" class="form-control" id="recipient-name" name="Libelle_AnEtude" value="{{$anneeEtude->Libelle_AnEtude}}">
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
