@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Bienvenido a {{ config('app.name', 'ITSA') }}</h1>
@stop

@section('content')
    <p>Panel de gestión académica del instituto.</p>
@stop