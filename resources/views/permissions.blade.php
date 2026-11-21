@extends('layout')
@section('title')
Permissions
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
            <div class="card p-4">
                <h5>Nouvelle permission</h5>
                <form action="{{ route('permissions.store') }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="intitule" class="form-label">Intitulé</label>
                                <input type="text" name="intitule" id="intitule" class="form-control">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12 col-sm-12">
                            <div class="form-group">
                                <label for="type" class="form-label">Type</label>
                                <select type="text" name="type" id="type" class="form-control type">
                                    @foreach ($items as $item)
                                    <option value="">Sélectionner un type...</option>
                                    <option value="{{$item->type}}">{{$item->type}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="card p-4">
                <table class="table table-sm table-striped datatable">
                    <thead>
                        <th>Intitulé</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                        <tr>
                            <td>{{$item->name}}</td>
                            <td>{{$item->type ?? 'N/A'}}</td>
                            <td>
                                <form action="{{ route('permissions.destroy', $item->id) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn" title="Voir" type="button" data-bs-toggle="modal" data-bs-target="#permissionUsers{{$loop->iteration}}"><i class="bi bi-eye text-dark"></i></button>
                                    <button class="btn" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette permission ?')"><i class="bi bi-trash text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="permissionUsers{{$loop->iteration}}" tabindex="-1" data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">Qui peut "{{$item->name}}" ?</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body pb-5">
                                        @forelse (\App\Models\User::permission($item->name)->get() as $us)
                                        <li>{{$us->fullname()}}</li>
                                        @empty
                                        Personne pour le moment
                                        @endforelse
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
    $('.type').addClass('form-select');
    new TomSelect('.type',{
        create:true
    })
</script>
@endsection
