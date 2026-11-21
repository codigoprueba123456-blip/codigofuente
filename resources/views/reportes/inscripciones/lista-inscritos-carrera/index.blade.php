@extends('adminlte::page')

@section('title', $configuracion['titulo'])

@section('content_header')
    <h1>{{ $configuracion['titulo'] }}</h1>
@stop

@section('content')
    {{-- Presenta una fila por inscripción vigente de la carrera y gestión seleccionadas. --}}
    @include('reportes.inscripciones.formulario')
@stop
