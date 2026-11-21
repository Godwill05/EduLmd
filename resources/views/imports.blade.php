@extends('layout')
@section('title')
Import de données
@endsection

@section('css')
<style>
  .imp-card { background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem; box-shadow:var(--ed-ombre); padding:1.25rem; }
  .imp-etapes { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.25rem; }
  .imp-etape {
    display:flex; align-items:center; gap:.45rem; font-size:.78rem; font-weight:600; color:#8A8D94;
    background:#F7F7F9; border:1px solid var(--ed-ligne); border-radius:999px; padding:.35rem .75rem;
  }
  .imp-etape .n {
    width:1.25rem; height:1.25rem; border-radius:999px; display:grid; place-items:center;
    background:#E6E6EA; color:#5C5F66; font-size:.68rem; font-weight:800;
  }
  .imp-etape.on { background:#FCF5F6; border-color:#C9A3AC; color:#822A40; }
  .imp-etape.on .n { background:#822A40; color:#fff; }

  .imp-zone {
    border:2px dashed #D9D3D6; border-radius:.8rem; padding:2rem 1rem; text-align:center;
    background:#FDFCFD; transition:border-color .15s, background .15s; cursor:pointer; display:block;
  }
  .imp-zone:hover, .imp-zone.drag { border-color:#822A40; background:#FCF5F6; }
  .imp-zone i { font-size:2rem; color:#822A40; display:block; margin-bottom:.4rem; }
  .imp-zone strong { display:block; font-size:.88rem; color:#0B0B0B; }
  .imp-zone span { font-size:.75rem; color:#8A8D94; }
  .imp-zone input[type="file"] { display:none; }

  .imp-fichier {
    display:none; align-items:center; gap:.6rem; margin-top:.7rem;
    padding:.5rem .7rem; border:1px solid #C9A3AC; background:#FCF5F6; border-radius:.6rem;
    font-size:.8rem; color:#6B2435; font-weight:600;
  }
  .imp-fichier.on { display:flex; }
  .imp-fichier i { font-size:1.1rem; }
  .imp-fichier .taille { margin-left:auto; font-weight:400; color:#8A8D94; font-size:.72rem; }

  .imp-colonnes { font-size:.75rem; color:#5C5F66; }
  .imp-colonnes code {
    background:#F4F4F6; padding:.05rem .3rem; border-radius:.25rem;
    font-size:.72rem; color:#822A40; font-weight:600;
  }
  .imp-colonnes li { margin-bottom:.2rem; }
</style>
@endsection

@section('content')
<section class="px-6 pb-3 pt-5">

  <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
    <div>
      <h1 class="text-[1.35rem] font-extrabold tracking-tight text-encre leading-tight">Import de données</h1>
      <p class="text-[0.82rem] text-encre-muted mt-1">
        Importez un fichier Excel ou CSV. Rien n'est enregistré avant votre validation.
      </p>
    </div>
    <a href="{{ route('home') }}" class="ed-btn ed-btn-secondary ed-btn-sm">
      <i class="ri-arrow-left-line"></i>Tableau de bord
    </a>
  </div>

  @if (session()->has('succes'))
    <div class="alert alert-success mb-4">
      <i class="ri-checkbox-circle-line me-1"></i>{{ session('succes') }}
    </div>
  @endif
  @if (session()->has('echec'))
    <div class="alert alert-danger mb-4">
      <i class="ri-alert-line me-1"></i>{{ session('echec') }}
    </div>
  @endif

  @if (session()->has('import.echecs') && count(session('import.echecs')))
    <div class="imp-card mb-4">
      <div class="dq-title mb-2" style="font-size:.9rem;font-weight:700;">Lignes en échec pendant l'écriture</div>
      <ul class="imp-colonnes">
        @foreach (session('import.echecs') as $e)
          <li><code>{{ $e }}</code></li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="imp-etapes">
    <div class="imp-etape on"><span class="n">1</span>Choisir le fichier</div>
    <div class="imp-etape"><span class="n">2</span>Prévisualiser le rapport</div>
    <div class="imp-etape"><span class="n">3</span>Confirmer l'import</div>
  </div>

  <div class="grid lg:grid-cols-3 gap-4">

    {{-- Formulaire --}}
    <div class="lg:col-span-2">
      <form method="POST" action="{{ route('imports.analyser') }}" enctype="multipart/form-data" class="imp-card">
        @csrf

        <label class="block text-[0.7rem] font-bold uppercase tracking-wider text-encre-faint mb-1">Type de données</label>
        <select name="type" id="impType" class="form-select form-select-sm mb-4" required>
          @foreach ($types as $cle => $libelle)
            <option value="{{ $cle }}">{{ $libelle }}</option>
          @endforeach
        </select>

        <label class="block text-[0.7rem] font-bold uppercase tracking-wider text-encre-faint mb-1">Fichier</label>
        <label class="imp-zone" id="impZone" for="impFichier">
          <i class="ri-upload-cloud-2-line"></i>
          <strong>Cliquez pour choisir un fichier</strong>
          <span>ou glissez-déposez — Excel (.xlsx, .xls) ou CSV, 5 Mo maximum</span>
          <input type="file" name="fichier" id="impFichier" accept=".csv,.txt,.xlsx,.xls" required>
        </label>

        <div class="imp-fichier" id="impFichierInfo">
          <i class="ri-file-excel-2-line"></i>
          <span class="nom"></span>
          <span class="taille"></span>
        </div>

        <div class="flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="ri-search-eye-line me-1"></i>Analyser le fichier
          </button>
          <a href="{{ route('imports.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
        </div>

        <p class="text-[0.75rem] text-encre-muted mt-3 mb-0">
          <i class="ri-information-line"></i>
          L'analyse ne modifie rien. Vous verrez le détail ligne par ligne avant de confirmer.
        </p>
      </form>
    </div>

    {{-- Format attendu --}}
    <div class="imp-card">
      <div class="dq-title mb-2">Format attendu</div>
      <p class="text-[0.78rem] text-encre-muted mb-3">
        La première ligne contient les en-têtes, les données commencent à la ligne 2.
      </p>

      <div class="imp-colonnes">
        <div class="font-semibold text-encre mb-1">Inscriptions + étudiants</div>
        <ol class="list-decimal ps-4 mb-3">
          <li><code>matricule</code></li>
          <li><code>nom</code></li>
          <li><code>prenoms</code></li>
          <li><code>date_naissance</code></li>
          <li><code>lieu_naissance</code></li>
          <li><code>sexe</code> (M/F)</li>
          <li><code>telephone</code></li>
          <li><code>email</code></li>
          <li><code>observation</code></li>
          <li><code>type_etudiant</code></li>
          <li><code>nationalite</code></li>
          <li><code>annee_academique</code></li>
          <li><code>annee_etude</code></li>
          <li><code>statut</code></li>
          <li><code>group_pdgq</code></li>
          <li><code>montant</code></li>
          <li><code>s_finance</code></li>
        </ol>

        <div class="font-semibold text-encre mb-1">Étudiants</div>
        <p class="mb-3">Colonnes 1 à 9 du format ci-dessus, sans les informations d'inscription.</p>

        <div class="font-semibold text-encre mb-1">Unités d'enseignement</div>
        <ol class="list-decimal ps-4">
          <li><code>code_ue</code></li>
          <li><code>libelle_ue</code></li>
        </ol>
      </div>
    </div>
  </div>

</section>
@endsection

@section('js')
<script>
(function () {
  var zone = document.getElementById('impZone');
  var input = document.getElementById('impFichier');
  var info = document.getElementById('impFichierInfo');
  if (!zone || !input) return;

  function afficher(f) {
    if (!f) { info.classList.remove('on'); return; }
    info.classList.add('on');
    info.querySelector('.nom').textContent = f.name;
    var ko = f.size / 1024;
    info.querySelector('.taille').textContent = ko > 1024
      ? (ko / 1024).toFixed(1) + ' Mo'
      : Math.round(ko) + ' Ko';
  }

  input.addEventListener('change', function () { afficher(input.files[0]); });

  ['dragenter', 'dragover'].forEach(function (ev) {
    zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('drag'); });
  });
  ['dragleave', 'drop'].forEach(function (ev) {
    zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('drag'); });
  });

  zone.addEventListener('drop', function (e) {
    var fichiers = e.dataTransfer.files;
    if (!fichiers.length) return;
    input.files = fichiers;
    afficher(fichiers[0]);
  });
})();
</script>
@endsection
