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
        <div class="display-ins">

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
    $('.display-ins').addClass('card p-2');
    $('.display-ins').html('<button class="btn btn-link text-purple-500" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
    $.ajax({
        url:"{{route('afficherInscription')}}",
        success:function(response){
            $('.display-ins').html('<h5 class="fw-bold px-4 pt-4">Liste des inscriptions</h5>'+response);
            const dataTable = new simpleDatatables.DataTable("#tableD");
            $('.etudiant').addClass('form-select');
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
                const montantPaye = button.getAttribute('data-bs-montantPaye')
                // If necessary, you could initiate an AJAX request here
                // and then do the updating in a callback.
                //
                // Update the modal's content.
                const matriculeInput = paiement.querySelector('.modal-body input[name="matricule"]')
                const anneeAcademiqueInput = paiement.querySelector('.modal-body input[name="anneeAcademique"]')
                const anneeEtudeInput = paiement.querySelector('.modal-body input[name="anneeEtude"]')

                matriculeInput.value = matricule
                anneeAcademiqueInput.value = anneeAcademique
                anneeEtudeInput.value = anneeEtude
                $('#montantPaye').attr('max',montantPaye);
                $('.reste').html(montantPaye)
            })
        }
    });
    function downloadpdf(param,param1,param2,param3=null) {
        var opt = {
            margin:       0,
            image:        { type: 'png', quality: 1 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'l', format: 'a7' },
            html2canvas:{scrollX: 0,scrollY: 0}
        };
        if (confirm('Voulez-vous télécharger cette carte étudiant ?')) {
            $.ajax({
                url:'/carte-etudiant/'+param+'/'+param1+'/'+param2,
                success:function(response){
                    html2pdf().set(opt).from(response).save('Carte_etudiant_'+param+'_'+param2+'_'+param3);
                },
            })
        }
    }

</script>
@endsection
