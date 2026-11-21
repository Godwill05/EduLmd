@extends('layout')
@section('title')
Profil
@endsection
@section('content')
<section class="section profile">
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success">
            {{session('succes')}}
        </div>
    </div>
    @endif
    <div class="row">
      <div class="col-xl-4">

        <div class="card">
          <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">

            <i class="bi bi-person-circle display-1"></i>
            {{-- <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle"> --}}
            <h2>{{Auth::user()->firstname}} {{Auth::user()->lastname}}</h2>
            {{-- <h3>{{Auth::user()->login}}</h3> --}}
          </div>
        </div>

      </div>

      <div class="col-xl-8">

        <div class="card">
          <div class="card-body pt-3">
            <!-- Bordered Tabs -->
            <ul class="nav nav-tabs nav-tabs-bordered">

              <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">Aperçu</button>
              </li>

              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Modifier le profil</button>
              </li>

              {{-- <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Settings</button>
              </li> --}}

              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Changer le mot de passe</button>
              </li>

            </ul>
            <div class="tab-content pt-2">

              <div class="tab-pane fade show active profile-overview" id="profile-overview">

                <h5 class="card-title">Mes informations</h5>

                <div class="row">
                  <div class="col-lg-3 col-md-4 label ">Nom Complet</div>
                  <div class="col-lg-9 col-md-8">{{Auth::user()->firstname}} {{Auth::user()->lastname}}</div>
                </div>

                <div class="row">
                  <div class="col-lg-3 col-md-4 label">Téléphone</div>
                  <div class="col-lg-9 col-md-8">{{Auth::user()->telephone}}</div>
                </div>

                <div class="row">
                  <div class="col-lg-3 col-md-4 label">Email</div>
                  <div class="col-lg-9 col-md-8">{{Auth::user()->email}}</div>
                </div>

              </div>

              <div class="tab-pane fade profile-edit pt-3" id="profile-edit">

                <!-- Profile Edit Form -->
                <form method="POST" action="{{ route('edit-profile') }}">
                    @csrf
                    @method('PATCH')

                  <div class="row mb-3">
                    <label for="lastname" class="col-md-4 col-lg-3 col-form-label">Nom</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="lastname" type="text" class="form-control" id="lastname" value="{{Auth::user()->lastname}}">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="firstname" class="col-md-4 col-lg-3 col-form-label">Prénom(s)</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="firstname" type="text" class="form-control" id="firstname" value="{{Auth::user()->firstname}}    ">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="telephone" class="col-md-4 col-lg-3 col-form-label">Téléphone</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="telephone" type="text" class="form-control" id="telephone" value="{{Auth::user()->telephone}}">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="Email" class="col-md-4 col-lg-3 col-form-label">Email</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="email" type="email" class="form-control" id="Email" value="{{Auth::user()->email}}">
                    </div>
                  </div>

                  <div class="text-center">
                    <button type="submit" class="btn-soft-primary mt-4" onclick="return confirm('Etes vous sûr de vouloir enregistrer les modifications ?')">Enregistrer les modifications</button>
                  </div>
                </form><!-- End Profile Edit Form -->

              </div>

              <div class="tab-pane fade pt-3" id="profile-settings">

                {{-- <!-- Settings Form -->
                <form>

                  <div class="row mb-3">
                    <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Email Notifications</label>
                    <div class="col-md-8 col-lg-9">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="changesMade" checked>
                        <label class="form-check-label" for="changesMade">
                          Changes made to your account
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="newProducts" checked>
                        <label class="form-check-label" for="newProducts">
                          Information on new products and services
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="proOffers">
                        <label class="form-check-label" for="proOffers">
                          Marketing and promo offers
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="securityNotify" checked disabled>
                        <label class="form-check-label" for="securityNotify">
                          Security alerts
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="text-center">
                    <button type="submit" class="btn btn-soft-primary">Save Changes</button>
                  </div>
                </form><!-- End settings Form --> --}}

              </div>

              <div class="tab-pane fade pt-3" id="profile-change-password">
                {{-- <!-- Change Password Form --> --}}
                <form method="POST" action="{{ route('change-password') }}" id="changePwd">
                    @csrf
                    @method('PATCH')

                  <div class="row mb-3">
                    <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Mot de passe actuel</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="password" type="password" class="form-control" id="currentPassword">
                      <div class="invalid-feedback">
                        Mot de passe invalide
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">Nouveau mot de passe</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="newpassword" type="password" class="form-control" id="newPassword">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="confirmpassword" class="col-md-4 col-lg-3 col-form-label">Confirmer le nouveau mot de passe</label>
                    <div class="col-md-8 col-lg-9">
                      <input name="confirmpassword" type="password" class="form-control" id="confirmpassword">
                      <div class="invalid-feedback">
                        Les mots de passe ne correspondent pas
                      </div>
                    </div>
                  </div>

                  <div class="text-center">
                    <button type="submit" class="btn-soft-primary" onclick="return confirm('Etes vous sûr de vouloir confirmer ?')">Changer le mot de passe</button>
                  </div>
                </form><!-- End Change Password Form -->

              </div>

            </div><!-- End Bordered Tabs -->

          </div>
        </div>

      </div>
    </div>
  </section>
@endsection
@section('js')
<script>
    $('#changePwd').on('submit',function(event){
        event.preventDefault();
        dataPost=$('#changePwd').serialize();
        $.ajax({
            url:"{{route('change-password')}}",
            type:'PATCH',
            data:dataPost,
            dataType:'json',
            success:
                function(response){
                    if(response.notMatch){
                        $('#currentPassword').addClass('is-invalid');
                        $('#newPassword').val('');
                        $('#confirmpassword').val('');
                    }
                    if (response.newNotConfirm) {
                        $('#currentPassword').addClass('is-valid');
                        $('#newPassword').addClass('is-invalid');
                        $('#confirmpassword').addClass('is-invalid');
                    }
                    if(response.redirect){
                        location.reload();
                    }
                }
        });
    });
</script>
@endsection
