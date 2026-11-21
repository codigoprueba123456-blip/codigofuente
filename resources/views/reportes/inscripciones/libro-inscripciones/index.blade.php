@extends('adminlte::page')

@section('title', $configuracion['titulo'])

@section('content_header')
    <h1>{{ $configuracion['titulo'] }}</h1>
@stop

@section('content')
    {{-- El libro general agrupa inscripciones por gestión y periodo; comparte el formulario, tabla y exportaciones del módulo. --}}
    @include('reportes.inscripciones.formulario')
@stop
