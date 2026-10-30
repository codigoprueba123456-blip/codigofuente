@extends('adminlte::page')

@section('title', 'Nueva carrera')

@section('content')
<div class="container-fluid pt-3">
    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title">Registrar nueva carrera</h3></div>
        @include('planes.carreras._form')
    </div>
</div>
@endsection
