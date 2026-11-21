@extends('layout')
@section('title')
Gérer les permissions de {{$user->fullname()}}
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">
        <div class="row">
            <div class="card p-4">
                <div class="d-flex justify-content-between">
                    <h5>Définir comme administrateur</h5>
                    <div class="form-check form-check-reverse form-switch">
                        <input class="form-check-input" type="checkbox" id="checkAll">
                    </div>
                </div>
                <form action="{{ route('utilisateurs.permissions_post') }}" method="post">
                    <hr class="mx-n4">
                    @csrf
                    @foreach ($permissions as $permission)
                    <div class="d-flex justify-content-between">
                        {{$permission->name}}
                        <div class="form-check form-check-reverse form-switch">
                            <input class="form-check-input" name="permissions[{{$permission->id}}]" type="checkbox" @checked($user->hasPermissionTo($permission->name))>
                        </div>
                    </div>
                    @endforeach
                    <hr class="mx-n4">
                    <div class="mt-4 text-end">
                        <a href="{{route('utilisateurs.index')}}" class="btn btn-info">Retour</a>
                        @if ($user->hasAnyPermission($permissions->value('name')))
                        <input type="submit" name="retirer" class="btn btn-danger" value='Retirer tout' onclick="return confirm('Êtes-vous sûr de vouloir retirer toutes les permissions de {{$user->fullname()}} ?')">
                        @endif
                        <button type="submit" class="btn btn-primary">Sauvegarder les modifications</button>
                    </div>
                    <input type="hidden" name="user" value="{{$user->id}}">
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
@section('js')
<script>
    $('#checkAll').click(function () {
     $('input:checkbox').prop('checked', this.checked);
    });
</script>
@endsection
