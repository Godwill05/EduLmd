@extends('layout')
@section('title')
Fiche étudiant
@endsection

@section('css')
<style>
  .fe-hero {
    display:flex; align-items:center; gap:1.1rem; flex-wrap:wrap;
    background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem;
    padding:1.15rem 1.25rem; box-shadow:var(--ed-ombre); margin-bottom:1rem;
  }
  .fe-avatar {
    width:4.2rem; height:4.2rem; border-radius:1rem; flex:0 0 auto;
    display:grid; place-items:center; overflow:hidden;
    background:#FCF5F6; color:#822A40; font-size:1.4rem; font-weight:800;
    border:1px solid #EAD2D8;
  }
  .fe-avatar img { width:100%; height:100%; object-fit:cover; }

  /* Avatar modifiable : l'appareil photo apparaît au survol et au focus. */
  .fe-avatar-wrap { position:relative; flex:0 0 auto; }
  .fe-avatar-btn {
    padding:0; cursor:pointer; position:relative;
    transition:border-color .15s, box-shadow .15s;
  }
  .fe-avatar-btn:hover, .fe-avatar-btn:focus-visible {
    border-color:#822A40; box-shadow:0 0 0 3px rgba(130,42,64,.12);
  }
  .fe-avatar-btn > span:first-child { width:100%; height:100%; display:grid; place-items:center; }
  .fe-avatar-voile {
    position:absolute; inset:0; display:grid; place-items:center;
    background:rgba(11,11,12,.55); color:#fff; font-size:1.1rem;
    opacity:0; transition:opacity .15s;
  }
  .fe-avatar-btn:hover .fe-avatar-voile,
  .fe-avatar-btn:focus-visible .fe-avatar-voile { opacity:1; }
  .fe-avatar-btn.is-envoi .fe-avatar-voile { opacity:1; background:rgba(11,11,12,.7); }
  .fe-avatar-btn.is-envoi .fe-avatar-voile i { animation:edSpin .8s linear infinite; }
  .fe-nom { font-size:1.2rem; font-weight:800; letter-spacing:-.01em; line-height:1.2; }
  .fe-mat {
    font-family:ui-monospace,monospace; font-size:.8rem; color:#5C5F66;
    background:#F4F4F6; padding:.1rem .4rem; border-radius:.3rem;
  }
  .fe-meta { font-size:.78rem; color:#8A8D94; display:flex; gap:.7rem; flex-wrap:wrap; margin-top:.3rem; }

  .fe-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(9rem,1fr)); gap:.6rem; margin-bottom:1rem; }
  .fe-kpi { background:#fff; border:1px solid var(--ed-ligne); border-radius:.8rem; padding:.7rem .85rem; }
  .fe-kpi .v { font-size:1.15rem; font-weight:800; letter-spacing:-.02em; line-height:1.15; }
  .fe-kpi .l { font-size:.7rem; font-weight:600; color:#5C5F66; margin-top:.1rem; }
  .fe-kpi.ok .v { color:#15803D; }
  .fe-kpi.warn .v { color:#B45309; }
  .fe-kpi.bad .v { color:#B91C1C; }

  .fe-card { background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem; box-shadow:var(--ed-ombre); overflow:hidden; }
  .fe-head {
    display:flex; align-items:center; gap:.5rem; padding:.8rem 1.1rem;
    border-bottom:1px solid var(--ed-ligne); background:#FCFCFD;
  }
  .fe-head h2 { margin:0; font-size:.88rem; font-weight:700; }
  .fe-head .n {
    margin-left:auto; font-size:.7rem; font-weight:700; background:#F4F4F6;
    color:#5C5F66; padding:.1rem .45rem; border-radius:99px;
  }

  .fe-ins { padding:.85rem 1.1rem; border-bottom:1px solid #F2F2F5; }
  .fe-ins:last-child { border-bottom:0; }
  .fe-ins-t { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; font-size:.84rem; font-weight:700; }
  .fe-ins-t .badge {
    font-size:.66rem; font-weight:700; padding:.1rem .4rem; border-radius:99px;
    background:#F4F4F6; color:#5C5F66;
  }
  .fe-ins-t .badge.ok { background:#EAF7EE; color:#15803D; }
  .fe-ins-t .badge.warn { background:#FEF3E2; color:#B45309; }
  .fe-ins-nums { display:flex; gap:1.1rem; margin-top:.35rem; font-size:.76rem; color:#5C5F66; flex-wrap:wrap; }
  .fe-ins-nums b { color:#0B0B0B; font-variant-numeric:tabular-nums; }
  .fe-barre { height:.35rem; background:#F0F0F3; border-radius:99px; margin-top:.5rem; overflow:hidden; }
  .fe-barre > i { display:block; height:100%; background:#15803D; border-radius:99px; }
  .fe-barre > i.partiel { background:#B45309; }
  .fe-actions { margin-left:auto; }

  .fe-dl { display:grid; grid-template-columns:auto 1fr; gap:.35rem .9rem; padding:.9rem 1.1rem; font-size:.82rem; }
  .fe-dl dt { color:#8A8D94; font-weight:600; }
  .fe-dl dd { margin:0; color:#0B0B0B; }

  .fe-vide { padding:1.4rem 1.1rem; text-align:center; font-size:.82rem; color:#8A8D94; }

  .fe-statut {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.3rem .7rem; border-radius:99px; font-size:.76rem; font-weight:700;
  }
  .fe-statut.ok { background:#EAF7EE; color:#15803D; }
  .fe-statut.warn { background:#FEF3E2; color:#B45309; }
  .fe-statut i { font-size:.95rem; }
</style>
@endsection

@section('content')
<section class="px-6 pb-3 pt-5">

  <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
    <div>
      <h1 class="text-[1.35rem] font-extrabold tracking-tight text-encre leading-tight">Fiche étudiant</h1>
      <p class="text-[0.82rem] text-encre-muted mt-1">Identité, parcours et situation financière.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
      <a href="{{ route('etudiants.index') }}" class="ed-btn ed-btn-secondary ed-btn-sm">
        <i class="ri-arrow-left-line"></i>Tous les étudiants
      </a>
      @if ($inscriptionCourante)
        <a href="{{ route('engagement.imprimer', ['matricule' => $etudiant->MATRICULE, 'annee' => $inscriptionCourante->Id_Annee, 'filiere' => $inscriptionCourante->ID_FILIERE]) }}"
           target="_blank" rel="noopener"
           class="ed-btn ed-btn-secondary ed-btn-sm"
           title="Imprimer la fiche d'engagement (PDF)">
          <i class="ri-file-paper-2-line"></i>Fiche d'engagement
        </a>
        <button type="button" class="ed-btn ed-btn-secondary ed-btn-sm"
                data-ed-echeancier="{{ $etudiant->MATRICULE }}"
                data-annee="{{ $inscriptionCourante->Id_Annee }}"
                data-filiere="{{ $inscriptionCourante->ID_FILIERE }}"
                title="Gérer les prévisions de paiement">
          <i class="ri-calendar-check-line"></i>Prévisions
        </button>
      @endif
      @if (!$solde)
        <button type="button" class="ed-btn ed-btn-primary ed-btn-sm" data-ed-pay="{{ $etudiant->MATRICULE }}">
          <i class="ri-hand-coin-line"></i>Encaisser
        </button>
      @endif
    </div>
  </div>

  @if (session()->has('echec'))
    <div class="alert alert-danger mb-4">
      <i class="ri-alert-line me-1"></i>{{ session('echec') }}
    </div>
  @endif

  {{-- Identité --}}
  <div class="fe-hero">
    {{-- Avatar cliquable : l'envoi d'une nouvelle photo se fait directement
         depuis la fiche, avec un aperçu avant validation. --}}
    <div class="fe-avatar-wrap">
      <button type="button" class="fe-avatar fe-avatar-btn" id="avatarBouton"
              title="Changer la photo de l'étudiant"
              aria-label="Changer la photo de l'étudiant">
        <span id="avatarApercu">
          @if ($etudiant->photo)
            <img src="{{ asset('assets/img/etudiants/' . $etudiant->photo) }}" alt="">
          @else
            {{ $initiales ?: '?' }}
          @endif
        </span>
        <span class="fe-avatar-voile"><i class="ri-camera-line"></i></span>
      </button>

      <form id="formPhoto" method="POST"
            action="{{ route('etudiants.photo', ['matricule' => $etudiant->MATRICULE]) }}"
            enctype="multipart/form-data" class="d-none">
        @csrf
        <input type="file" name="photo" id="champPhoto"
               accept="image/jpeg,image/png,image/webp">
      </form>
    </div>
    <div>
      <div class="fe-nom">{{ $etudiant->nomEtu }} {{ $etudiant->prenomsEtu }}</div>
      <div class="flex items-center gap-2 mt-1">
        <span class="fe-mat">{{ $etudiant->MATRICULE }}</span>
        @if ($etudiant->sexeEtu)
          <span class="text-[0.75rem] text-encre-muted">
            {{ $etudiant->sexeEtu === 'F' ? 'Féminin' : 'Masculin' }}
          </span>
        @endif
      </div>
      <div class="fe-meta">
        @if ($etudiant->dateNaisEtu)
          <span><i class="ri-cake-2-line"></i> {{ $etudiant->dateNaisEtu }}</span>
        @endif
        @if ($etudiant->lieuNaisEtu)
          <span><i class="ri-map-pin-line"></i> {{ $etudiant->lieuNaisEtu }}</span>
        @endif
        @if ($etudiant->telEtu)
          <span><i class="ri-phone-line"></i> {{ $etudiant->telEtu }}</span>
        @endif
        @if ($etudiant->emailEtu)
          <span><i class="ri-mail-line"></i> {{ $etudiant->emailEtu }}</span>
        @endif
      </div>
    </div>
    <div class="ml-auto">
      <span class="fe-statut {{ $solde ? 'ok' : 'warn' }}">
        <i class="ri-{{ $solde ? 'checkbox-circle-line' : 'time-line' }}"></i>
        {{ $solde ? 'À jour' : 'Reste à payer' }}
      </span>
    </div>
  </div>

  {{-- Situation financière --}}
  <div class="fe-kpis">
    <div class="fe-kpi">
      <div class="v">{{ number_format($totalAttendu, 0, ',', ' ') }} F</div>
      <div class="l">Frais attendus</div>
    </div>
    <div class="fe-kpi ok">
      <div class="v">{{ number_format($totalPaye, 0, ',', ' ') }} F</div>
      <div class="l">Encaissé</div>
    </div>
    <div class="fe-kpi {{ $totalReste > 0 ? 'bad' : 'ok' }}">
      <div class="v">{{ number_format($totalReste, 0, ',', ' ') }} F</div>
      <div class="l">Reste à payer</div>
    </div>
    <div class="fe-kpi {{ $solde ? 'ok' : 'warn' }}">
      <div class="v">{{ $tauxGlobal }}%</div>
      <div class="l">Couverture</div>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-4">

    {{-- Inscriptions --}}
    <div class="lg:col-span-2 fe-card">
      <div class="fe-head">
        <i class="ri-graduation-cap-line text-[#822A40]"></i>
        <h2>Parcours</h2>
        <span class="n">{{ $inscriptions->count() }} {{ $inscriptions->count() > 1 ? 'inscriptions' : 'inscription' }}</span>
      </div>

      @forelse ($inscriptions as $ins)
        <div class="fe-ins">
          <div class="fe-ins-t">
            <span>{{ $ins->Libelle_AnEtude ?: $ins->ID_FILIERE }}</span>
            <span class="badge">{{ $ins->ANNEE_ACAD ?: '—' }}</span>
            @if ($ins->LibStatut)
              <span class="badge">{{ $ins->LibStatut }}</span>
            @endif
            <span class="badge {{ $ins->solde ? 'ok' : 'warn' }}">
              {{ $ins->solde ? 'Soldé' : 'Reste à payer' }}
            </span>
            <span class="fe-actions flex gap-1">
              <a class="ed-pay-btn" href="{{ route('paiements.index') }}?matricule={{ urlencode($etudiant->MATRICULE) }}">
                <i class="ri-file-list-3-line"></i>Paiements
              </a>
              <a class="ed-pay-btn"
                 href="{{ route('engagement.imprimer', ['matricule' => $etudiant->MATRICULE, 'annee' => $ins->Id_Annee, 'filiere' => $ins->ID_FILIERE]) }}"
                 target="_blank" rel="noopener" title="Fiche d'engagement de cette année">
                <i class="ri-file-paper-2-line"></i>Engagement
              </a>
              <button type="button" class="ed-pay-btn"
                      data-ed-echeancier="{{ $etudiant->MATRICULE }}"
                      data-annee="{{ $ins->Id_Annee }}"
                      data-filiere="{{ $ins->ID_FILIERE }}"
                      title="Prévisions de paiement de cette année">
                <i class="ri-calendar-check-line"></i>Prévisions
              </button>
            </span>
          </div>
          <div class="fe-ins-nums">
            <span>Attendu <b>{{ number_format($ins->attendu, 0, ',', ' ') }} F</b></span>
            <span>Encaissé <b>{{ number_format($ins->total_paye, 0, ',', ' ') }} F</b></span>
            <span><b>{{ $ins->nb_paiements }}</b> paiement(s)</span>
          </div>
          <div class="fe-barre">
            <i class="{{ $ins->taux < 100 ? 'partiel' : '' }}" style="width: {{ $ins->taux }}%"></i>
          </div>
        </div>
      @empty
        <div class="fe-vide">Aucune inscription enregistrée pour cet étudiant.</div>
      @endforelse
    </div>

    {{-- Derniers paiements --}}
    <div class="fe-card">
      <div class="fe-head">
        <i class="ri-hand-coin-line text-[#822A40]"></i>
        <h2>Derniers paiements</h2>
        <span class="n">{{ $derniersPaiements->count() }}</span>
      </div>

      @forelse ($derniersPaiements as $p)
        <div class="fe-ins">
          <div class="fe-ins-t">
            <span>{{ number_format((int) $p->montantPaye, 0, ',', ' ') }} F</span>
            <span class="badge">Tranche {{ $p->numeroTranche ?: '—' }}</span>
            @if ($p->Libelle_AnEtude)
              <span class="badge">{{ $p->Libelle_AnEtude }}</span>
            @endif
          </div>
          <div class="fe-ins-nums">
            <span>{{ $p->datePaiement ?: '—' }}</span>
            <span>{{ $p->modePaiement ?: '—' }}</span>
            <span>{{ $p->ANNEE_ACAD ?: '' }}</span>
          </div>
        </div>
      @empty
        <div class="fe-vide">Aucun paiement enregistré.</div>
      @endforelse
    </div>
  </div>

  {{-- Informations administratives --}}
  <div class="fe-card mt-4">
    <div class="fe-head">
      <i class="ri-file-user-line text-[#822A40]"></i>
      <h2>Informations administratives</h2>
    </div>
    <dl class="fe-dl">
      <dt>Matricule</dt><dd>{{ $etudiant->MATRICULE }}</dd>
      <dt>Nom</dt><dd>{{ $etudiant->nomEtu ?: '—' }}</dd>
      <dt>Prénoms</dt><dd>{{ $etudiant->prenomsEtu ?: '—' }}</dd>
      <dt>Date de naissance</dt><dd>{{ $etudiant->dateNaisEtu ?: '—' }}</dd>
      <dt>Lieu de naissance</dt><dd>{{ $etudiant->lieuNaisEtu ?: '—' }}</dd>
      <dt>Sexe</dt><dd>{{ $etudiant->sexeEtu ?: '—' }}</dd>
      <dt>Téléphone</dt><dd>{{ $etudiant->telEtu ?: '—' }}</dd>
      <dt>Email</dt><dd>{{ $etudiant->emailEtu ?: '—' }}</dd>
      <dt>Observations</dt><dd>{{ $etudiant->Observation ?: '—' }}</dd>
    </dl>
  </div>

</section>
@endsection

@section('js')
<script>
/*
 * Changement de la photo depuis la fiche étudiant.
 *
 *  - clic sur l'avatar → sélecteur de fichier ;
 *  - aperçu immédiat avant l'envoi (aucune requête tant que l'image n'est
 *    pas choisie) ;
 *  - envoi en Ajax, mise à jour de l'avatar sans recharger la page.
 */
(function () {
  'use strict';

  var bouton = document.getElementById('avatarBouton');
  var form = document.getElementById('formPhoto');
  var champ = document.getElementById('champPhoto');
  var apercu = document.getElementById('avatarApercu');

  if (!bouton || !form || !champ || !apercu) return;

  var csrf = document.querySelector('meta[name="csrf-token"]');
  csrf = csrf ? csrf.content : '';

  bouton.addEventListener('click', function () { champ.click(); });

  champ.addEventListener('change', function () {
    var fichier = champ.files && champ.files[0];
    if (!fichier) return;

    /* Contrôles côté client : message immédiat, le serveur revalide ensuite. */
    if (!/^image\/(jpeg|png|webp)$/.test(fichier.type)) {
      window.alert('Formats acceptés : JPEG, PNG ou WebP.');
      champ.value = '';
      return;
    }
    if (fichier.size > 4 * 1024 * 1024) {
      window.alert('L\'image ne doit pas dépasser 4 Mo.');
      champ.value = '';
      return;
    }

    var lecteur = new FileReader();
    lecteur.onload = function (e) {
      apercu.innerHTML = '<img src="' + e.target.result + '" alt="Aperçu">';
    };
    lecteur.readAsDataURL(fichier);

    envoyer(fichier);
  });

  function envoyer(fichier) {
    var donnees = new FormData(form);
    bouton.classList.add('is-envoi');

    fetch(form.action, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf
      },
      body: donnees,
      credentials: 'same-origin'
    }).then(function (r) {
      return r.text().then(function (t) {
        var d = null; try { d = t ? JSON.parse(t) : null; } catch (e) { d = null; }
        if (!r.ok) throw new Error((d && d.message) || ('HTTP ' + r.status));
        return d;
      });
    }).then(function (reponse) {
      apercu.innerHTML = '<img src="' + reponse.url + '" alt="">';
      afficherBandeau('Photo mise à jour.', 'ok');
    }).catch(function (erreur) {
      afficherBandeau('Échec de l\'envoi : ' + erreur.message, 'erreur');
    }).finally(function () {
      bouton.classList.remove('is-envoi');
      champ.value = '';
    });
  }

  function afficherBandeau(message, ton) {
    var el = document.createElement('div');
    el.className = 'ed-toast ' + (ton === 'erreur' ? 'ed-toast-erreur' : 'ed-toast-ok');
    el.innerHTML = '<i class="' + (ton === 'erreur' ? 'ri-error-warning-line' : 'ri-checkbox-circle-line') + '"></i>'
      + '<span></span>';
    el.querySelector('span').textContent = message;
    document.body.appendChild(el);
    setTimeout(function () { el.classList.add('is-out'); }, 2600);
    setTimeout(function () { el.remove(); }, 3100);
  }
})();
</script>
@endsection
