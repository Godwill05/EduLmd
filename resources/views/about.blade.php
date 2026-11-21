@extends('layout')

@section("title")
À propos
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
      <div class="col-lg-6">
          <div class="p-4 card">
            <i class="fs-2 text-primary ri-phone-line"></i>
            <h3 class="fw-bolder">Téléphone</h3>
            <p>+229 97 93 35 67<br>+229 69 80 04 26<br>+229 62 40 49 07</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="p-4 card">
            <i class="fs-2 text-primary ri-mail-line"></i>
            <h3 class="fw-bolder">E-mail</h3>
            <p>jbsegla@gmail.com<br>darrylavlessi@gmail.com<br>mohamedzoldik@gmail.com</p>
          </div>
        </div>
    </div>
</div>
@endsection
