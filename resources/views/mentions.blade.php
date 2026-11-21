@extends('layout')
@section('title')
Mentions
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
            <h5 class="fw-bold">Nouvelle mention</h5>
            <form action="{{ route('mentions.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="libelleMention">Libellé de la mention</label>
                            <input type="text" name="libelleMention" id="libelleMention"
                            class="form-control
                            @error('libelleMention')
                            is-invalid
                            @enderror"
                            placeholder="ex:Planification">
                            <div class="invalid-feedback">Ce champ est obligatoire !</div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group my-2">
                            <label class="form-label" for="domaineMention">Domaine de la mention</label>
                            <select name="domaineMention" id="domaineMention" class="form-control form-select
                            @error('domaineMention')
                            is-invalid
                            @enderror">
                                <option value="">Sélectionner un domaine...</option>
                                @foreach ($domaines as $domaine)
                                <option value="{{$domaine->Id_domaine}}">{{$domaine->lib_domaine}}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Sélectionner le domaine svp !</div>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button class="btn btn-primary mt-4">Enregistrer</button>
                </div>
            </form>
        </div>
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Libellé de la mention</th>
                    <th>Libellé du domaine</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($mentions as $mention)
                        <tr>
                            <td>{{$mention->libelleMention}}</td>
                            <td>{{$mention->domaine->lib_domaine}}</td>
                            <td>
                                <form action="{{ route('mentions.destroy', $mention->codeMention) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$mention->libelleMention}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier une mention</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('mentions.update', $mention->codeMention) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-3">
                                            <label class="form-label" for="libelleMention">Libellé de la mention</label>
                                            <input type="text" name="libelleMention" id="libelleMention" class="form-control" placeholder="ex:Planification" value="{{$mention->libelleMention}}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="domaineMention">Domaine de la mention</label>
                                            <select name="domaineMention" id="domaineMention" class="form-control form-select">
                                                <option value="">Sélectionner un domaine...</option>
                                                @foreach ($domaines as $domaine)
                                                <option value="{{$domaine->Id_domaine}}" @selected($mention->Id_domaine==$domaine->Id_domaine)>{{$domaine->lib_domaine}}</option>
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
