@extends('adminlte::page')

@section('title', $configuracion['titulo'])

@section('content_header')
    <h1>{{ $configuracion['titulo'] }}</h1>
@stop

@section('content')
    {{-- El listado diferencia postulantes pendientes de quienes ya se inscribieron como estudiantes. --}}
    @include('reportes.inscripciones.formulario')
@stop
