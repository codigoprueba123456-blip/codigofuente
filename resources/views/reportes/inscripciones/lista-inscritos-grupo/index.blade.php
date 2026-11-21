@extends('adminlte::page')

@section('title', $configuracion['titulo'])

@section('content_header')
    <h1>{{ $configuracion['titulo'] }}</h1>
@stop

@section('content')
    {{-- Presenta asignaciones de estudiantes al grupo elegido dentro de la carrera, gestión y nivel indicados. --}}
    @include('reportes.inscripciones.formulario')
@stop
