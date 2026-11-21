<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Session expirée</title>
<meta content="" name="description">
<meta content="" name="keywords">

<link href="{{ asset('favicon.ico') }}" rel="icon">
<link href="{{ asset('/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

<link href="{{ asset('/assets/css/edulmd.css') }}" rel="stylesheet">
<link href="{{ asset('/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
</head>

<body class="min-h-screen bg-canvas flex flex-col">

<main class="flex-1 flex items-center justify-center px-6 py-16">
  <div class="w-full max-w-xl text-center">

    <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2.5 mb-10" aria-label="EduLMD — Accueil">
      <span class="w-11 h-11 rounded-xl bg-bordeaux-50 border border-bordeaux-100 flex items-center justify-center overflow-hidden p-1">
        <img src="{{ asset('/logo.png') }}" alt="" class="w-full h-full object-contain">
      </span>
      <span class="text-left">
        <span class="block text-[1rem] font-extrabold tracking-tight text-encre leading-tight">EduLMD</span>
        <span class="block text-[0.6rem] font-bold uppercase tracking-[0.12em] text-bordeaux-700">LMD Web Solutions</span>
      </span>
    </a>

    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-bordeaux-50 border border-bordeaux-100 text-bordeaux-800 text-[0.72rem] font-bold uppercase tracking-wider mb-6">
      <i class="ri-error-warning-line"></i> Erreur 419
    </div>

    <div class="text-[5.5rem] sm:text-[7rem] font-extrabold leading-none tracking-tighter text-bordeaux-700 select-none">419</div>

    <h1 class="mt-4 text-[1.35rem] sm:text-[1.6rem] font-bold text-encre tracking-tight leading-snug">
      Votre session a expiré.
    </h1>

    <p class="mt-3 text-[0.92rem] text-encre-muted max-w-md mx-auto leading-relaxed">
      Pour votre sécurité, la page a expiré après une période d'inactivité. Rechargez la page puis reconnectez-vous.
    </p>

    <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
      <a href="{{ route('home') }}" class="ed-btn ed-btn-primary ed-btn-lg">
        <i class="ri-home-4-line"></i>Retour à l'accueil
      </a>
      <a href="javascript:history.back()" class="ed-btn ed-btn-secondary ed-btn-lg">
        <i class="ri-arrow-left-line"></i>Page précédente
      </a>
    </div>

    <p class="mt-12 text-[0.75rem] text-encre-faint">
      &copy; {{ now()->year }} LMD Web Solutions — Besoin d'aide ? Contactez votre administrateur.
    </p>
  </div>
</main>

</body>
</html>
