<table class="table table-striped datatable" id="tableD">
    <thead>
        <th>Identité</th>
        <th>Classe</th>
        <th>Montant à payer</th>
        <th>Payé</th>
        <th>Echéance</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @foreach ($previsions as $prevision)
        <tr class="text-bg-danger">
            <td>{{$prevision->MATRICULE}} / {{\App\Models\Etudiant::find($prevision->MATRICULE)->fullname()}}</td>
            <td>{{$prevision->ID_FILIERE}} {{\App\Models\AnneeAcademique::find($prevision->Id_Annee)->ANNEE_ACAD}}</td>
            <td>{{$prevision->montant_a_payer}}</td>
            <td>{{($prevision->paye==1)?'Oui':'Non'}}</td>
            @php
                \Carbon\Carbon::setlocale('fr')
            @endphp
            <td><span class="text-capitalize">{{\Carbon\Carbon::parse($prevision->echeance)->translatedFormat('l j F Y')}}</span></td>
            <td>
                <form action="{{ route('previsions.destroy',$prevision->id) }}" method="post" id="form-del-displayprev-{{$prevision->id}}">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn" onclick="confirmDelete(document.getElementById('form-del-displayprev-{{$prevision->id}}'), 'cette prévision de <strong>{{ number_format($prevision->montant_a_payer, 0, \',\', \' \') }} F CFA</strong>')"><i class="text-danger ri-delete-bin-line"></i></button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
