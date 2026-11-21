@extends('layout')
@section('title')
Etudiants
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
            <h5 class="fw-bold">Nouvel étudiant</h5>
            <form action="{{ route('etudiants.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="anneeAcademique" class="form-label">Année académique</label>
                            <select required name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                <option value="">Sélectionner l'année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                <option value="{{$anneeAcademique->ANNEE_ACAD}}">{{$anneeAcademique->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
                            <input required type="text" name="nom" id="nom" class="form-control" placeholder="Doe">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="prenom">Prénom(s) <span class="text-danger">*</span></label>
                            <input required type="text" name="prenom" id="prenom" class="form-control" placeholder="John">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="dateNaissance">Date de naissance <span class="text-danger">*</span></label>
                            <input required type="date" name="dateNaissance" id="dateNaissance" class="form-control" placeholder="01/01/2001">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="lieuNaissance">Lieu de naissance <span class="text-danger">*</span></label>
                            <input required type="text" name="lieuNaissance" id="lieuNaissance" class="form-control" placeholder="Nowhere">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nationalite">Nationalité <span class="text-danger">*</span></label>
                            <select required class="form-control form-select" name="nationalite" id="nationalite">
                                <option value="">Sélectionner une nationalité...</option>
                                @foreach (\App\Models\Nationalite::all() as $nationalite)
                                <option value="{{$nationalite->codeNationalite}}">{{$nationalite->libelleNationalite}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="sexe">Sexe <span class="text-danger">*</span></label>
                            <select required class="form-control form-select" name="sexe" id="sexe">
                                <option value="">Sélectionner un genre...</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                            <input required type="text" name="telephone" id="telephone" class="form-control" placeholder="+229 90011234">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input required type="text" name="email" id="email" class="form-control" placeholder="nom@example.com">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="typeEtudiant">Type d'étudiant <span class="text-danger">*</span></label>
                            <select required class="form-control form-select" name="typeEtudiant" id="typeEtudiant">
                                <option value="">Sélectionner un type...</option>
                                @foreach (\App\Models\TypeEtudiant::all() as $te)
                                <option value="{{$te->codeTypeEtu}}">{{$te->libTypeEtu}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="photo" class="form-label">Photo</label>
                            <input type="file" name="photo" id="photo" class="form-control form-control-file" accept="image/*">
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
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Photo</th>
                    <th>Matricule</th>
                    <th>Nom Complet</th>
                    <th>Contacts</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            @empty($item->photo)
                            @if ($item->sexeEtu=='M')
                            <td><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}"><img src="./storage/assets/img/etudiants/M.png" alt=""  class="avatar imhvr"></a></td>
                            @elseif ($item->sexeEtu=='F')
                            <td><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}"><img src="./storage/assets/img/etudiants/Mlle.png" alt=""  class="avatar imhvr"></a></td>
                            @endif
                            @endempty
                            @isset($item->photo)
                            <td><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}"><img src="./storage/assets/img/etudiants/{{$item->photo}}" alt=""  class="avatar imhvr"></a></td>
                            @endisset
                            <td>{{$item->MATRICULE}}</td>
                            <td>{{$item->nomEtu}} {{$item->prenomsEtu}}</td>
                            <td>Email : {{$item->emailEtu}}<br>Téléphone : {{$item->telEtu}}</td>
                            <td>
                                <form action="{{ route('etudiants.destroy', $item->MATRICULE) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" title="Voir"><i class="bi bi-eye" data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"></i></button>
                                    <button type="button" class="btn" title="Modifier" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class="bi bi-pencil text-secondary"></i></button>
                                    <button type="submit" title="Supprimer" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$item->nomEtu}} {{$item->prenomsEtu}}?')" class="btn"><i class="bi bi-trash text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">A propos de l'étudiant</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Matricule : {{$item->MATRICULE}}
                                        <hr>
                                        Nom : {{$item->nomEtu}}
                                        <hr>
                                        Prénom(s) : {{$item->prenomsEtu}}
                                        <hr>
                                        Date de naissance : {{$item->dateNaisEtu}}
                                        <hr>
                                        Lieu de naissance : {{$item->lieuNaisEtu}}
                                        <hr>
                                        Sexe : {{$item->sexeEtu}}
                                        <hr>
                                        Téléphone : {{$item->telEtu}}
                                        <hr>
                                        Email : {{$item->emailEtu}}
                                        <hr>
                                        Observations : {{$item->Observation}}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier un étudiant</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('etudiants.update', $item->MATRICULE) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row g-3">
                                            <div class="col-lg-4 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="matricule">Matricule <span class="text-danger">*</span></label>
                                                    <input type="text" name="matricule" id="matricule" class="form-control" placeholder="11111111" value="{{$item->MATRICULE}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
                                                    <input value="{{$item->nomEtu}}" type="text" name="nom" id="nom" class="form-control" placeholder="Doe">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="prenom">Prénom(s) <span class="text-danger">*</span></label>
                                                    <input value="{{$item->prenomsEtu}}" type="text" name="prenom" id="prenom" class="form-control" placeholder="John">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="dateNaissance">Date de naissance <span class="text-danger">*</span></label>
                                                    <input value="{{$item->dateNaisEtu}}" type="date" name="dateNaissance" id="dateNaissance" class="form-control" placeholder="01/01/2001">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="lieuNaissance">Lieu de naissance <span class="text-danger">*</span></label>
                                                    <input value="{{$item->lieuNaisEtu}}" type="text" name="lieuNaissance" id="lieuNaissance" class="form-control" placeholder="Nowhere">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="nationalite">Nationalité <span class="text-danger">*</span></label>
                                                    <select class="form-control form-select" name="nationalite" id="nationalite">
                                                        <option value="">Sélectionner une nationalité...</option>
                                                        @foreach (\App\Models\Nationalite::all() as $nationalite)
                                                        <option value="{{$nationalite->codeNationalite}}" @selected($nationalite->codeNationalite==$item->codeNationalite)>{{$nationalite->libelleNationalite}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="sexe">Sexe <span class="text-danger">*</span></label>
                                                    <select class="form-control form-select" name="sexe" id="sexe">
                                                        <option value="">Sélectionner un genre...</option>
                                                        <option value="M" @selected($item->sexeEtu=="M")>Masculin</option>
                                                        <option value="F" @selected($item->sexeEtu=="F")>Féminin</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                                                    <input value="{{$item->telEtu}}" type="text" name="telephone" id="telephone" class="form-control" placeholder="+229 90011234">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                                                    <input value="{{$item->emailEtu}}" type="email" name="email" id="email" class="form-control" placeholder="nom@example.com">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="typeEtudiant">Type d'étudiant <span class="text-danger">*</span></label>
                                                    <select class="form-control form-select" name="typeEtudiant" id="typeEtudiant">
                                                        <option value="">Sélectionner un type...</option>
                                                        @foreach (\App\Models\TypeEtudiant::all() as $te)
                                                        <option value="{{$te->codeTypeEtu}}" @selected($te->codeTypeEtu==$item->codeTypeEtu)>{{$te->libTypeEtu}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="observations">Observations</label>
                                                    <textarea name="observations" id="observations" class="form-control" placeholder="Aucune">{{$item->Observation}}</textarea>
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
                        <div class="modal fade" id="imgPm{{$loop->iteration}}" tabindex="-1" data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">Gérer photo de l'étudiant</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body px-4 pb-4">
                                        <form action="{{route('managePhotoEtudiant',$item->MATRICULE)}}" method="post" enctype="multipart/form-data">
                                            @csrf
                                            <div class="col-12">
                                                <label for="photo" class="form-label">Photo</label>
                                                <input type="file" name="photo" id="photo" class="form-control form-control-file">
                                            </div>
                                            <div class="mt-4 text-end">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                @isset($item->photo)
                                                <a href="./storage/assets/img/etudiants/{{$item->photo}}" download class="btn btn-dark">Télécharger l'image</a>
                                                <input type="submit" onclick="return confirm('Etes-vous sûr de vouloir supprimer ?')" value="Supprimer l'image" name="btn" class="btn btn-danger">
                                                <input type="submit" name="btn" class="btn btn-primary" value="Modifier">
                                                @else
                                                <input type="submit" name="btn" class="btn btn-primary" value="Enregistrer">
                                                @endisset
                                            </div>
                                        </form>
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
@section('js')
<script>
function downloadpdf(param) {
    // var elementDownload = document.querySelector(params1);
    var opt = {
        image:        { type: 'png', quality: 1 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
        html2canvas:{scrollX: 0,scrollY: 0}
    };
    $.ajax({
        url:'/carte-etudiant/'+param,
        success:function(response){
            html2pdf().set(opt).from(response).save('Carte_etudiant_'+param);
        }
    })
}
</script>
@endsection
@section('css')
<style>
.imhvr:hover
{
    opacity: 0.7;
}
</style>
@endsection
