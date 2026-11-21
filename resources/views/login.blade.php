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

  <!-- Vendor CSS Files (100 % locaux) -->
  <link href="{{ asset('/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/css/edulmd-design.css') }}" rel="stylesheet">

  <style>
    .login-page {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1.05fr 1fr;
      background: var(--ed-bg);
    }
    .login-brand {
      position: relative;
      overflow: hidden;
      background: linear-gradient(150deg, #1e1b4b 0%, #312e81 45%, #4f46e5 100%);
      color: #fff;
      padding: 3rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .login-brand::after {
      content: "";
      position: absolute;
      right: -120px;
      bottom: -120px;
      width: 340px;
      height: 340px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.07);
    }
    .login-brand::before {
      content: "";
      position: absolute;
      left: -80px;
      top: -80px;
      width: 240px;
      height: 240px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
    }
    .login-brand .brand-top { position: relative; z-index: 1; display: flex; align-items: center; gap: 0.8rem; }
    .login-brand .brand-logo {
      width: 52px; height: 52px; border-radius: 14px; background: #fff;
      display: flex; align-items: center; justify-content: center; overflow: hidden;
      box-shadow: var(--ed-shadow-md);
    }
    .login-brand .brand-logo img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
    .login-brand .brand-name { font-weight: 800; font-size: 1.15rem; letter-spacing: -0.01em; }
    .login-brand .brand-sub { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.65); font-weight: 600; }
    .login-brand .brand-hero { position: relative; z-index: 1; max-width: 480px; }
    .login-brand .brand-hero h1 { color: #fff; font-size: 2rem; font-weight: 800; line-height: 1.2; margin-bottom: 0.9rem; }
    .login-brand .brand-hero p { color: rgba(255,255,255,0.78); font-size: 0.98rem; margin-bottom: 1.75rem; }
    .login-brand .brand-features { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.85rem; }
    .login-brand .brand-features li { display: flex; align-items: center; gap: 0.7rem; font-size: 0.9rem; color: rgba(255,255,255,0.88); }
    .login-brand .brand-features .feat-icon {
      width: 34px; height: 34px; border-radius: 10px; flex: 0 0 auto;
      background: rgba(255,255,255,0.14); display: inline-flex; align-items: center; justify-content: center;
      font-size: 1rem;
    }
    .login-brand .brand-foot { position: relative; z-index: 1; font-size: 0.78rem; color: rgba(255,255,255,0.55); }

    .login-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1.5rem;
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      background: var(--ed-surface);
      border: 1px solid var(--ed-border);
      border-radius: var(--ed-radius-xl);
      box-shadow: var(--ed-shadow-md);
      padding: 2rem;
    }
    .login-card .login-logo { display: flex; justify-content: center; margin-bottom: 1.25rem; }
    .login-card .login-logo img { height: 64px; object-fit: contain; }
    .login-card h2 { font-size: 1.3rem; font-weight: 800; text-align: center; margin-bottom: 0.3rem; }
    .login-card .login-subtitle { text-align: center; color: var(--ed-muted); font-size: 0.88rem; margin-bottom: 1.5rem; }
    .login-card .form-control { padding-left: 2.4rem; }
    .login-card #yourPassword { padding-right: 2.6rem; }
    .login-card .field-icon {
      position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%);
      color: var(--ed-faint); font-size: 1rem; pointer-events: none;
    }
    .login-card .field-wrap { position: relative; }
    .login-card .toggle-password {
      position: absolute; right: 0.55rem; top: 50%; transform: translateY(-50%);
      border: none; background: transparent; color: var(--ed-faint); padding: 0.3rem;
      border-radius: 6px; font-size: 1rem; line-height: 1;
    }
    .login-card .toggle-password:hover { color: var(--ed-primary); }
    .login-card .btn-login { padding: 0.7rem 1rem; font-size: 0.95rem; border-radius: var(--ed-radius); }
    .login-card .login-foot { text-align: center; font-size: 0.78rem; color: var(--ed-muted); margin-top: 1.25rem; }

    @media (max-width: 991.98px) {
      .login-page { grid-template-columns: 1fr; }
      .login-brand { display: none; }
    }
    @media (max-width: 575.98px) {
      .login-card { padding: 1.5rem 1.25rem; }
    }
  </style>
</head>

<body>
  <main class="login-page">

    <!-- Panneau marque (desktop) -->
    <section class="login-brand" aria-hidden="true">
      <div class="brand-top">
        <span class="brand-logo"><img src="{{ asset('logo6.png') }}" alt=""></span>
        <span>
          <span class="brand-name d-block">EduLMD</span>
          <span class="brand-sub">LMD Web Solutions</span>
        </span>
      </div>

      <div class="brand-hero">
        <h1>La gestion académique LMD, simple et fiable.</h1>
        <p>Licence — Master — Doctorat : étudiants, inscriptions, notes, délibérations et documents officiels réunis dans une seule plateforme.</p>
        <ul class="brand-features">
          <li><span class="feat-icon"><i class="bi bi-people"></i></span> Suivi complet du parcours étudiant</li>
          <li><span class="feat-icon"><i class="bi bi-clipboard2-data"></i></span> Notes, relevés et délibérations</li>
          <li><span class="feat-icon"><i class="bi bi-file-earmark-text"></i></span> Documents officiels et impressions</li>
          <li><span class="feat-icon"><i class="bi bi-shield-lock"></i></span> Accès sécurisé par rôles et permissions</li>
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
        <div class="alert alert-warning d-flex align-items-center gap-2" id="mA" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span class="flex-grow-1">Vous devez d'abord vous connecter !</span>
          <button type="button" class="btn btn-sm btn-light" onclick="document.querySelector('#mA').remove()">OK</button>
        </div>
        @endif

        @if (session()->has('errorCredentials'))
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
          <i class="bi bi-x-circle-fill"></i><span>{{ session('errorCredentials') }}</span>
        </div>
        @endif

        <form class="needs-validation" novalidate method="POST" action="{{ route('login.post') }}">
          @csrf
          <div class="mb-3">
            <label for="yourUsername" class="form-label">Nom d'utilisateur</label>
            <div class="field-wrap">
              <i class="bi bi-person field-icon"></i>
              <input type="text" name="email" class="form-control @error('email') is-invalid @enderror" id="yourUsername" required value="{{ old('email') }}" autocomplete="username" placeholder="Votre identifiant">
              <div class="invalid-feedback">Entrez votre nom d'utilisateur s'il vous plaît.</div>
            </div>
          </div>

          <div class="mb-3">
            <label for="yourPassword" class="form-label">Mot de passe</label>
            <div class="field-wrap">
              <i class="bi bi-lock field-icon"></i>
              <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="yourPassword" required autocomplete="current-password" placeholder="Votre mot de passe">
              <button type="button" class="toggle-password" id="togglePassword" aria-label="Afficher / masquer le mot de passe" title="Afficher / masquer">
                <i class="bi bi-eye"></i>
              </button>
              <div class="invalid-feedback">Entrez votre mot de passe s'il vous plaît !</div>
            </div>
          </div>

          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" value="true" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
          </div>

          <button class="btn btn-primary btn-login w-100" type="submit">
            <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
          </button>
        </form>

        <div class="login-foot">Copyright &copy; {{ now()->year }} | <strong>LMD Web Solutions</strong> | Tous droits réservés.</div>
      </div>
    </section>
  </main>

  <script src="{{ asset('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script>
    // Validation Bootstrap (même comportement que main.js)
    (function () {
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
          toggle.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
          input.focus();
        });
      }
    })();
  </script>
</body>

</html>
