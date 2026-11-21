@extends('layout')
@section('title')
Accueil
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6 col-sm-12 col-md-12">
                <a href="{{ route('etudiants.index') }}" class="">
                    <div class="card p-4">
                        <div class="d-flex justify-content-between">
                            <h4>Etudiants</h4>
                            <span class="fw-bold fs-4">{{\App\Models\Etudiant::count()}}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6 col-sm-12 col-md-12">
                <a href="{{ route('inscriptions.index') }}" class="">
                    <div class="card p-4">
                        <div class="d-flex justify-content-between">
                            <h4>Inscriptions</h4>
                            <span class="fw-bold fs-4">{{\App\Models\Inscription::count()}}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>
</section>
@endsection
