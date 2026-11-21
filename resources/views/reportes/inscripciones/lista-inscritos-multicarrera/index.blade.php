@extends('adminlte::page')

@section('title', $configuracion['titulo'])

@section('content_header')
    <h1>{{ $configuracion['titulo'] }}</h1>
@stop

@section('content')
    {{-- Incluye las inscripciones de personas con más de una carrera activa en la gestión seleccionada. --}}
    @include('reportes.inscripciones.formulario')
@stop
