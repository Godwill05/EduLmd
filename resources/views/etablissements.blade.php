@extends('layout')
@section('title')
Etablissements
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
            <h5 class="fw-bold">Nouvel établissement</h5>
            <form action="{{ route('etablissements.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="logo" class="form-label">Logo</label>
                            <input type="file" name="logo" id="logo" class="form-control form-control-file" accept="image/*">
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="universite">Université de tutelle <span class="text-danger">*</span></label>
                            <select name="universite" id="universite" class="form-control form-select">
                                <option value="">Sélectionner une université...</option>
                                @foreach (\App\Models\Universite::all() as $universite)
                                <option value="{{$universite->id}}">{{$universite->nom}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nom">Ecole/UFR <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control" placeholder="Institut de Formation et de Recherche en Informatique">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="adressePostale">Adresse postale</label>
                            <input type="text" name="adressePostale" id="adressePostale" class="form-control" placeholder="03 BP 001">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" name="telephone" id="telephone" class="form-control" placeholder="+229 90011234">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="nom@example.com">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="siteWeb">Site web</label>
                            <input type="text" name="siteWeb" id="siteWeb" class="form-control" placeholder="www.example.com">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="directeur" class="form-label">Directeur <span class="text-danger">*</span></label>
                            <input type="text" name="directeur" id="directeur" class="form-control" placeholder="John DOE" required>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="directeurAdjoint" class="form-label">Directeur Adjoint <span class="text-danger">*</span></label>
                            <input type="text" name="directeurAdjoint" id="directeurAdjoint" class="form-control" placeholder="Jack DOE" required>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
                            <input type="text" name="ville" id="ville" class="form-control" placeholder="Cotonou">
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
                    <th>Logo</th>
                    <th>Université</th>
                    <th>Unité de Formation et de Recherche</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>
                                @isset($item->logo_ufr)
                                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}"><img src="{{asset('./storage/assets/img/ufr')}}/{{$item->logo_ufr}}" alt="" srcset="" style="width: 70px;height:70px" class="imhvr"></a>
                                @else
                                <div>Aucun logo<br><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}">Ajouter ?</a></div>
                                @endisset
                            </td>
                            <td>{{$item->universite->nom}}</td>
                            <td>{{$item->Ecol_Ufr}}</td>
                            <td>
                                <form action="{{ route('etablissements.destroy', $item->Id_Ufr) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"><i class=" ri-eye-line"></i></button>

                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$item->Ecol_Ufr}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">A propos de l'établissement</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex justify-content-between align-content-center align-items-center">
                                            <span>Photo :</span>
                                            @empty($item->logo_ufr)
                                            <div>Aucun logo<br><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}">Ajouter ?</a></div>
                                            @else
                                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}"><img src="{{asset('./storage/assets/img/ufr')}}/{{$item->logo_ufr}}" class="imhvr" alt="" srcset="" style="width: 100px;height:100px"></a>
                                            @endempty
                                        </div>
                                        <hr>
                                        Université de tutelle : {{$item->universite->nom}}
                                        <hr>
                                        Ecole/UFR : {{$item->Ecol_Ufr}}
                                        <hr>
                                        Adresse postale : {{$item->Adres_Post}}
                                        <hr>
                                        Email : {{$item->mail}}
                                        <hr>
                                        Directeur : {{$item->directeur}}
                                        <hr>
                                        Directeur Adjoint : {{$item->directeurAdjoint}}
                                        <hr>
                                        Ville : {{$item->ville}}
                                        <hr>
                                        Téléphone : {{$item->Tel}}
                                        <hr>
                                        Site web : {{$item->Site_web}}
                                        <hr>
                                        Observations : {{$item->obs}}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="imgPm{{$loop->iteration}}" tabindex="-1" data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">Gérer logo</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body px-4 pb-4">
                                        <form action="{{route('manageLogo_ufr',$item->Id_Ufr)}}" method="post" enctype="multipart/form-data">
                                            @csrf
                                            <div class="col-12">
                                                <label for="logo" class="form-label">Logo</label>
                                                <input type="file" name="logo" id="logo" class="form-control form-control-file">
                                            </div>
                                            <div class="mt-4 text-end">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                @isset($item->logo_ufr)
                                                <a href="./storage/assets/img/ufr/{{$item->logo_ufr}}" download class="btn btn-dark">Télécharger l'image</a>
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
                        <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier un domaine</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('etablissements.update', $item->Id_Ufr) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row">
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="universite">Université <span class="text-danger">*</span></label>
                                                    <select name="universite" id="universite" class="form-control form-select">
                                                        <option value="">Sélectionner une université...</option>
                                                        @foreach (\App\Models\Universite::all() as $universite)
                                                        <option value="{{$universite->id}}" @selected($item->Univ==$universite->id)>{{$universite->nom}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="nom">Ecole/UFR <span class="text-danger">*</span></label>
                                                    <input type="text" name="nom" id="nom" class="form-control" placeholder="Université d'Abomey-Calavi" value="{{old('nom',$item->Ecol_Ufr)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="adressePostale">Adresse postale <span class="text-danger">*</span></label>
                                                    <input type="text" name="adressePostale" id="adressePostale" class="form-control" placeholder="03 BP 001" value="{{old('adressePostale',$item->Adres_Post)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                                                    <input type="text" name="telephone" id="telephone" class="form-control" placeholder="+229 90011234" value="{{old('telephone',$item->Tel)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                                                    <input type="email" name="email" id="email" class="form-control" placeholder="nom@example.com" value="{{old('email',$item->mail)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="siteWeb">Site web <span class="text-danger">*</span></label>
                                                    <input type="text" name="siteWeb" id="siteWeb" class="form-control" placeholder="www.example.com" value="{{old('siteWeb',$item->Site_web)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label for="directeur" class="form-label">Directeur <span class="text-danger">*</span></label>
                                                    <input type="text" name="directeur" id="directeur" class="form-control" placeholder="John DOE" value="{{$item->directeur}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label for="directeurAdjoint" class="form-label">Directeur Adjoint <span class="text-danger">*</span></label>
                                                    <input type="text" name="directeurAdjoint" id="directeurAdjoint" class="form-control" placeholder="Jack DOE" value="{{$item->directeurAdjoint}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
                                                    <input type="text" name="ville" id="ville" class="form-control" placeholder="Cotonou" value="{{$item->ville}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="observations">Observations <span class="text-danger">*</span></label>
                                                    <textarea name="observations" id="observations" class="form-control" placeholder="Aucune">{{old('observations',$item->obs)}}</textarea>
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
@section('css')
<style>
.imhvr:hover
{
    opacity: 0.7;
}
</style>
@endsection
