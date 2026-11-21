@extends('layout')
@section('title', 'Gestion des notes')

@section('content')
<section class="section">
<div class="container-fluid">

  {{-- ─────────────────────────── En-tête ─────────────────────────── --}}
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div>
      <h1 class="ed-page-title mb-0">Gestion des notes</h1>
      <p class="ed-page-sub mb-0">
        Choisissez une évaluation, puis saisissez les notes sans quitter la page.
      </p>
    </div>
  </div>

  {{-- ────────────────────── Filtres (une seule ligne) ────────────────────── --}}
  <div class="ed-card mb-3">
    <form action="{{ route('afficherNotes') }}" method="POST" id="formFiltresNotes"
          data-sel-annee-etude="{{ request('anneeEtude') }}"
          data-sel-annee-acad="{{ request('anneeAcademique') }}"
          data-sel-semestre="{{ request('semestreEtude') }}"
          data-sel-ue="{{ request('ue') }}"
          data-sel-ecue="{{ request('ecue') }}">
      @csrf
      <div class="row g-3">
        <div class="col-lg-3 col-md-6">
          <label class="form-label" for="anneeEtude">Année d'étude</label>
          <select name="anneeEtude" id="anneeEtude" class="form-select">
            <option value="">Sélectionner…</option>
            @foreach ($anneesEtude as $annee)
              <option value="{{ $annee->Code_Annee_Etude }}"
                      @selected(request('anneeEtude') === $annee->Code_Annee_Etude)>
                {{ $annee->Libelle_AnEtude }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-lg-3 col-md-6">
          <label class="form-label" for="anneeAcademique">Année académique</label>
          <select name="anneeAcademique" id="anneeAcademique" class="form-select">
            <option value="">Sélectionner…</option>
            @foreach ($anneesAcademiques as $annee)
              <option value="{{ $annee->id_Annee_Acad }}"
                      @selected((string) request('anneeAcademique') === (string) $annee->id_Annee_Acad)>
                {{ $annee->ANNEE_ACAD }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-lg-2 col-md-6">
          <label class="form-label" for="semestreEtude">Semestre</label>
          <select name="semestreEtude" id="semestreEtude" class="form-select" disabled>
            <option value="">Sélectionner…</option>
          </select>
          <div class="form-text" id="requiredAnEtd">Choisissez une année d'étude.</div>
          <div class="form-text text-danger d-none" id="erreurSemestre"></div>
        </div>

        <div class="col-lg-2 col-md-6">
          <label class="form-label" for="ue">UE</label>
          <select name="ue" id="ue" class="form-select" disabled>
            <option value="">Sélectionner…</option>
          </select>
          <div class="form-text" id="requiredSemAnac">Choisissez le semestre et l'année.</div>
          <div class="form-text text-danger d-none" id="erreurUe"></div>
        </div>

        <div class="col-lg-2 col-md-6">
          <label class="form-label" for="ecue">ECUE</label>
          <select name="ecue" id="ecue" class="form-select" disabled>
            <option value="">Sélectionner…</option>
          </select>
          <div class="form-text" id="requiredUe">Choisissez d'abord l'UE.</div>
          <div class="form-text text-danger d-none" id="erreurEcue"></div>
        </div>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
        <button type="submit" class="btn btn-primary">
          <i class="ri-filter-3-line me-1"></i>Afficher les évaluations
        </button>
        <a href="{{ route('afficherNotes') }}" class="btn btn-outline-secondary">
          <i class="ri-refresh-line me-1"></i>Réinitialiser
        </a>
        <span class="form-text mb-0 ms-auto d-none d-md-inline">
          <i class="ri-flashlight-line"></i> La liste se recharge automatiquement dès que les quatre filtres sont choisis.
        </span>
      </div>
    </form>
  </div>

  {{-- ─────────────────── Deux colonnes : évaluations + console ─────────────────── --}}
  <div class="row g-3">

    {{-- Liste des évaluations --}}
    <div class="col-xl-5 col-lg-5">
      <div class="ed-card h-100 d-flex flex-column">
        <div class="ed-card-head">
          <h2 class="ed-card-title mb-0">
            <i class="ri-calendar-event-line me-1"></i>Évaluations
            @isset($evaluations)
              <span class="ed-chip ms-1">{{ $evaluations->count() }}</span>
            @endisset
          </h2>
        </div>

        <div class="ed-eval-list" id="listeEvaluations">
          @isset($evaluations)
            @forelse ($evaluations as $evaluation)
              <button type="button" class="ed-eval-item"
                      data-eval="{{ $evaluation->numeroEval }}"
                      data-annee="{{ $evaluation->an }}"
                      data-etude="{{ $evaluation->Code_Annee_Etude }}">
                <span class="ed-eval-date">
                  <strong>{{ \Carbon\Carbon::parse($evaluation->dateEval)->format('d/m') }}</strong>
                  <span>{{ \Carbon\Carbon::parse($evaluation->heureDebutEval)->format('H:i') }}</span>
                </span>
                <span class="ed-eval-main">
                  <span class="ed-eval-ecue">{{ $evaluation->libelleEcu }}</span>
                  <span class="ed-eval-meta">
                    {{ $evaluation->libelleUe }} · {{ $evaluation->Nom_Classe }}
                    · {{ $evaluation->libTypEval }}
                  </span>
                  <span class="ed-eval-meta">
                    {{ $evaluation->nomProf }} {{ $evaluation->prenomsProf }}
                  </span>
                </span>
                <span class="ed-eval-go"><i class="ri-arrow-right-s-line"></i></span>
              </button>
            @empty
              <div class="ed-eval-empty">
                <i class="ri-inbox-line"></i>
                <p>
                  @if (request()->isMethod('post'))
                    Aucune évaluation pour ces filtres.
                  @else
                    Choisissez les quatre filtres pour afficher les évaluations.
                  @endif
                </p>
              </div>
            @endforelse
          @else
            <div class="ed-eval-empty">
              <i class="ri-inbox-line"></i>
              <p>Choisissez les quatre filtres pour afficher les évaluations.</p>
            </div>
          @endisset
        </div>
      </div>
    </div>

    {{-- Console de saisie --}}
    <div class="col-xl-7 col-lg-7">
      <div class="ed-card h-100 d-flex flex-column" id="consoleNotes">
        <div class="ed-card-head">
          <h2 class="ed-card-title mb-0">
            <i class="ri-edit-2-line me-1"></i>Saisie des notes
          </h2>
          <span class="ed-chip ed-chip-muted" id="consoleEtat">aucune évaluation</span>
        </div>

        <div id="consoleCorps" class="ed-console-body">
          <div class="ed-eval-empty">
            <i class="ri-cursor-line"></i>
            <p>Sélectionnez une évaluation à gauche pour commencer la saisie.</p>
            <span class="ed-cmdk-tip">Les notes déjà enregistrées apparaîtront pré-remplies.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</section>
@endsection

@section('js')
<script>
/*
 * Gestion des notes — console de saisie.
 *
 * Principes :
 *  - la liste des évaluations se recharge toute seule quand les quatre
 *    filtres sont complets (plus besoin de cliquer sur « Afficher ») ;
 *  - cliquer une évaluation charge la console de saisie en Ajax, sans
 *    quitter la page ni rouvrir une modale ;
 *  - les notes partent en une requête, avec un retour clair ;
 *  - les filtres restent sélectionnés après un rechargement.
 */
(function () {
  'use strict';

  var form     = document.getElementById('formFiltresNotes');
  var selEtude = document.getElementById('anneeEtude');
  var selAcad  = document.getElementById('anneeAcademique');
  var selSem   = document.getElementById('semestreEtude');
  var selUe    = document.getElementById('ue');
  var selEcue  = document.getElementById('ecue');
  var corps    = document.getElementById('consoleCorps');
  var etat     = document.getElementById('consoleEtat');

  if (!form || !selEtude || !corps) return;

  var csrf = document.querySelector('meta[name="csrf-token"]');
  csrf = csrf ? csrf.content : '';

  var memorise = {
    etude: form.dataset.selAnneeEtude || '',
    acad:  form.dataset.selAnneeAcad  || '',
    sem:   form.dataset.selSemestre   || '',
    ue:    form.dataset.selUe         || '',
    ecue:  form.dataset.selEcue       || ''
  };

  var evaluationCourante = null;

  /* ───────────────────────── Utilitaires ───────────────────────── */

  function esc(valeur) {
    return String(valeur == null ? '' : valeur)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function vider(select, texte) {
    select.innerHTML = '';
    var o = document.createElement('option');
    o.value = '';
    o.textContent = texte || 'Sélectionner…';
    select.appendChild(o);
  }

  function remplir(select, items, valeurCourante) {
    items.forEach(function (element) {
      var o = document.createElement('option');
      o.value = element.valeur;
      o.textContent = element.texte;
      select.appendChild(o);
    });
    if (valeurCourante) select.value = String(valeurCourante);
  }

  function signaler(id, message) {
    var el = document.getElementById(id);
    if (!el) return;
    if (!message) { el.classList.add('d-none'); el.textContent = ''; return; }
    el.textContent = message;
    el.classList.remove('d-none');
  }

  /** Appel JSON. L'en-tête X-Requested-With est exigé par /api/ue et /api/ecue. */
  function demander(url, options) {
    var opt = options || {};
    opt.headers = Object.assign({
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrf
    }, opt.headers || {});
    opt.credentials = 'same-origin';
    return fetch(url, opt).then(function (reponse) {
      return reponse.text().then(function (texte) {
        var data = null;
        try { data = texte ? JSON.parse(texte) : null; } catch (e) { data = null; }
        if (!reponse.ok) {
          var message = data && data.message ? data.message : ('HTTP ' + reponse.status);
          throw new Error(message);
        }
        return data;
      });
    });
  }

  function toast(message, ton) {
    var el = document.createElement('div');
    el.className = 'ed-toast ' + (ton === 'erreur' ? 'ed-toast-erreur' : 'ed-toast-ok');
    el.innerHTML = '<i class="' + (ton === 'erreur' ? 'ri-error-warning-line' : 'ri-checkbox-circle-line') + '"></i>'
      + '<span>' + esc(message) + '</span>';
    document.body.appendChild(el);
    setTimeout(function () { el.classList.add('is-out'); }, 2600);
    setTimeout(function () { el.remove(); }, 3100);
  }

  /* ───────────────────────── Cascade des filtres ───────────────────────── */

  function chargerSemestres(codeAnneeEtude, valeurAChoisir) {
    vider(selSem, 'Sélectionner…');
    signaler('erreurSemestre', '');
    if (!codeAnneeEtude) { selSem.disabled = true; return Promise.resolve(); }
    return demander('/api/semestre-etude/' + encodeURIComponent(codeAnneeEtude))
      .then(function (liste) {
        selSem.disabled = false;
        remplir(selSem, liste.map(function (e) {
          return { valeur: e.id_S_Gr_Spe, texte: e.Nom_Classe };
        }), valeurAChoisir);
        if (!liste.length) signaler('erreurSemestre', 'Aucun semestre pour cette année.');
      })
      .catch(function (e) { signaler('erreurSemestre', 'Chargement impossible (' + e.message + ').'); });
  }

  function chargerUes(idAnneeAcad, idSemestre, valeurAChoisir) {
    vider(selUe, 'Sélectionner…');
    signaler('erreurUe', '');
    if (!idAnneeAcad || !idSemestre) { selUe.disabled = true; return Promise.resolve(); }
    return demander('/api/ue/' + encodeURIComponent(idAnneeAcad) + '/' + encodeURIComponent(idSemestre))
      .then(function (liste) {
        selUe.disabled = false;
        remplir(selUe, liste.map(function (e) {
          return { valeur: e.Id_Attr_ue_Sem, texte: e.libelleUe };
        }), valeurAChoisir);
        if (!liste.length) signaler('erreurUe', 'Aucune UE pour ce semestre.');
      })
      .catch(function (e) { signaler('erreurUe', 'Chargement impossible (' + e.message + ').'); });
  }

  function chargerEcues(idAttrUeSem, valeurAChoisir) {
    vider(selEcue, 'Sélectionner…');
    signaler('erreurEcue', '');
    if (!idAttrUeSem) { selEcue.disabled = true; return Promise.resolve(); }
    return demander('/api/ecue/' + encodeURIComponent(idAttrUeSem))
      .then(function (liste) {
        selEcue.disabled = false;
        remplir(selEcue, liste.map(function (e) {
          return { valeur: e.ecue, texte: e.libelle };
        }), valeurAChoisir);
        if (!liste.length) signaler('erreurEcue', 'Aucune ECUE pour cette UE.');
      })
      .catch(function (e) { signaler('erreurEcue', 'Chargement impossible (' + e.message + ').'); });
  }

  /* ─────────────── Rechargement automatique des évaluations ─────────────── */

  var minuteur = null;
  var dernierChargement = '';

  /**
   * Soumet les filtres dès qu'ils sont complets, sans attendre le bouton.
   * Le formulaire reste un vrai POST : l'URL et l'historique sont corrects,
   * et l'utilisateur peut mettre en favori un état filtré.
   */
  function rechargerSiComplet() {
    var pret = selEtude.value && selAcad.value && selSem.value && selUe.value && selEcue.value;

    /* Le ECUE se charge en dernier : on attend qu'il ait une valeur choisie. */
    if (!pret) return;

    var signature = [selEtude.value, selAcad.value, selSem.value, selUe.value, selEcue.value].join('|');
    if (signature === dernierChargement) return;   /* déjà affiché */
    dernierChargement = signature;

    window.clearTimeout(minuteur);
    minuteur = window.setTimeout(function () { form.requestSubmit(); }, 250);
  }

  selEtude.addEventListener('change', function () {
    selUe.disabled = true; selEcue.disabled = true;
    vider(selUe, 'Sélectionner…'); vider(selEcue, 'Sélectionner…');
    chargerSemestres(this.value, '');
  });

  selAcad.addEventListener('change', function () {
    if (!selSem.value) { signaler('erreurUe', 'Choisissez d\'abord le semestre.'); return; }
    selEcue.disabled = true; vider(selEcue, 'Sélectionner…');
    chargerUes(this.value, selSem.value, '').then(rechargerSiComplet);
  });

  selSem.addEventListener('change', function () {
    if (!selAcad.value) { signaler('erreurUe', 'Choisissez d\'abord l\'année académique.'); return; }
    selEcue.disabled = true; vider(selEcue, 'Sélectionner…');
    chargerUes(selAcad.value, this.value, '').then(rechargerSiComplet);
  });

  selUe.addEventListener('change', function () {
    chargerEcues(this.value, '').then(rechargerSiComplet);
  });

  selEcue.addEventListener('change', rechargerSiComplet);

  /* ───────────────────────── Restauration au chargement ───────────────────────── */

  if (memorise.etude) {
    chargerSemestres(memorise.etude, memorise.sem).then(function () {
      if (!memorise.acad || !memorise.sem) return;
      return chargerUes(memorise.acad, memorise.sem, memorise.ue).then(function () {
        if (!memorise.ue) return;
        return chargerEcues(memorise.ue, memorise.ecue);
      });
    });
  }

  /* ───────────────────────── Console de saisie ───────────────────────── */

  function ouvrirConsole(bouton) {
    var params = new URLSearchParams({
      id_eval: bouton.dataset.eval,
      id_annee: bouton.dataset.annee,
      id_an_etude: bouton.dataset.etude
    });

    document.querySelectorAll('.ed-eval-item.is-open').forEach(function (el) {
      el.classList.remove('is-open');
    });
    bouton.classList.add('is-open');

    etat.textContent = 'chargement…';
    etat.className = 'ed-chip ed-chip-muted';
    corps.innerHTML = '<div class="ed-eval-empty"><i class="ri-loader-4-line ed-spin"></i><p>Chargement des étudiants…</p></div>';

    demander('{{ route('notes.console') }}?' + params.toString())
      .then(afficherConsole)
      .catch(function (e) {
        etat.textContent = 'erreur';
        etat.className = 'ed-chip ed-chip-erreur';
        corps.innerHTML = '<div class="ed-eval-empty"><i class="ri-error-warning-line"></i>'
          + '<p>' + esc(e.message) + '</p></div>';
      });
  }

  function afficherConsole(data) {
    evaluationCourante = data.evaluation;

    var stats = data.statistiques;
    etat.textContent = stats.saisies + ' / ' + stats.total + ' notes saisies';
    etat.className = 'ed-chip' + (stats.restantes === 0 ? '' : ' ed-chip-muted');

    var entete = ''
      + '<div class="ed-console-head">'
      +   '<div>'
      +     '<div class="ed-console-ecue">' + esc(data.evaluation.libelle || data.evaluation.ecue) + '</div>'
      +     '<div class="ed-console-meta">'
      +       esc(data.evaluation.type || '') + ' · ' + esc(data.anneeEtude)
      +       ' · ' + esc(data.anneeAcademique)
      +     '</div>'
      +   '</div>'
      +   '<div class="ed-console-stats">'
      +     '<span><strong id="statSaisies">' + stats.saisies + '</strong> saisies</span>'
      +     '<span><strong id="statRestantes">' + stats.restantes + '</strong> restantes</span>'
      +     '<span><strong id="statMoyenne">' + (stats.moyenne == null ? '—' : stats.moyenne) + '</strong> moyenne</span>'
      +   '</div>'
      + '</div>';

    if (!data.etudiants.length) {
      corps.innerHTML = entete
        + '<div class="ed-eval-empty"><i class="ri-user-unfollow-line"></i>'
        + '<p>Aucun étudiant inscrit à cette année d\'étude pour cette année académique.</p></div>';
      return;
    }

    var lignes = data.etudiants.map(function (e, i) {
      return ''
        + '<tr>'
        +   '<td class="ed-num">' + (i + 1) + '</td>'
        +   '<td><span class="ed-mat">' + esc(e.matricule) + '</span></td>'
        +   '<td>' + esc(e.nom) + ' ' + esc(e.prenoms) + '</td>'
        +   '<td class="ed-note-cell">'
        +     '<input type="number" class="form-control form-control-sm ed-note-input"'
        +       ' name="' + esc(e.matricule) + '"'
        +       ' min="0" max="20" step="0.01" inputmode="decimal"'
        +       ' value="' + (e.note == null ? '' : esc(e.note)) + '">'
        +   '</td>'
        +   '<td class="ed-note-etat" data-etat="' + esc(e.matricule) + '">'
        +     (e.note == null ? '<span class="ed-dot-muted"></span>' : '<span class="ed-dot-ok"></span>')
        +   '</td>'
        + '</tr>';
    }).join('');

    corps.innerHTML = entete
      + '<form id="formNotes" class="ed-console-form">'
      +   '<input type="hidden" name="numeroEvaluation" value="' + esc(data.evaluation.numero) + '">'
      +   '<div class="ed-console-scroll">'
      +     '<table class="table table-sm align-middle ed-notes-table">'
      +       '<thead><tr>'
      +         '<th style="width:2.5rem">#</th><th>Matricule</th><th>Étudiant</th>'
      +         '<th style="width:7rem">Note / 20</th><th style="width:2.5rem"></th>'
      +       '</tr></thead>'
      +       '<tbody>' + lignes + '</tbody>'
      +     '</table>'
      +   '</div>'
      +   '<div class="ed-console-actions">'
      +     '<span class="ed-console-hint">Astuce : <kbd>Entrée</kbd> passe au champ suivant.</span>'
      +     '<button type="submit" class="btn btn-primary"><i class="ri-save-3-line me-1"></i>Enregistrer</button>'
      +   '</div>'
      + '</form>';

    brancherConsole();
  }

  function brancherConsole() {
    var formNotes = document.getElementById('formNotes');
    if (!formNotes) return;

    /* Entrée = champ suivant : la saisie au pavé numérique devient continue. */
    formNotes.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var champs = Array.prototype.slice.call(formNotes.querySelectorAll('.ed-note-input'));
      var index = champs.indexOf(document.activeElement);
      if (index === -1) return;
      e.preventDefault();
      if (champs[index + 1]) { champs[index + 1].focus(); champs[index + 1].select(); }
      else formNotes.requestSubmit();
    });

    /* Validation immédiate : on signale une note hors 0–20 au lieu de la
       laisser partir et d'être ignorée silencieusement. */
    formNotes.addEventListener('input', function (e) {
      var champ = e.target.closest('.ed-note-input');
      if (!champ) return;
      var valeur = champ.value.trim();
      var invalide = valeur !== '' && (!isFinite(Number(valeur)) || Number(valeur) < 0 || Number(valeur) > 20);
      champ.classList.toggle('is-invalid', invalide);
    });

    formNotes.addEventListener('submit', function (e) {
      e.preventDefault();
      var bouton = formNotes.querySelector('button[type="submit"]');
      bouton.disabled = true;
      var texteInitial = bouton.innerHTML;
      bouton.innerHTML = '<i class="ri-loader-4-line ed-spin me-1"></i>Enregistrement…';

      demander('{{ route('notes.insererNotes') }}', {
        method: 'POST',
        body: new URLSearchParams(new FormData(formNotes))
      }).then(function (reponse) {
        toast(reponse.message || 'Notes enregistrées.', 'ok');
        rafraichirConsole();
      }).catch(function (erreur) {
        toast(erreur.message, 'erreur');
      }).finally(function () {
        bouton.disabled = false;
        bouton.innerHTML = texteInitial;
      });
    });
  }

  /** Recharge la console pour actualiser les compteurs et les notes en base. */
  function rafraichirConsole() {
    var bouton = document.querySelector('.ed-eval-item.is-open');
    if (bouton) ouvrirConsole(bouton);
  }

  document.addEventListener('click', function (e) {
    var bouton = e.target.closest('.ed-eval-item');
    if (bouton) ouvrirConsole(bouton);
  });

  /* Le bouton « Afficher » reste utile, mais on prévient la double soumission. */
  form.addEventListener('submit', function () {
    var bouton = form.querySelector('button[type="submit"]');
    if (bouton) {
      bouton.disabled = true;
      bouton.innerHTML = '<i class="ri-loader-4-line ed-spin me-1"></i>Chargement…';
    }
  });
})();
</script>
@endsection
