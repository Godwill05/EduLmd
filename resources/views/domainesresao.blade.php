@extends('layout')
@section('title')
Domaines RESAO
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
        @can('Créer un domaine')
        <div class="col-lg-4 col-sm-12 col-md-12">
            <div class="p-4 card">
                <h5 class="fw-bold">Nouveau domaine</h5>
                <form action="{{ route('domainesresao.store') }}" method="POST">
                    @csrf
                    <div class="form-group my-2">
                        <label class="form-label" for="libelleDomaine">Libellé du Domaine</label>
                        <input type="text" name="libelleDomaine" id="libelleDomaine"
                        class="form-control
                        @error('libelleDomaine')
                        is-invalid
                        @enderror">
                        <div class="invalid-feedback">Entrer le libellé de domaine svp !</div>
                    </div>
                    <button class="btn btn-primary mt-4 w-100">Enregistrer</button>
                </form>
            </div>
        </div>
        @endcan
        <div class="@can('Créer un domaine') col-lg-8 @else col-lg-12 @endcan col-sm-12 col-md-12">
            <div class="card p-2">
                <table class="table table-striped datatable">
                    <thead>
                        <th>Libellé de domaine</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach ($domaines as $domaine)
                            <tr>
                                <td>{{$domaine->lib_domaine}}</td>
                                <td>
                                    @can('Supprimer un domaine')
                                    <form action="{{ route('domainesresao.destroy', $domaine->Id_domaine) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                        <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$domaine->lib_domaine}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                    </form>
                                    @else
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>
                                    @endcan
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
                                        <form method="POST" action="{{ route('domainesresao.update', $domaine->Id_domaine) }}" id="modalUpd{{$loop->iteration}}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="mb-3">
                                                <label for="recipient-name" class="col-form-label">Libellé du domaine:</label>
                                                <input type="text" class="form-control" id="recipient-name" name="libelleDomaine" value="{{$domaine->lib_domaine}}">
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
</div>
</section>
@endsection
