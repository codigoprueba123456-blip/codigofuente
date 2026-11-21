@extends('adminlte::page')

@section('title', $configuracion['titulo'])

@section('content_header')
    <h1>{{ $configuracion['titulo'] }}</h1>
@stop

@section('content')
    {{-- Este libro restringe el listado a una gestión académica y una carrera. --}}
    @include('reportes.inscripciones.formulario')
@stop
