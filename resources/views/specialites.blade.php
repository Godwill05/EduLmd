@extends('layout')
@section('title')
Spécialités
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
            <h5 class="fw-bold">Nouvelle spécialité</h5>
            <form action="{{ route('specialites.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="codeSpecialite">Code de la spécialité</label>
                            <input type="text" name="codeSpecialite" id="codeSpecialite" class="form-control
                            @error('codeSpecialite')
                            is-invalid
                            @enderror" placeholder="ex:DESCOGEF">
                            <div class="invalid-feedback">Ce champ est obligatoire !</div>
                        </div>
                    </div>
                    <div class="col-lg-8 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="libelleSpecialite">Libellé de la spécialité</label>
                            <input type="text" name="libelleSpecialite" id="libelleSpecialite" class="form-control
                            @error('libelleSpecialite')
                            is-invalid
                            @enderror" placeholder="ex:Gestion des Banques et Assurances">
                            <div class="invalid-feedback">Ce champ est obligatoire !</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="mentionSpecialite">Mention de la spécialité</label>
                            <select name="mentionSpecialite" id="mentionSpecialite" class="form-control form-select
                            @error('mentionSpecialite')
                            is-invalid
                            @enderror">
                                <option value="">Sélectionner une mention...</option>
                                @foreach ($mentions as $mention)
                                <option value="{{$mention->codeMention}}">{{$mention->libelleMention}}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire !</div>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button class="btn btn-primary mt-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Libellé de la spécialité</th>
                    <th>Libellé de la mention</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($specialites as $specialite)
                        <tr>
                            <td>{{$specialite->libelleSpe}}</td>
                            <td>{{$specialite->mention->libelleMention}}</td>
                            <td>
                                <form action="{{ route('specialites.destroy', $specialite->codeSpe) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$specialite->libelleSpe}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier une spécialité</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('specialites.update', $specialite->codeSpe) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-3">
                                            <label class="form-label" for="codeSpecialite">Code de la spécialité</label>
                                            <input type="text" name="codeSpecialite" id="codeSpecialite" class="form-control" placeholder="ex:DECOGEF" value="{{$specialite->codeSpe}}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="libelleSpecialite">Libellé de la spécialité</label>
                                            <input type="text" name="libelleSpecialite" id="libelleSpecialite" class="form-control" placeholder="ex:Gestion des Banques et Assurances" value="{{$specialite->libelleSpe}}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="mentionSpecialite">Mention de la spécialité</label>
                                            <select name="mentionSpecialite" id="mentionSpecialite" class="form-control form-select">
                                                <option value="">Sélectionner une mention...</option>
                                                @foreach ($mentions as $mention)
                                                <option value="{{$mention->codeMention}}" @selected($specialite->mention->codeMention==$mention->codeMention)>{{$mention->libelleMention}}</option>
                                                @endforeach
                                            </select>
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
