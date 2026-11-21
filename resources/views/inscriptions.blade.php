@extends('layout')
@section('title')
Inscriptions
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
    @if ($errors->any())
    <div class="row">
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="p-4 card">
            <!-- Bordered Tabs -->
            <ul class="nav nav-tabs nav-tabs-bordered" id="borderedTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#enregistrement" type="button" role="tab" aria-controls="home" aria-selected="true">Enregistrement</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#importCSV" type="button" role="tab" aria-controls="profile" aria-selected="false">Importer un fichier csv</button>
            </li>
            </ul>
            <div class="tab-content pt-2" id="borderedTabContent">
                <div class="tab-pane fade show active p-2" id="enregistrement" role="tabpanel" aria-labelledby="home-tab">
                    <h5 class="fw-bold">Nouvelle inscription</h5>
                    <form action="{{ route('inscriptions.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="anneeEtude">Année d'étude <span class="text-danger">*</span></label>
                                    <select required name="anneeEtude" id="anneeEtude" class="form-control form-select">
                                        <option value="">Séléctionner une année d'étude...</option>
                                        @foreach (\App\Models\AnneeEtude::all() as $anEt)
                                        <option value="{{$anEt->Code_Annee_Etude}}">{{$anEt->Libelle_AnEtude}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="anneeAcademique">Année académique <span class="text-danger">*</span></label>
                                    <select required name="anneeAcademique" id="anneeAcademique" class="form-control form-select">
                                        <option value="">Séléctionner une année académique...</option>
                                        @foreach (\App\Models\AnneeAcademique::all() as $anAc)
                                        <option value="{{$anAc->id_Annee_Acad}}">{{$anAc->ANNEE_ACAD}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-6">
                                <label for="etudiant" class="form-label">Etudiant <span class="text-danger">*</span></label>
                                <select required name="etudiant" id="etudiant" class="etudiant form-control">
                                    <option value="">Sélectionner l'étudiant...</option>
                                </select>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="groupePedagogique">Groupe pédagogique <span class="text-danger">*</span></label>
                                    <select name="groupePedagogique" id="groupePedagogique" class="form-control form-select">
                                        <option value="">Aucun</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="montantAPayer">Montant à payer (en FCFA)<span class="text-danger">*</span></label>
                                    <input required type="number" name="montantAPayer" id="montantAPayer" class="form-control" placeholder="10000">
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="situationFinanciere">Situation financière <span class="text-danger">*</span></label>
                                    <select required name="situationFinanciere" id="situationFinanciere" class="form-control form-select">
                                        <option value="">Spécifier la situation financière...</option>
                                        <option value="OUI">Inscrit</option>
                                        <option value="NON">Non inscrit</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="statut">Statut</label>
                                    <select required name="statut" id="statut" class="form-control form-select">
                                        <option value="">Séléctionner un statut...</option>
                                        @foreach (\App\Models\Statut::all() as $statut)
                                        <option value="{{$statut->codeStatut}}">{{$statut->LibStatut}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="observations">Observations</label>
                                    <textarea name="observations" id="observations" class="form-control" placeholder="Aucune"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade p-2" id="importCSV" role="tabpanel" aria-labelledby="profile-tab">
                    <form action="{{ route('importInscription') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="csv" id="csv" class="form-control form-control-file mt-3" accept=".csv">
                        <div class="mt-4 text-end">
                          <button class="btn btn-primary">Valider</button>
                          <a href="/modeles/inscriptions.csv" download class="btn btn-dark">Télécharger un modèle</a>
                        </div>
                    </form>
                </div>
            </div><!-- End Bordered Tabs -->
        </div>
        <div class="display-ins card p-4">
            <form action="" id="recher">
                <div class="clearfix mb-4">
                    <div class="float-end">
                        <button class="btn btn-dark" type="button" onclick="rechercheEtudiant()">Valider</button>
                    </div>
                    <div class="float-start">
                        <input type="search" name="recherche" id="recherche" class="form-control" placeholder="Rechercher">
                    </div>
                </div>
            </form>
            <div id="resultats-ins">
            <table class="table table-striped" id="tableD">
                <thead>
                    <th>Matricule</th>
                    <th>Nom complet</th>
                    <th>Année académique</th>
                    <th>Année d'étude</th>
                    <th>Reste à payer</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{$item->MATRICULE}}</td>
                            <td>{{$item->etudiant ? $item->etudiant->nomEtu . ' ' . $item->etudiant->prenomsEtu : 'N/A'}}</td>
                            <td>{{$item->anneeacademique?->ANNEE_ACAD ?? 'N/A'}}</td>
                            <td>{{$item->ID_FILIERE}}</td>
                            <td>
                                @if($item->reste_paye > 0)
                                    <span class="badge bg-warning text-dark">{{number_format($item->reste_paye, 0, ',', ' ')}} F</span>
                                @elseif($item->frais_paye > 0)
                                    <span class="badge bg-success">Soldé</span>
                                @else
                                    <span class="badge bg-secondary">À configurer</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <form action="{{ route('inscriptions.destroy') }}" method="post" id="form-del-ins-{{$loop->iteration}}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn"  data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"><i title="Voir" class="bi bi-eye"></i></button>
                                    {{-- On se base UNIQUEMENT sur reste_paye, jamais sur S_Finance,
                                         qui peut être désynchronisé (colonne redondante/legacy). --}}
                                    @if ($item->reste_paye > 0)
                                    <button
                                        type="button"
                                        class="btn paiement"
                                        data-bs-toggle="modal"
                                        data-bs-target="#paiement"
                                        data-bs-matricule="{{$item->MATRICULE}}"
                                        data-bs-anneeAcademique="{{$item->Id_Annee}}"
                                        data-bs-anneeEtude="{{$item->ID_FILIERE}}"
                                        data-bs-montantPaye="{{$item->reste_paye}}"
                                        title="Enregistrer paiement">
                                        <i class="text-green-500 bi bi-cash"></i>
                                    </button>
                                    @elseif($item->frais_paye > 0)
                                    <span>Soldé!</span>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                    <a class="btn" href="{{ route('inscriptions.edit') }}?m={{$item->MATRICULE}}&aa={{$item->Id_Annee}}&ae={{$item->ID_FILIERE}}"><i title="Modifier" class="bi bi-pencil text-secondary"></i></a>
                                    <button type="button" onclick="downloadpdf({{$item->MATRICULE}},{{$item->Id_Annee}},'{{$item->ID_FILIERE}}','{{\App\Models\AnneeAcademique::find($item->Id_Annee)?->ANNEE_ACAD ?? ''}}')" class="btn" title="Carte étudiant"><i class="bi bi-person-vcard"></i></button>
                                    <a href="{{ route('engagement.imprimer', ['matricule' => $item->MATRICULE, 'annee' => $item->Id_Annee, 'filiere' => $item->ID_FILIERE]) }}" target="_blank" class="btn" title="Fiche d'engagement"><i class="bi bi-file-earmark-text text-primary"></i></a>
                                    <input type="hidden" name="matricule" value="{{$item->MATRICULE}}">
                                    <input type="hidden" name="anneeAcademique" value="{{$item->Id_Annee}}">
                                    <input type="hidden" name="anneeEtude" value="{{$item->ID_FILIERE}}">

                                    <button type="button" onclick="confirmDelete(document.getElementById('form-del-ins-{{$loop->iteration}}'), 'l\'inscription en <strong>{{$item->ID_FILIERE}}</strong> de <strong>{{$item->etudiant ? $item->etudiant->nomEtu . ' ' . $item->etudiant->prenomsEtu : 'l\'étudiant'}}</strong>')" class="btn"><i title="Supprimer" class="bi bi-trash text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">A propos de l'étudiant</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Matricule : {{$item->MATRICULE}}
                                        <hr>
                                        Nom : {{$item->etudiant?->nomEtu ?? 'N/A'}}
                                        <hr>
                                        Prénom(s) : {{$item->etudiant?->prenomsEtu ?? 'N/A'}}
                                        <hr>
                                        Année académique : {{$item->anneeacademique?->ANNEE_ACAD ?? 'N/A'}}
                                        <hr>
                                        Année d'étude : {{$item->ID_FILIERE}}
                                        <hr>
                                        Statut : {{$item->statut?->LibStatut ?? 'N/A'}}
                                        <hr>
                                        Groupe pédagogique : {{$item->Group_PDGQ??"Aucun"}}
                                        <hr>
                                        Scolarité : {{number_format($item->frais_paye, 0, ',', ' ')}} F
                                        <hr>
                                        Reste à payer : {{number_format($item->reste_paye, 0, ',', ' ')}} F
                                        <hr>
                                        Situation financière : {{($item->reste_paye <= 0) ? "Inscrit" : "Non inscrit"}}
                                        <hr>
                                        Observations : {{$item->Observations}}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
            {{$items->links()}}
            </div>{{-- fin #resultats-ins --}}
            <div class="modal fade" id="paiement" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Enregistrer un paiement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-5 pb-5">
                            <form method="POST" action="{{ route('paiements.store') }}" class="needs-validation">
                                @csrf
                                <div class="row g-3">
                                    <input type="hidden" name="matricule">
                                    <input type="hidden" name="anneeAcademique">
                                    <input type="hidden" name="anneeEtude">
                                    <span class="form-text text-muted fst-italic">
                                        Il reste <span class="fw-bold reste"></span> F à payer
                                    </span>
                                    <div class="col-lg-6 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="datePaiement">Date de paiement <span class="text-danger">*</span></label>
                                            <input type="text" name="datePaiement" id="datePaiement" class="form-control" placeholder="01/01/1970" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="montantPaye">Montant (en FCFA) <span class="text-danger">*</span></label>
                                            <input required type="number" name="montantPaye" id="montantPaye" class="form-control" placeholder="10000" min="0">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="modePaiement">Mode de paiement <span class="text-danger">*</span></label>
                                            <select name="modePaiement" id="modePaiement" class="form-control form-select" required>
                                                <option value="">Séléctionner un mode de paiement...</option>
                                                <option value="Chèque">Chèque</option>
                                                <option value="Espèces">Espèces</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 text-end">
                                    <button class="btn btn-primary">Enregistrer</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                </div>

                            </form>
                        </div>
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
    etu=new TomSelect('.etudiant',{maxOptions:null});
    $.ajax({
        url:'/api/etudiants',
        success:function(response){
            response.forEach(item => {
                etu.addOption({value: item.MATRICULE,text: item.nomEtu+' '+item.prenomsEtu});
            });
            $('.etudiant').addClass('form-select');
        }
    })
    function rechercheEtudiant(){
    $('#resultats-ins').html('<button class="btn btn-link text-purple-500" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
    $.ajax({
        url:"{{route('afficherInscription')}}",
        method:'GET',
        data:$('#recher').serialize(),
        success:function(response){
            $('#resultats-ins').html(response);
        }
    });
    }
            const paiement = document.getElementById('paiement')
            const datepicker = new Datepicker(document.querySelector('#datePaiement'), {
                autohide:true,
                buttonClass: 'btn',
            });
            new TomSelect('#modePaiement',{create:true})
            paiement.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const button = event.relatedTarget
                // Extract info from data-bs-* attributes
                const matricule = button.getAttribute('data-bs-matricule')
                const anneeAcademique = button.getAttribute('data-bs-anneeAcademique')
                const anneeEtude = button.getAttribute('data-bs-anneeEtude')
                const montantRestant = button.getAttribute('data-bs-montantPaye')

                // Update the modal's hidden fields
                const matriculeInput = paiement.querySelector('.modal-body input[name="matricule"]')
                const anneeAcademiqueInput = paiement.querySelector('.modal-body input[name="anneeAcademique"]')
                const anneeEtudeInput = paiement.querySelector('.modal-body input[name="anneeEtude"]')
                const montantInput = document.getElementById('montantPaye')

                const dateInput = document.getElementById('datePaiement')
                const now = new Date()
                const day = String(now.getDate()).padStart(2, '0')
                const month = String(now.getMonth() + 1).padStart(2, '0')
                const year = now.getFullYear()
                dateInput.value = `${day}/${month}/${year}`

                matriculeInput.value = matricule
                anneeAcademiqueInput.value = anneeAcademique
                anneeEtudeInput.value = anneeEtude
                montantInput.value = ''
                montantInput.setAttribute('max', montantRestant)
                montantInput.setAttribute('min', '1')
                $('.reste').html(Number(montantRestant).toLocaleString('fr-FR'))

                // Remove old listener if any
                const newInput = montantInput.cloneNode(true)
                montantInput.parentNode.replaceChild(newInput, montantInput)

                // Real-time validation on amount input
                newInput.addEventListener('input', function() {
                    const val = parseInt(this.value) || 0
                    const max = parseInt(montantRestant) || 0
                    if (val > max) {
                        this.classList.add('is-invalid')
                        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                            const feedback = document.createElement('div')
                            feedback.className = 'invalid-feedback'
                            feedback.textContent = 'Le montant ne peut pas dépasser ' + max.toLocaleString('fr-FR') + ' F'
                            this.parentNode.appendChild(feedback)
                        }
                    } else {
                        this.classList.remove('is-invalid')
                        const feedback = this.parentNode.querySelector('.invalid-feedback')
                        if (feedback) feedback.remove()
                    }
                })

                // Prevent form submission if amount exceeds max
                const form = paiement.querySelector('form')
                form.onsubmit = function(e) {
                    const val = parseInt(newInput.value) || 0
                    const max = parseInt(montantRestant) || 0
                    if (val > max || val <= 0) {
                        e.preventDefault()
                        newInput.classList.add('is-invalid')
                        alert('Le montant doit être compris entre 1 et ' + max.toLocaleString('fr-FR') + ' F')
                        return false
                    }
                }
            })
    function downloadpdf(param,param1,param2,param3=null) {
        var opt = {
            margin:       0,
            image:        { type: 'png', quality: 1 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'l', format: 'a7' },
            html2canvas:{scrollX: 0,scrollY: 0}
        };
        confirmAction({
            title: 'Télécharger la carte étudiant',
            message: 'Voulez-vous générer et télécharger la carte d\'étudiant pour le matricule <strong>' + param + '</strong> ?',
            icon: 'bi-person-vcard-fill',
            gradient: 'linear-gradient(135deg,#1a73e8,#4285f4)',
            confirmText: 'Télécharger',
            confirmClass: 'btn-primary',
            confirmIcon: 'bi-download',
            onConfirm: function() {
                $.ajax({
                    url:'/carte-etudiant/'+param+'/'+param1+'/'+param2,
                    success:function(response){
                        html2pdf().set(opt).from(response).save('Carte_etudiant_'+param+'_'+param2+'_'+param3);
                    },
                });
            }
        });
    }

</script>
@endsection