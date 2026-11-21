@extends('adminlte::page')

@section('title', $configuracion['titulo'])

@section('content_header')
    <h1>{{ $configuracion['titulo'] }}</h1>
@stop

@section('content')
    {{-- Filtra las asignaciones por nivel, turno y letra final del código del grupo. --}}
    @include('reportes.inscripciones.formulario')
@stop
