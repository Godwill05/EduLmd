@extends('layout')
@section('title')
Semestres Etudes
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
        <div class="col-lg-12">
            <div class="p-4 card">
                <h5 class="fw-bold">Construction des semestres du grade d'une spécialité</h5>
                <form action="{{ route('semestres-etudes.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-4 col-md-12 col-sm-12">
                            <div class="form-group my-2">
                                <label class="form-label" for="libelleSemestre">Libellé du semestre</label>
                                <input type="text" name="libelleSemestre" id="libelleSemestre" class="form-control
                                @error('libelleSemestre')
                                is-invalid
                                @enderror" placeholder="LPs1-GBA" value="{{old('libelleSemestre')}}">
                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 col-sm-12">
                            <div class="form-group my-2">
                                <label class="form-label" for="grade">Grade</label>
                                <select name="grade" id="grade" class="form-control form-select
                                @error('grade')
                                is-invalid
                                @enderror">
                                    <option value="">Sélectionner une grade</option>
                                    @foreach ($grades as $grade)
                                    <option value="{{$grade->codeGrade}}">{{$grade->libelleGrade}}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 col-sm-12">
                            <div class="form-group my-2">
                                <label class="form-label" for="semestre">Semestre</label>
                                <select name="semestre" id="semestre" class="form-control form-select
                                @error('semestre')
                                is-invalid
                                @enderror">
                                    <option value="">Sélectionner une semestre</option>
                                    @foreach ($semestres as $semestre)
                                    <option value="{{$semestre->numeroSemestre}}">{{$semestre->Lib_Semestre}}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group my-2">
                                <label class="form-label" for="specialite">Spécialité</label>
                                <select name="specialite" id="specialite" class="form-control form-select
                                @error('specialite')
                                is-invalid
                                @enderror">
                                    <option value="">Sélectionner une spécialité</option>
                                    @foreach ($specialites as $sp)
                                    <option value="{{$sp->codeSpe}}">{{$sp->libelleSpe}}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-12 col-md-12">
                            <div class="form-group my-2">
                                <label for="option" class="form-label">Option</label>
                                <input type="text" name="option" id="option" class="form-control" placeholder="Analyse Informatique et Programmation">
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card p-2">
                <table class="table table-striped datatable">
                    <thead>
                        <th>Nom du semestre</th>
                        <th>Semestre correspondant</th>
                        <th>Grade correspondant</th>
                        <th>Spécialité correspondante</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach ($semestresEtudes as $smEtd)
                            <tr>
                                <td>{{$smEtd->Nom_Classe}}</td>
                                <td>{{$smEtd->semestre->Lib_Semestre}}</td>
                                <td>{{$smEtd->grade->libelleGrade}}</td>
                                <td>{{$smEtd->specialite->libelleSpe}} {{($smEtd->options)?"($smEtd->options)":""}}</td>
                                <td>
                                    <form action="{{ route('semestres-etudes.destroy', $smEtd->id_S_Gr_Spe) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class="bi bi-pencil text-secondary"></i></button>

                                        <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$smEtd->Nom_Classe}}?')" class="btn"><i class="bi bi-trash text-danger"></i></button>
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
                                        <form method="POST" action="{{ route('semestres-etudes.update', $smEtd->id_S_Gr_Spe) }}" id="modalUpd">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="libelleSemestre">Libellé du semestre</label>
                                                <input type="text" name="libelleSemestre" id="libelleSemestre" class="form-control" placeholder="LPs1-GBA" value="{{old('libelleSemestre',$smEtd->Nom_Classe)}}">
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="grade">Grade</label>
                                                <select name="grade" id="grade" class="form-control form-select">
                                                    <option value="">Sélectionner une grade</option>
                                                    @foreach ($grades as $grade)
                                                    <option value="{{$grade->codeGrade}}" @selected($grade->codeGrade==$smEtd->grade->codeGrade)>{{$grade->libelleGrade}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="semestre">Semestre</label>
                                                <select name="semestre" id="semestre" class="form-control form-select">
                                                    <option value="">Sélectionner une semestre</option>
                                                    @foreach ($semestres as $semestre)
                                                    <option value="{{$semestre->numeroSemestre}}" @selected($semestre->numeroSemestre==$smEtd->semestre->numeroSemestre)>{{$semestre->Lib_Semestre}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="specialite">Spécialité</label>
                                                <select name="specialite" id="specialite" class="form-control form-select">
                                                    <option value="">Sélectionner une spécialité</option>
                                                    @foreach ($specialites as $sp)
                                                    <option value="{{$sp->codeSpe}}" @selected($sp->codeSpe==$smEtd->specialite->codeSpe)>{{$sp->libelleSpe}}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="option" class="form-label">Option</label>
                                                <input type="text" name="option" id="option" class="form-control" placeholder="Analyse Informatique et Programmation" value="{{old('option',$smEtd->options)}}">
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
