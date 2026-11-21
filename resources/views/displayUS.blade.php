<table class="table table-striped align-middle">
    <thead>
        <th>Année académique</th>
        <th>Semestre</th>
        <th>UE</th>
        <th>Note minimale</th>
        <th>Crédit</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{$item->anneeacademique->ANNEE_ACAD}}</td>
                <td>{{$item->semestre->Nom_Classe}}</td>
                <td>
                    Intitulé : {{$item->ue->libelleUe}}<br>
                    Catégorie de l'UE : {{$item->categorieUe->Lib_Cat_Ue}}<br>
                    Importance : {{$item->importance->libelleImp}}<br>
                </td>
                <td>{{$item->Seuil_Ue}}</td>
                <td>{{$item->Credit}}</td>
                <td class="text-nowrap">
                    <form action="{{ route('attribuer-ue-semestre.destroy',$item->Id_Attr_ue_Sem) }}" method="post">
                        @csrf
                        @method('DELETE')

                        <a class="btn" href="{{ route('attribuer-ue-semestre.edit', $item->Id_Attr_ue_Sem) }}"><i class=" ri-pencil-line text-secondary"></i></a>

                        <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer cette relation entre  l\'UE {{$item->ue->libelleUe}} et le semestre {{$item->semestre->Nom_Classe}}')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
