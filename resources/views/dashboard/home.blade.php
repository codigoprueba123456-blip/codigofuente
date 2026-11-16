@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
    <p class="text-muted mb-0">
        Resumen general del sistema
        @if ($gestionActual)
            · Gestión {{ $gestionActual->gestion }} / Periodo {{ $gestionActual->periodo }}
        @endif
    </p>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($estadisticas['inscripciones']) }}</h3>
                    <p>Inscripciones activas</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($estadisticas['postulantes']) }}</h3>
                    <p>Postulantes</p>
                </div>
                <div class="icon"><i class="fas fa-file-signature"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($estadisticas['carreras']) }}</h3>
                    <p>Carreras activas</p>
                </div>
                <div class="icon"><i class="fas fa-graduation-cap"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($estadisticas['grupos']) }}</h3>
                    <p>Grupos activos</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i>Inscripciones por carrera</h3>
                </div>
                <div class="card-body">
                    @forelse ($inscripcionesPorCarrera as $carrera)
                        <div class="progress-group">
                            {{ $carrera->carrera }}
                            <span class="float-right"><b>{{ number_format($carrera->estudiantes) }}</b></span>
                            <div class="progress progress-sm">
                                <div
                                    class="progress-bar bg-success"
                                    style="width: {{ min(100, (int) round($carrera->estudiantes * 100 / $maxEstudiantesCarrera)) }}%;"
                                ></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No hay inscripciones activas para mostrar.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="far fa-calendar-alt mr-1"></i>Gestión académica</h3>
                </div>
                <div class="card-body">
                    @if ($gestionActual)
                        <h4 class="mb-1">{{ $gestionActual->gestion }}</h4>
                        <p class="text-muted mb-2">Periodo {{ $gestionActual->periodo }}</p>
                        <span class="badge badge-success">Gestión activa</span>
                    @else
                        <p class="text-muted mb-0">No hay una gestión académica activa.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
