@extends('layout')
@section('title')
Salles
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
            <h5 class="fw-bold">Nouvelle salle</h5>
            <form action="{{ route('salles.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="codeSalle">Code Salle <span class="text-danger">*</span></label>
                            <input type="text" name="codeSalle" id="codeSalle" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="salle">Salle <span class="text-danger">*</span></label>
                            <input type="text" name="salle" id="salle" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nbPlacesCours">Nombre de places (cours) <span class="text-danger">*</span></label>
                            <input type="number" name="nbPlacesCours" id="nbPlacesCours" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nbPlacesCompositions">Nombre de places (compositions) <span class="text-danger">*</span></label>
                            <input type="number" name="nbPlacesCompositions" id="nbPlacesCompositions" class="form-control">
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
                    <th>Code de la salle</th>
                    <th>Salle</th>
                    <th>Nombre de places (cours)</th>
                    <th>Nombre de places (compositions)</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{$item->codeSalle}}</td>
                            <td>{{$item->Lib_Salle}}</td>
                            <td>{{$item->nombrePlaceCours}}</td>
                            <td>{{$item->nombrePlaceCompo}}</td>
                            <td>
                                <form action="{{ route('salles.destroy', $item->codeSalle) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$item->Lib_Salle}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
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
                                    <form method="POST" action="{{ route('salles.update', $item->codeSalle) }}" id="modalUpd{{$loop->iteration}}">

@csrf
                                        @method('PATCH')

                                        <div class="form-group my-2">
                                            <label class="form-label" for="codeSalle">Code Salle <span class="text-danger">*</span></label>
                                            <input type="text" name="codeSalle" id="codeSalle" class="form-control" value="{{old('codeSalle',$item->codeSalle)}}">
                                        </div>
                                        <div class="form-group my-2">
                                            <label class="form-label" for="salle">Salle <span class="text-danger">*</span></label>
                                            <input type="text" name="salle" id="salle" class="form-control" value="{{old('salle',$item->Lib_Salle)}}">
                                        </div>

                                        <div class="form-group my-2">
                                            <label class="form-label" for="nbPlacesCours">Nombre de places (cours) <span class="text-danger">*</span></label>
                                            <input type="number" name="nbPlacesCours" id="nbPlacesCours" class="form-control" value="{{old('nbPlacesCours',$item->nombrePlaceCours)}}">
                                        </div>

                                        <div class="form-group my-2">
                                            <label class="form-label" for="nbPlacesCompositions">Nombre de places (compositions) <span class="text-danger">*</span></label>
                                            <input type="number" name="nbPlacesCompositions" id="nbPlacesCompositions" class="form-control" value="{{old('nbPlacesCompositions',$item->nombrePlaceCompo)}}">
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
