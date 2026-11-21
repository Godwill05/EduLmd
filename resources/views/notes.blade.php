@extends('layout')
@section('title')
Gestion des notes
@endsection
@section('content')
<section class="section">
<div class="container-fluid">
    @if (session()->has('succes'))
    <div class="row">
        <div class="alert alert-success">
            {{session('succes')}}
        </div>
    </div>
    @endif
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger">
            {{session('echec')}}
        </div>
    </div>
    @endif
    <div class="row">
        <div class="p-4 card">
            <form action="{{ route('afficherNotes') }}" method="POST" id="formFiltresNotes"
                  data-sel-annee-etude="{{ request('anneeEtude') }}"
                  data-sel-annee-acad="{{ request('anneeAcademique') }}"
                  data-sel-semestre="{{ request('semestreEtude') }}"
                  data-sel-ue="{{ request('ue') }}"
                  data-sel-ecue="{{ request('ecue') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeEtude">Année d'étude</label>
                            <select name="anneeEtude" id="anneeEtude" class="form-control form-select">
                                <option value="">Sélectionner une année d'étude...</option>
                                @foreach (\App\Models\AnneeEtude::all() as $anneeEtude)
                                <option value="{{$anneeEtude->Code_Annee_Etude}}">{{$anneeEtude->Libelle_AnEtude}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="anneeAcademique">Année académique</label>
                            <select name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                <option value="">Sélectionner l'année académique...</option>
                                @foreach (\App\Models\AnneeAcademique::all() as $anneeAcademique)
                                <option value="{{$anneeAcademique->id_Annee_Acad}}">{{$anneeAcademique->ANNEE_ACAD}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="semestreEtude">Semestre d'étude</label>
                            <select name="semestreEtude" id="semestreEtude" class="semestre form-control" disabled>
                                <option value="">Sélectionner le semestre...</option>
                            </select>
                            <div class="form-text" id="requiredAnEtd">Choisissez d'abord une année d'étude !</div>
                            <div class="form-text text-danger d-none" id="erreurSemestre"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ue">UE</label>
                            <select name="ue" id="ue" class="ue form-control" disabled>
                                <option value="">Sélectionner l'UE...</option>
                            </select>
                            <div class="form-text" id="requiredSemAnac">Choisissez d'abord le semestre et l'année académique !</div>
                            <div class="form-text text-danger d-none" id="erreurUe"></div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="ecue">ECUE</label>
                            <select name="ecue" id="ecue" class="form-control ecue" disabled>
                                <option value="">Sélectionner l'ECUE...</option>
                            </select>
                            <div class="form-text" id="requiredUe">Choisissez d'abord l'UE !</div>
                            <div class="form-text text-danger d-none" id="erreurEcue"></div>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button class="btn btn-primary mt-4">Afficher</button>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="p-4 card">
            <table class="table table-striped datatable">
                <thead>
                    <th>Classe</th>
                    <th>UE / ECUE</th>
                    <th>Enseignant</th>
                    <th>Date et heure</th>
                    <th>Nature</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @isset($evaluations)
                    @foreach ($evaluations as $evaluation)
                        <tr>
                            <td>{{$evaluation->Code_Annee_Etude}} / {{$evaluation->Nom_Classe}} {{$evaluation->ANNEE_ACAD}}</td>
                            <td>{{$evaluation->libelleUe}} / {{$evaluation->libelleEcu}}</td>
                            <td>{{$evaluation->nomProf}} {{$evaluation->prenomsProf}}<br>Tel : {{$evaluation->telProf}}</td>
                            <td>{{$evaluation->dateEval}} de {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->format("H:i")}} à {{\Carbon\Carbon::parse($evaluation->heureDebutEval)->addHours($evaluation->dureeEval)->format("H:i")}}</td>
                            <td>{{$evaluation->libTypEval}}</td>
                            <td>
                                {{-- <form action="/test?id_eval={{$evaluation->numeroEval}}&id_annee={{$evaluation->an}}&id_an_etude={{$evaluation->Code_Annee_Etude}}">
                                    @csrf --}}
                                    <a target="_blank" title="Insérer notes" href="?id_eval={{$evaluation->numeroEval}}&id_annee={{$evaluation->an}}&id_an_etude={{$evaluation->Code_Annee_Etude}}#modalNote" target="_blank" class="btn"><i class=" ri-menu-line"></i></a>
                                {{-- </form> --}}
                            </td>
                        </tr>
                    @endforeach
                    @endisset
                </tbody>
            </table>
            @if(!empty(request()->all()) && !request()->isMethod('POST'))
            <div class="modal fade" id="modalNote" tabindex="-1"  data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Insertion des notes des étudiants de l'année étude <span class="text-danger">{{request()->id_an_etude}}</span> pour le <span class="text-lowercase">{{\App\Models\Evaluation::find(request()->id_eval)->typeEvaluation->libTypEval}}</span> de l'ECUE <span class="text-danger">"{{\App\Models\Evaluation::find(request()->id_eval)->ecue->libelleEcu}}"</span> pour le compte de l'année académique <span class="text-danger">{{\App\Models\AnneeAcademique::find(request()->id_annee)->ANNEE_ACAD}}</span></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="insererNotes" action="{{ route('notes.insererNotes') }}">
                                @csrf
                                <input type="hidden" name="numeroEvaluation" value="{{request()->id_eval}}">
                                <table class="table table-striped">
                                    <thead>
                                        <th>Matricule</th>
                                        <th>Nom</th>
                                        <th>Prénoms</th>
                                        <th>Note obtenue</th>
                                        @can('Supprimer les notes')
                                        <th>Actions</th>
                                        @endcan
                                    </thead>
                                    <tbody>
                                        @php
                                            $an=request()->id_annee;
                                            $fil=request()->id_an_etude;
                                            $eva=request()->id_eval
                                        @endphp
                                        @foreach (DB::select("SELECT inscriptions.MATRICULE as MATRICULE, nomEtu,prenomsEtu FROM etudiant,`inscriptions` WHERE etudiant.MATRICULE=inscriptions.MATRICULE and Id_Annee=" . $an . " AND`ID_FILIERE`='" . $fil . "' ORDER BY nomEtu,prenomsEtu ASC") as $inscription)
                                        <tr>
                                            @php
                                                $rcnote=DB::select("SELECT idObtenir,numeroInscription as MATRICULE, numeroEval,Note,loginupdate FROM obtenir WHERE obtenir.numeroInscription='" . $inscription->MATRICULE . "' and obtenir.numeroEval=" . $eva . "")
                                            @endphp
                                            <td>{{$inscription->MATRICULE}}</td>
                                            <td>{{$inscription->nomEtu}}</td>
                                            <td>{{$inscription->prenomsEtu}}</td>
                                            @if(isset($rcnote[0]->idObtenir))
                                            <td><input id="note{{$rcnote[0]->idObtenir}}" type="number" min="0" step="0.01" max="20" class="form-control form-control-sm" value="{{$rcnote[0]->Note ?? ''}}" name="{{$inscription->MATRICULE}}"></td>
                                            @can('Supprimer les notes')
                                            <td id="supprimerNote{{$rcnote[0]->idObtenir}}"><i data-reach="{{$rcnote[0]->idObtenir}}" onclick="supprimerNote({{$rcnote[0]->idObtenir}})" class="btn ri-delete-bin-line text-danger" title="Supprimer note"></i></td>
                                            @endcan
                                            @else
                                            <td><input type="number" min="0" step="0.01" max="20" class="form-control form-control-sm" value="{{$rcnote[0]->Note ?? ''}}" name="{{$inscription->MATRICULE}}"></td>
                                            @can('Supprimer les notes')
                                            <td><span class=" ri-delete-bin-line btn disabled"></span></td>
                                            @endcan
                                            @endif
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            {{-- Bouton natif : `$().submit()` ne déclenche pas
                                 l'événement submit, donc l'Ajax ne partait pas. --}}
                            <button type="submit" form="insererNotes" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</section>
@endsection
@section('js')
<script>
/*
 * Formulaire « Gestion des notes ».
 *
 * Les troisièmes niveaux (semestre, UE, ECUE) sont désactivés au départ : un
 * <select> désactivé n'est jamais envoyé avec le formulaire. La cascade Ajax
 * les remplit, mais après « Afficher » la page se recharge et le navigateur
 * oubliait la sélection : le formulaire revenait vide, semestre et UE
 * désactivés, donc jamais soumis. On restaure ici la cascade complète à
 * partir de la sélection mémorisée dans le formulaire.
 */
(function () {
  'use strict';

  var form     = document.getElementById('formFiltresNotes');
  var selEtude = document.getElementById('anneeEtude');
  var selAcad  = document.getElementById('anneeAcademique');
  var selSem   = document.getElementById('semestreEtude');
  var selUe    = document.getElementById('ue');
  var selEcue  = document.getElementById('ecue');

  if (!form || !selEtude || !selAcad || !selSem || !selUe || !selEcue) return;

  var memorise = {
    etude: form.dataset.selAnneeEtude  || '',
    acad:  form.dataset.selAnneeAcad   || '',
    sem:   form.dataset.selSemestre    || '',
    ue:    form.dataset.selUe          || '',
    ecue:  form.dataset.selEcue        || ''
  };

  /* ─────────── Utilitaires ─────────── */

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
    if (valeurCourante) select.value = valeurCourante;
  }

  function signaler(id, message) {
    var el = document.getElementById(id);
    if (!el) return;
    if (!message) { el.classList.add('d-none'); el.textContent = ''; return; }
    el.textContent = message;
    el.classList.remove('d-none');
  }

  /* Requête JSON. On envoie l'en-tête X-Requested-With : les routes
     /api/ue et /api/ecue répondent 401 sans lui (contrôle request()->ajax()). */
  function charger(url) {
    return fetch(url, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    }).then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    });
  }

  /* Active un select qui reprend une valeur mémorisée, même si son parent
     n'a pas encore été rejoué. */
  function activer(select) { select.disabled = false; }

  /* ─────────── Cascade ─────────── */

  function chargerSemestres(codeAnneeEtude, valeurAChoisir) {
    vider(selSem, 'Sélectionner le semestre...');
    signaler('erreurSemestre', '');
    if (!codeAnneeEtude) {
      selSem.disabled = true;
      return Promise.resolve();
    }
    return charger('/api/semestre-etude/' + encodeURIComponent(codeAnneeEtude))
      .then(function (response) {
        activer(selSem);
        remplir(selSem, response.map(function (e) {
          return { valeur: e.id_S_Gr_Spe, texte: e.Nom_Classe };
        }), valeurAChoisir);
        if (!response.length) signaler('erreurSemestre', 'Aucun semestre pour cette année d\'étude.');
      })
      .catch(function (e) {
        signaler('erreurSemestre', 'Impossible de charger les semestres (' + e.message + ').');
      });
  }

  function chargerUes(idAnneeAcad, idSemestre, valeurAChoisir) {
    vider(selUe, 'Sélectionner l\'UE...');
    signaler('erreurUe', '');
    if (!idAnneeAcad || !idSemestre) {
      selUe.disabled = true;
      return Promise.resolve();
    }
    return charger('/api/ue/' + encodeURIComponent(idAnneeAcad) + '/' + encodeURIComponent(idSemestre))
      .then(function (response) {
        activer(selUe);
        remplir(selUe, response.map(function (e) {
          return { valeur: e.Id_Attr_ue_Sem, texte: e.libelleUe };
        }), valeurAChoisir);
        if (!response.length) signaler('erreurUe', 'Aucune UE pour ce semestre et cette année académique.');
      })
      .catch(function (e) {
        signaler('erreurUe', 'Impossible de charger les UE (' + e.message + ').');
      });
  }

  function chargerEcues(idAttrUeSem, valeurAChoisir) {
    vider(selEcue, 'Sélectionner l\'ECUE...');
    signaler('erreurEcue', '');
    if (!idAttrUeSem) {
      selEcue.disabled = true;
      return Promise.resolve();
    }
    return charger('/api/ecue/' + encodeURIComponent(idAttrUeSem))
      .then(function (response) {
        activer(selEcue);
        remplir(selEcue, response.map(function (e) {
          return { valeur: e.ecue, texte: e.libelle };
        }), valeurAChoisir);
        if (!response.length) signaler('erreurEcue', 'Aucune ECUE rattachée à cette UE.');
      })
      .catch(function (e) {
        signaler('erreurEcue', 'Impossible de charger les ECUE (' + e.message + ').');
      });
  }

  /* ─────────── Écouteurs ─────────── */

  selEtude.addEventListener('change', function () {
    selUe.disabled = true;
    selEcue.disabled = true;
    vider(selUe, 'Sélectionner l\'UE...');
    vider(selEcue, 'Sélectionner l\'ECUE...');
    chargerSemestres(this.value, '');
  });

  selAcad.addEventListener('change', function () {
    if (!selSem.value) {
      signaler('erreurUe', 'Choisissez d\'abord le semestre.');
      return;
    }
    selEcue.disabled = true;
    vider(selEcue, 'Sélectionner l\'ECUE...');
    chargerUes(this.value, selSem.value, '');
  });

  selSem.addEventListener('change', function () {
    if (!selAcad.value) {
      signaler('erreurUe', 'Choisissez d\'abord l\'année académique.');
      return;
    }
    selEcue.disabled = true;
    vider(selEcue, 'Sélectionner l\'ECUE...');
    chargerUes(selAcad.value, this.value, '');
  });

  selUe.addEventListener('change', function () {
    chargerEcues(this.value, '');
  });

  /* ─────────── Restauration après « Afficher » ─────────── */

  if (memorise.etude) {
    chargerSemestres(memorise.etude, memorise.sem).then(function () {
      if (!memorise.acad || !memorise.sem) return;
      return chargerUes(memorise.acad, memorise.sem, memorise.ue).then(function () {
        if (!memorise.ue) return;
        return chargerEcues(memorise.ue, memorise.ecue);
      });
    });
  }

  /* ─────────── Modal d'insertion des notes ─────────── */

  $(document).ready(function () {
    if (window.location.href.indexOf('#modalNote') != -1) {
      $('#modalNote').modal('show');
    }
  });

  window.supprimerNote = function (note) {
    if (!confirm('Voulez-vous vraiment retirer cette note ?')) return;
    fetch('/api/deleteNote/' + encodeURIComponent(note), {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
          ? document.querySelector('meta[name="csrf-token"]').content
          : ''
      },
      credentials: 'same-origin'
    }).then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      $('#note' + note).val('');
      $('#supprimerNote' + note).html('<span class="ri-delete-bin-line btn disabled"></span>');
    }).catch(function (e) {
      alert('La suppression a échoué (' + e.message + ').');
    });
  };

  /* Enregistrement des notes : le retour doit venir d'ici, pas du chargement
     de la page. L'ancien code passait `alert(...)` en rappel, ce qui
     l'exécutait immédiatement. */
  $(document).on('submit', '#insererNotes', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('button[type="submit"], .btn-primary').first();

    $btn.prop('disabled', true).text('Enregistrement…');

    fetch($form.attr('action') || window.location.href, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
          ? document.querySelector('meta[name="csrf-token"]').content
          : ''
      },
      body: new URLSearchParams(new FormData(this)),
      credentials: 'same-origin'
    }).then(function (r) {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      $('#modalNote').modal('hide');
      /* Bandeau discret plutôt qu'une alerte bloquante. */
      var bandeau = document.createElement('div');
      bandeau.className = 'alert alert-success position-fixed';
      bandeau.style.cssText = 'top:1rem;right:1rem;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.15)';
      bandeau.textContent = 'Notes enregistrées.';
      document.body.appendChild(bandeau);
      setTimeout(function () { bandeau.remove(); }, 3200);
    }).catch(function (err) {
      alert('L\'enregistrement a échoué (' + err.message + ').');
    }).finally(function () {
      $btn.prop('disabled', false).text('Enregistrer');
    });
  });
})();
</script>
@endsection
