@extends('layout')
@section('title')
Audits
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">
        <table class="table datatable">
            <thead>
                <th>#</th>
                <th>Table</th>
                <th>Action</th>
                <th>Auteur</th>
                <th>Date</th>
            </thead>
            <tbody>
                @foreach (\App\Models\Audit::orderBy('created_at','desc')->get() as $audit)
                <tr>
                    <td>{{$loop->iteration}}</td>
                    @php
                        $table = new (trim($audit->auditable_type,'"'));
                        $table=$table->getTable()
                    @endphp
                    <td>{{$table}}</td>
                    <td>
                        @switch($audit->event)
                            @case('created')
                                {{'Création'}}
                                @break
                            @case('updated')
                                {{'Modification'}} | <a href="javascript:void(0);" onclick="$('#showMr{{$loop->iteration}}').toggleClass('d-none');">Détails</a> <span class="d-none" id="showMr{{$loop->iteration}}">(
                                    @foreach (json_decode($audit->old_values) as $key => $value)
                                        @foreach (json_decode($audit->new_values) as $key1 => $value1)
                                        <ul>
                                            @if ($key==$key1)
                                                <li>
                                                    <span class="text-primary">{{$key}}</span> : <span class="text-danger">{{$value}}</span> -> <span class="text-success">{{$value1}}</span>
                                                </li>
                                            @endif
                                        </ul>
                                        @endforeach
                                    @endforeach
                                )</span>
                                @break
                            @case('deleted')
                                {{'Suppression'}}
                                @break
                            @default

                        @endswitch
                    </td>
                    <td>{{\App\Models\User::find($audit->user_id)->login}}</td>
                    <td>{{'le '.\Carbon\Carbon::parse($audit->created_at)->format('d/m/Y à h\hi\m\i\ns\s')}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
