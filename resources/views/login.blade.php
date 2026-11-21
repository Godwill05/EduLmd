<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>..::LMD Web Solutions | Licence - Master - Doctorat::..</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="{{ asset('favicon.ico') }}" rel="icon">
  <link href="{{ asset('/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Feuille de style unique — Tailwind compilé en local -->
  <link href="{{ asset('/assets/css/edulmd.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
</head>

<body>
  <main class="login-page">

    <!-- Panneau marque (desktop) -->
    <section class="login-brand" aria-hidden="true">
      <div class="brand-top">
        <span class="brand-logo"><img src="{{ asset('logo6.png') }}" alt=""></span>
        <span>
          <span class="brand-name block">EduLMD</span>
          <span class="brand-sub">LMD Web Solutions</span>
        </span>
      </div>

      <div class="brand-hero">
        <h1>La gestion académique LMD, simple et fiable.</h1>
        <p>Licence — Master — Doctorat : étudiants, inscriptions, notes, délibérations et documents officiels réunis dans une seule plateforme.</p>
        <ul class="brand-features">
          <li><span class="feat-icon"><i class="ri-team-line"></i></span> Suivi complet du parcours étudiant</li>
          <li><span class="feat-icon"><i class="ri-file-list-3-line"></i></span> Notes, relevés et délibérations</li>
          <li><span class="feat-icon"><i class="ri-file-text-line"></i></span> Documents officiels et impressions</li>
          <li><span class="feat-icon"><i class="ri-shield-check-line"></i></span> Accès sécurisé par rôles et permissions</li>
        </ul>
      </div>

      <div class="brand-foot">&copy; {{ now()->year }} LMD Web Solutions — Tous droits réservés.</div>
    </section>

    <!-- Panneau formulaire -->
    <section class="login-panel">
      <div class="login-card">
        <div class="login-logo"><img src="{{ asset('logo6.png') }}" alt="Logo EduLMD"></div>
        <h2>Bon retour</h2>
        <p class="login-subtitle">Connectez-vous à votre espace de travail</p>

        @if(request('mustAuthenticate') !== null)
        <div class="alert alert-warning flex items-center gap-2" id="mA" role="alert">
          <i class="ri-error-warning-fill"></i>
          <span class="flex-grow-1">Vous devez d'abord vous connecter !</span>
          <button type="button" class="btn btn-sm btn-light" onclick="document.querySelector('#mA').remove()">OK</button>
        </div>
        @endif

        @if (session()->has('errorCredentials'))
        <div class="alert alert-danger flex items-center gap-2" role="alert">
          <i class="ri-close-circle-fill"></i><span>{{ session('errorCredentials') }}</span>
        </div>
        @endif

        <form class="needs-validation" novalidate method="POST" action="{{ route('login.post') }}">
          @csrf
          <div class="mb-3">
            <label for="yourUsername" class="form-label">Nom d'utilisateur</label>
            <div class="field-wrap">
              <i class="ri-user-line field-icon"></i>
              <input type="text" name="email" class="form-control @error('email') is-invalid @enderror" id="yourUsername" required value="{{ old('email') }}" autocomplete="username" placeholder="Votre identifiant">
              <div class="invalid-feedback">Entrez votre nom d'utilisateur s'il vous plaît.</div>
            </div>
          </div>

          <div class="mb-3">
            <label for="yourPassword" class="form-label">Mot de passe</label>
            <div class="field-wrap">
              <i class="ri-lock-line field-icon"></i>
              <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="yourPassword" required autocomplete="current-password" placeholder="Votre mot de passe">
              <button type="button" class="toggle-password" id="togglePassword" aria-label="Afficher / masquer le mot de passe" title="Afficher / masquer">
                <i class="ri-eye-line"></i>
              </button>
              <div class="invalid-feedback">Entrez votre mot de passe s'il vous plaît !</div>
            </div>
          </div>

          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" value="true" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
          </div>

          <button class="btn btn-primary btn-login w-100" type="submit">
            <i class="ri-login-box-line me-1"></i>Se connecter
          </button>
        </form>

        <div class="login-foot">Copyright &copy; {{ now()->year }} | <strong>LMD Web Solutions</strong> | Tous droits réservés.</div>
      </div>
    </section>
  </main>

  <script>
    (function () {
      // Validation de formulaire
      var forms = document.querySelectorAll('.needs-validation');
      Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
          form.classList.add('was-validated');
        }, false);
      });

      // Afficher / masquer le mot de passe
      var toggle = document.getElementById('togglePassword');
      var input = document.getElementById('yourPassword');
      if (toggle && input) {
        toggle.addEventListener('click', function () {
          var show = input.type === 'password';
          input.type = show ? 'text' : 'password';
          toggle.innerHTML = '<i class="ri-eye' + (show ? '-off' : '') + '-line"></i>';
          input.focus();
        });
      }
    })();
  </script>
</body>

</html>
