@extends('layout')
@section('title')
Afficher les prévisions
@endsection
@section('content')
<section class="section">
<div class="container-fluid">
    <div class="row">
        <div class="card p-4">
            <h4>Afficher les prévisions</h4>
            <form action="{{ route('impression.prevision') }}" method="post" id="prevForm">
                @csrf
                <div class="row">

                    <div class="col-lg-12 col-sm-12 col-md-12">
                        <div class="form-group mb-3">
                            <label for="dateDebut" class="form-label">Période</label>
                            <div class="input-group" id="periode">
                                <input type="text" name="dateDebut" id="dateDebut" class="form-control" placeholder="01/01/1970">
                                <span class="input-group-text">à</span>
                                <input type="text" name="dateFin" id="dateFin" class="form-control" placeholder="01/01/1970">
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-primary" id="afficher">Afficher</button>
                        <input type="submit" value="Imprimer" name="btn" class="btn btn-dark">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="displayPrevisions">

    </div>
</div>
</section>
@endsection
@section('js')
<script>
    $('#afficher').on('click',function(e){
        $('.displayPrevisions').addClass('card p-4');
        $('.displayPrevisions').html('<button class="btn btn-link text-purple-500" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement en cours...</button>');
        $.ajax({
            url:'/dPrev',
            method:'POST',
            data:$('#prevForm').serialize(),
            success:function(response){
                $('.displayPrevisions').html(response)
                const dataTable = new simpleDatatables.DataTable("#tableD");
            }
        })
    });
    elem = document.querySelector('#periode');
    const datepicker = new DateRangePicker(elem, {
        autohide:true,
        buttonClass: 'btn',
        todayButton:true,
        todayButtonMode:1,
        allowOneSidedRange:true
    });
    [document.querySelector('#dateDebut'),
    document.querySelector('#dateFin')].forEach(element => {
        element.setAttribute('readonly',true);
    });
</script>
@endsection
