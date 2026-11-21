@extends('layout')
@section('title')
Parcours d'un étudiant
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">
        <div class="p-4 card">
            <form action="{{ route('parcours') }}" method="post">
                @csrf
                <div class="row">
                    <div class="form-group my-2">
                        <label for="etudiant" class="form-label">Étudiant</label>
                        <select name="etudiant" id="etudiant" class="form-control etudiant">
                            <option value="">Sélectionner un étudiant...</option>
                        </select>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary">Afficher</button>
                </div>
            </form>
        </div>

        @isset($etud)
        <div class="p-4 card">
            <h4>Parcours de {{$etud->nomEtu}} {{$etud->prenomsEtu}} {{$etud->MATRICULE}}</h4>
            <div id="html1">
                <ul>
                    @foreach ($etud->inscriptions as $inscription)
                    <li data-jstree='{"opened":true,"icon":"bx bxs-bookmark"}'>{{$inscription->anneeAcademique->ANNEE_ACAD}}
                      <ul data-jstree='{"icon":"bx bxs-bookmark"}'>
                        @php
                            $recapAnnuel=\App\Models\RecapAnnuel::where('MATRICULE',$inscription->MATRICULE)->where('Code_Annee_Etude',$inscription->anneeEtude->Code_Annee_Etude)->where('id_Annee_Acad',$inscription->anneeAcademique->id_Annee_Acad);
                        @endphp
                        @if ($recapAnnuel->get()->isEmpty())
                        <li data-jstree='{"icon":"bx bxs-bookmark"}'>Non disponible</li>
                        @else
                        <li data-jstree='{"opened":true,"icon":"bx bxs-bookmark"}'><span @class(['text-danger'=>$recapAnnuel->value('DecisionJury')!=='V','text-success'=>$recapAnnuel->value('DecisionJury')=='V' ])>{{$inscription->anneeEtude->Libelle_AnEtude}} [Moyenne : {{$recapAnnuel->value('MoyenneSem')}}] (Décision : {{$recapAnnuel->value('DecisionJury')=='V'?'Validée':'Non validée'}})</span>
                            <ul data-jstree='{"icon":"bx bxs-bookmark"}'>
                                @foreach ($inscription->anneeEtude->anneeEtudeSemestres as $semestreAnneeEtude)
                                @php
                                    $recapSemestre=\App\Models\RecapSemestre::where('MATRICULE',$inscription->MATRICULE)->where('id_Annee_Acad',$inscription->anneeAcademique->id_Annee_Acad)->where('id_S_Gr_Spe',$semestreAnneeEtude->classe->id_S_Gr_Spe)->first();
                                    $recapUes=\App\Models\RecapUe::whereRelation('ueSem','id_S_Gr_Spe',$semestreAnneeEtude->classe->id_S_Gr_Spe)->where('MATRICULE',$inscription->MATRICULE)->where('Code_Annee_Etude',$inscription->anneeEtude->Code_Annee_Etude)->get();
                                @endphp
                                <li data-jstree='{"opened":true,"icon":"bx bxs-bookmark"}'>{{$semestreAnneeEtude->classe->semestre->Lib_Semestre}} [Moyenne : {{$recapSemestre->MoyenneSem ?? 'Non disponible'}}]
                                    <ul data-jstree='{"icon":"bx bxs-bookmark"}'>
                                        @foreach ($recapUes as $recapUe)
                                        <li data-jstree='{"icon":"bx bxs-bookmark"}'><span @class(['text-danger'=>$recapUe->Etat_UE!=='V','text-success'=>$recapUe->Etat_UE=='V' ])>{{$recapUe->ueSem->ue->libelleUe}} [Moyenne : {{$recapUe->Moyenne_UE}}] (Décision : {{$recapUe->Etat_UE=='V'?'Validée':'Non validée'}})</span></li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @endif
                      </ul>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endisset
    </div>
</section>
@endsection
@section('js')
<script>
    $('#html1').jstree();
    $.ajax({
        url:"/api/etudiants",
        type:'GET',
        success:function(response){
            response.forEach(element => {
                $('.etudiant').append($('<option>', {
                    value: element.MATRICULE,
                    text: element.nomEtu+' '+element.prenomsEtu
                }));
            });
            new TomSelect(document.querySelector('.etudiant'),{maxOptions:null});
        }
    });
</script>
@endsection
