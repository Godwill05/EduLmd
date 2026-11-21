@extends('layout')
@section('title')
Universités
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
            <h5 class="fw-bold">Nouvelle université</h5>
            <form action="{{ route('universites.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="logo" class="form-label">Logo</label>
                            <input type="file" name="logo" id="logo" class="form-control form-control-file" accept="image/*">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="code">Code Université <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" class="form-control" placeholder="ex:UAC">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nom">Université <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control" placeholder="Université d'Abomey-Calavi">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="recteur">Recteur <span class="text-danger">*</span></label>
                            <input type="text" name="recteur" id="recteur" class="form-control" placeholder="Jack DOE">
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
                            <label class="form-label" for="email">Email</label>
                            <input type="text" name="email" id="email" class="form-control" placeholder="nom@example.com">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="siteWeb">Site web</label>
                            <input type="text" name="siteWeb" id="siteWeb" class="form-control" placeholder="www.example.com">
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
                    <th>Code</th>
                    <th>Nom | Recteur</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>
                                @isset($item->logo)
                                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}"><img src="{{asset('/storage/assets/img/univ')}}/{{$item->logo}}" alt="" srcset="" style="width: 70px;height:70px" class="imhvr"></a>
                                @else
                                <div>Aucun logo<br><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#imgPm{{$loop->iteration}}">Ajouter ?</a></div>
                                @endisset
                            </td>
                            <td>{{$item->code}}</td>
                            <td>{{$item->nom}} | {{$item->recteur}}</td>
                            <td>{{$item->email}}</td>
                            <td>{{$item->telephone}}</td>
                            <td>
                                <form action="{{ route('universites.destroy', $item->id) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn"  data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"><i class=" ri-eye-line"></i></button>

                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$item->nom}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">A propos de l'université</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex justify-content-between align-content-center align-items-center">
                                            <span>Photo :</span>
                                            <img src="{{asset('./storage/assets/img/univ')}}/{{$item->logo}}" alt="" srcset="" style="width: 100px;height:100px">
                                        </div>
                                        <hr>
                                        Code : {{$item->code}}
                                        <hr>
                                        Nom : {{$item->nom}}
                                        <hr>
                                        Adresse postale : {{$item->adressePostale}}
                                        <hr>
                                        Email : {{$item->email}}
                                        <hr>
                                        Recteur : {{$item->recteur}}
                                        <hr>
                                        Téléphone : {{$item->telephone}}
                                        <hr>
                                        Site web : {{$item->siteWeb}}
                                        <hr>
                                        Observations : {{$item->observations}}
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
                                        <form action="{{route('manageLogo_univ',$item->id)}}" method="post" enctype="multipart/form-data">
                                            @csrf
                                            <div class="col-12">
                                                <label for="logo" class="form-label">Logo</label>
                                                <input type="file" name="logo" id="logo" class="form-control form-control-file">
                                            </div>
                                            <div class="mt-4 text-end">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                @isset($item->logo)
                                                <a href="./storage/assets/img/univ/{{$item->logo}}" download class="btn btn-dark">Télécharger l'image</a>
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
                                    <form method="POST" action="{{ route('universites.update', $item->id) }}" id="modalUpd{{$loop->iteration}}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row">
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="code">Code Université <span class="text-danger">*</span></label>
                                                    <input type="text" name="code" id="code" class="form-control" placeholder="ex:UAC" value="{{old('codeUniversite',$item->code)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="nom">Université <span class="text-danger">*</span></label>
                                                    <input type="text" name="nom" id="nom" class="form-control" placeholder="Université d'Abomey-Calavi" value="{{old('universite',$item->nom)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="adressePostale">Adresse postale</label>
                                                    <input type="text" name="adressePostale" id="adressePostale" class="form-control" placeholder="03 BP 001" value="{{old('adressePostale',$item->adressePostale)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="telephone">Téléphone<span class="text-danger">*</span></label>
                                                    <input type="text" name="telephone" id="telephone" class="form-control" placeholder="+229 90011234" value="{{old('telephone',$item->telephone)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="email">Email</label>
                                                    <input type="email" name="email" id="email" class="form-control" placeholder="nom@example.com" value="{{old('email',$item->email)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="siteWeb">Site web</label>
                                                    <input type="text" name="siteWeb" id="siteWeb" class="form-control" placeholder="www.example.com" value="{{old('siteWeb',$item->siteWeb)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="recteur">Recteur <span class="text-danger">*</span></label>
                                                    <input type="text" name="recteur" id="recteur" class="form-control" placeholder="Jack DOE" value="{{old('recteur',$item->recteur)}}">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12">
                                                <div class="form-group my-2">
                                                    <label class="form-label" for="observations">Observations</label>
                                                    <textarea name="observations" id="observations" class="form-control" placeholder="Aucune">{{old('observations',$item->observations)}}</textarea>
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
