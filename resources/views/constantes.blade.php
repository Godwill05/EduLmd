@extends('layout')
@section('title')
Constantes
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
        <div class="col-lg-6">
            <div class="card p-2">
                <table class="table table-striped datatable">
                    <thead>
                        <th>Code</th>
                        <th>Catégorie</th>
                    </thead>
                    <tbody>
                        @foreach ($constantesCat as $cc)
                            <tr>
                                <td>{{$cc->Id_Cat_Ue}}</td>
                                <td>{{$cc->Lib_Cat_Ue}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">

            <div class="card p-2">
                <table class="table table-striped datatable">
                    <thead>
                        <th>Code</th>
                        <th>Importance</th>
                    </thead>
                    <tbody>
                        @foreach ($constantesImp as $ci)
                            <tr>
                                <td>{{$ci->idImportance}}</td>
                                <td>{{$ci->libelleImp}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</section>
@endsection
