<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  {{-- Page publique : un employeur la consulte sans compte. Elle ne doit
       donc rien révéler de plus que ce qui est déjà imprimé sur le
       diplôme qu'il tient en main. --}}
  <title>Vérification d'un document — IUSAA</title>
  <meta name="description" content="Vérifiez l'authenticité d'une attestation ou d'un certificat délivré par l'Institut Universitaire Saints Adrien et Anselme.">
  <meta name="robots" content="noindex, nofollow">

  <link href="{{ asset('favicon.ico') }}" rel="icon">
  <link href="{{ asset('/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">
  <link href="{{ asset('/assets/css/edulmd.css') }}" rel="stylesheet">
  <link href="{{ asset('/assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">

  <style>
    :root {
      --ed-bordeaux: #822a40;
      --ed-bordeaux-fonce: #6b2435;
      --ed-vert: #2e7d53;
      --ed-vert-clair: #eaf5ef;
      --ed-ligne: #e6e6ea;
      --ed-fond: #f6f6f8;
      --ed-noir: #0b0b0c;
      --ed-gris: #5c5c66;
      --ed-rouge: #b3261e;
      --ed-rouge-clair: #fdecea;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      background: var(--ed-fond);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: var(--ed-noir);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 32px 16px 56px;
    }

    .vf-entete {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
    }

    .vf-entete img { height: 44px; width: auto; }

    .vf-entete .nom {
      font-weight: 700;
      font-size: 15px;
      line-height: 1.25;
    }

    .vf-entete .sous {
      color: var(--ed-gris);
      font-size: 12.5px;
    }

    .vf-carte {
      width: 100%;
      max-width: 620px;
      background: #fff;
      border: 1px solid var(--ed-ligne);
      border-radius: 14px;
      box-shadow: 0 8px 28px rgba(11, 11, 12, .06);
      overflow: hidden;
    }

    .vf-bandeau {
      padding: 22px 26px;
      display: flex;
      align-items: center;
      gap: 14px;
      border-bottom: 1px solid var(--ed-ligne);
    }

    .vf-bandeau .icone {
      width: 46px; height: 46px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      font-size: 24px;
      flex: 0 0 auto;
    }

    .vf-bandeau h1 {
      margin: 0;
      font-size: 19px;
      font-weight: 700;
      letter-spacing: -.01em;
    }

    .vf-bandeau p {
      margin: 3px 0 0;
      font-size: 13px;
      color: var(--ed-gris);
    }

    /* États : valide (vert) / révoqué (rouge) / inconnu (neutre) */
    .est-valide .bandeau { background: var(--ed-vert-clair); }
    .est-valide .icone { background: var(--ed-vert); color: #fff; }
    .est-valide h1 { color: var(--ed-vert); }

    .est-revoque .bandeau { background: var(--ed-rouge-clair); }
    .est-revoque .icone { background: var(--ed-rouge); color: #fff; }
    .est-revoque h1 { color: var(--ed-rouge); }

    .est-inconnu .bandeau { background: #f1f1f4; }
    .est-inconnu .icone { background: #8a8a95; color: #fff; }
    .est-inconnu h1 { color: #4a4a55; }

    .vf-corps { padding: 8px 26px 26px; }

    .vf-ligne {
      display: flex;
      gap: 16px;
      padding: 13px 0;
      border-bottom: 1px dashed var(--ed-ligne);
      font-size: 14px;
    }

    .vf-ligne:last-child { border-bottom: 0; }

    .vf-ligne .cle {
      flex: 0 0 44%;
      color: var(--ed-gris);
      font-weight: 500;
    }

    .vf-ligne .valeur {
      flex: 1;
      font-weight: 600;
      word-break: break-word;
    }

    .vf-ref {
      font-family: 'SFMono-Regular', Consolas, monospace;
      letter-spacing: .06em;
      background: #f4f4f7;
      border: 1px solid var(--ed-ligne);
      border-radius: 6px;
      padding: 2px 7px;
      font-size: 13px;
      display: inline-block;
    }

    .vf-alerte {
      margin: 16px 26px 0;
      padding: 12px 14px;
      border-radius: 9px;
      font-size: 13.5px;
      line-height: 1.5;
    }

    .vf-alerte.rouge { background: var(--ed-rouge-clair); border: 1px solid #f3c9c6; color: #7a1a15; }
    .vf-alerte.neutre { background: #f4f4f7; border: 1px solid var(--ed-ligne); color: #4a4a55; }

    .vf-recherche {
      margin-top: 22px;
      width: 100%;
      max-width: 620px;
    }

    .vf-recherche label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 7px;
      color: var(--ed-gris);
    }

    .vf-recherche .ligne {
      display: flex;
      gap: 9px;
    }

    .vf-recherche input {
      flex: 1;
      padding: 11px 13px;
      border: 1px solid var(--ed-ligne);
      border-radius: 9px;
      font-size: 14px;
      font-family: inherit;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .vf-recherche input:focus {
      outline: 2px solid var(--ed-bordeaux);
      outline-offset: 1px;
      border-color: var(--ed-bordeaux);
    }

    .vf-recherche button {
      padding: 11px 20px;
      background: var(--ed-bordeaux);
      color: #fff;
      border: 0;
      border-radius: 9px;
      font-size: 14px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: background .15s;
    }

    .vf-recherche button:hover { background: var(--ed-bordeaux-fonce); }

    .vf-pied {
      margin-top: 26px;
      font-size: 12px;
      color: var(--ed-gris);
      text-align: center;
      max-width: 620px;
      line-height: 1.6;
    }

    @media (max-width: 520px) {
      .vf-ligne { flex-direction: column; gap: 3px; }
      .vf-ligne .cle { flex: none; }
      .vf-bandeau { padding: 18px; }
      .vf-corps { padding: 4px 18px 20px; }
      .vf-alerte { margin: 14px 18px 0; }
      .vf-recherche .ligne { flex-direction: column; }
    }
  </style>
</head>

<body>

  @php
    $etat = ! $document ? 'est-inconnu' : ($document->estValide() ? 'est-valide' : 'est-revoque');
  @endphp

  <header class="vf-entete">
    <img src="{{ asset('logo6.png') }}" alt="">
    <div>
      <div class="nom">Institut Universitaire Saints Adrien et Anselme</div>
      <div class="sous">Vérification des documents officiels</div>
    </div>
  </header>

  <main class="vf-carte {{ $etat }}">

    <section class="vf-bandeau bandeau">
      <span class="icone">
        @if ($etat === 'est-valide')
          <i class="ri-shield-check-line"></i>
        @elseif ($etat === 'est-revoque')
          <i class="ri-shield-cross-line"></i>
        @else
          <i class="ri-question-line"></i>
        @endif
      </span>
      <div>
        <h1>
          @if ($etat === 'est-valide')
            Document authentique
          @elseif ($etat === 'est-revoque')
            Document révoqué
          @else
            Code inconnu
          @endif
        </h1>
        <p>
          @if ($etat === 'est-valide')
            Ce document a bien été délivré par l'établissement.
          @elseif ($etat === 'est-revoque')
            Ce document a été annulé par l'établissement.
          @else
            Aucun document ne correspond à ce code.
          @endif
        </p>
      </div>
    </section>

    @if ($document)
      <div class="vf-corps">
        <div class="vf-ligne">
          <span class="cle">Type de document</span>
          <span class="valeur">{{ $document->libelleType() }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Titulaire</span>
          <span class="valeur">{{ $document->nom_complet }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Cycle</span>
          <span class="valeur">{{ $document->cycle ?: '—' }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Mention</span>
          <span class="valeur">{{ $document->mention ?: '—' }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Spécialité / Option</span>
          <span class="valeur">{{ $document->specialite ?: '—' }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Session</span>
          <span class="valeur">{{ $document->session_etude ?: '—' }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Délivré le</span>
          <span class="valeur">{{ $document->date_delivrance ?: '—' }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">À</span>
          <span class="valeur">{{ $document->lieu_delivrance ?: '—' }}</span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Référence portée sur le document</span>
          <span class="valeur"><span class="vf-ref">{{ $document->reference ?: '—' }}</span></span>
        </div>
        <div class="vf-ligne">
          <span class="cle">Code de vérification</span>
          <span class="valeur"><span class="vf-ref">{{ $document->code }}</span></span>
        </div>
      </div>

      @if (! $document->estValide())
        <div class="vf-alerte rouge">
          <strong>Ne pas accepter ce document.</strong>
          Motif : {{ $document->motif_revocation ?: 'non précisé' }}.
          @if ($document->revoque_le)
            Révoqué le {{ $document->revoque_le->format('d/m/Y') }}.
          @endif
        </div>
      @endif
    @else
      <div class="vf-alerte neutre">
        <strong>Que faire ?</strong> Vérifiez que le code a été saisi correctement
        (14 caractères, par groupes de 4). S'il est exact et que cette page ne
        trouve rien, le document n'a pas été émis par l'établissement.
      </div>
    @endif

  </main>

  <form class="vf-recherche" method="POST" action="{{ route('verification.recherche') }}">
    @csrf
    <label for="code">Vérifier un autre document</label>
    <div class="ligne">
      <input id="code" name="code" type="text" inputmode="text" autocomplete="off"
             maxlength="30" placeholder="A7K2-9XQ4-B3M8"
             value="{{ old('code') }}">
      <button type="submit">Vérifier</button>
    </div>
    @error('code')
      <p style="color:#b3261e;font-size:13px;margin:7px 0 0;">{{ $message }}</p>
    @enderror
  </form>

  <p class="vf-pied">
    Cette page n'affiche que les informations déjà portées sur le document.<br>
    Pour toute question : contactez le service de la scolarité de l'établissement.
  </p>

</body>

</html>
