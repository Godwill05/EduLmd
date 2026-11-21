@extends('layout')
@section('title')
Grades
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
        <div class="col-lg-4 col-sm-12 col-md-12">
            <div class="p-4 card">
                <h5 class="fw-bold">Nouveau grade</h5>
                <form action="{{ route('grades.store') }}" method="POST">
                    @csrf
                    <div class="form-group my-2">
                        <label class="form-label" for="codeGrade">Code du grade</label>
                        <input type="text" name="codeGrade" id="codeGrade" class="form-control
                        @error('codeGrade')
                        is-invalid
                        @enderror">
                        <div class="invalid-feedback">Ce champ est obligatoire !</div>
                    </div>
                    <div class="form-group my-2">
                        <label class="form-label" for="libelleGrade">Libellé du grade</label>
                        <input type="text" name="libelleGrade" id="libelleGrade" class="form-control
                        @error('libelleGrade')
                        is-invalid
                        @enderror">
                        <div class="invalid-feedback">Ce champ est obligatoire !</div>
                    </div>
                    <button class="btn btn-primary mt-4 w-100">Enregistrer</button>
                </form>
            </div>
        </div>
        <div class="col-lg-8 col-sm-12 col-md-12">
            <div class="card p-2">
                <table class="table table-striped datatable">
                    <thead>
                        <th>Libellé du grade</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach ($grades as $grade)
                            <tr>
                                <td>{{$grade->libelleGrade}}</td>
                                <td>
                                    <form action="{{ route('grades.destroy', $grade->codeGrade) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class="bi bi-pencil text-secondary"></i></button>

                                        <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$grade->libelleGrade}}?')" class="btn"><i class="bi bi-trash text-danger"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier un grade</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="{{ route('grades.update', $grade->codeGrade) }}" id="modalUpd">
                                            @csrf
                                            @method('PATCH')
                                            <div class="mb-3">
                                                <label for="recipient-nam" class="col-form-label">Code du grade:</label>
                                                <input type="text" class="form-control" id="recipient-nam" name="codeGrade" value="{{$grade->codeGrade}}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="recipient-name" class="col-form-label">Libellé du grade:</label>
                                                <input type="text" class="form-control" id="recipient-name" name="libelleGrade" value="{{$grade->libelleGrade}}">
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
