<table class="table table-striped tableD" id="tableD">
    <thead>
        <th>UE</th>
        <th>ECUE</th>
        <th>Mode d'évaluation</th>
        <th>Enseignant</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td class="align-middle">{{$item->ueSemestre->ue->libelleUe}} | {{$item->ueSemestre->semestre->Nom_Classe}} | {{$item->ueSemestre->anneeacademique->ANNEE_ACAD}}</td>
                <td>
                    {{ $item->ecue->libelleEcu }}
                </td>
                <td>{{$item->Eva_Mode}}</td>
                <td>{{$item->professeur->nomProf}} {{$item->professeur->prenomsProf}}</td>
                <td class="text-nowrap">


                        <div class="row">
                            <div class="col-auto">
                                <button type="button" class="btn"><i class="bi bi-eye" data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"></i></button>
                            </div>
                            <div class="col-auto">
                                <form action="{{ route('attribuer-ecue-ue.edit') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="uesem" value="{{$item->Id_Attr_ue_Sem}}">
                                    <input type="hidden" name="ecUe" value="{{$item->codeEcu}}">
                                    <button type="submit" class="btn btn"><i class="bi bi-pencil text-secondary"></i></button>
                                </form>
                            </div>
                            <div class="col-auto">
                                <form action="{{ route('attribuer-ecue-ue.destroy') }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="uesem" value="{{$item->Id_Attr_ue_Sem}}">
                                    <input type="hidden" name="ecUe" value="{{$item->codeEcu}}">
                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer cette relation entre  l\'UE {{$item->ueSemestre->ue->libelleUe}} et l\'ECUE {{$item->ecue->libelleEcu}}')" class="btn"><i class="bi bi-trash text-danger"></i></button>
                                </form>
                            </div>
                        </div>


                </td>
            </tr>
            <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTitleId">Détails</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Année académique : {{$item->ueSemestre->anneeacademique->ANNEE_ACAD}}
                            <hr>
                            Semestre : {{$item->ueSemestre->semestre->Nom_Classe}}
                            <hr>
                            UE : {{$item->ueSemestre->ue->libelleUe}}
                            <hr>
                            ECUE : {{$item->ecue->libelleEcu}}
                            <hr>
                            Note minimale : {{$item->Seuil_Ecu}}
                            <hr>
                            Pondération : {{$item->Ponderation*100}} %
                            <hr>
                            Poids Devoir : {{$item->PoidsDev*100}} %
                            <hr>
                            Poids Partiel: {{$item->PoidsPart*100}} %
                            <hr>
                            Enseignant responsable : {{$item->professeur->nomProf}} {{$item->professeur->prenomsProf}}
                            <hr>
                            Masse horaire : {{$item->MasseHoraire}}
                            <hr>
                            Masse horaire exécuté : {{$item->MasseHoraireExec}}
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
