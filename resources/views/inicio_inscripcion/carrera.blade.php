@extends('adminlte::page')

@section('title', $carrera->nombreConResolucionMinisterial().' · '.$gestion->gestion)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <a href="{{ route('inicio_inscripcion.gestion', $gestion->id_gestionacademica) }}" class="text-muted small">
                <i class="fas fa-arrow-left mr-1"></i> Volver a la gestión {{ $gestion->gestion }}
            </a>
            <h1 class="mt-2 mb-1">
                <i class="fas fa-graduation-cap text-success mr-2"></i>{{ $carrera->nombreConResolucionMinisterial() }}
            </h1>
            <p class="text-muted mb-0">
                Gestión {{ $gestion->gestion }} · {{ $carrera->codigo ?? 'Sin código' }}
            </p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('admin.grupos.index', ['id_gestionacademica' => $gestion->id_gestionacademica, 'id_carrera' => $carrera->id_carrera]) }}"
                class="btn btn-outline-primary">
                <i class="fas fa-list mr-1"></i> Abrir grupos existentes
            </a>
            @if ($puedeProgramarGrupos)
                <a href="{{ route('admin.grupos.programar') }}" class="btn btn-primary">
                    <i class="fas fa-users-cog mr-1"></i> Programar en módulo actual
                </a>
            @endif
        </div>
    </div>
@stop

@section('content')
    @php
        $regimenCarrera = (int) $carrera->regimen === 2 ? 'Semestral' : 'Anual';
    @endphp

    <div class="container-fluid px-0">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            La resolución y el régimen corresponden a la ficha actual de la carrera; el sistema no guarda una copia histórica por gestión.
        </div>

        <div class="row">
            <div class="col-lg-8">
                <h2 class="h4 mb-3">
                    <i class="fas fa-users text-success mr-1"></i> Paralelos y turnos
                </h2>
                <div class="row">
                    @foreach ($paralelosPorTurno as $paraleloIndex => $paralelo)
                        <div class="col-md-6">
                            <article class="card border shadow-sm mb-3 h-100">
                                <div class="card-body d-flex align-items-center">
                                    <span class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mr-3"
                                        style="width: 44px; height: 44px; flex: 0 0 44px;">
                                        <i class="fas fa-users"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <a href="{{ route('inicio_inscripcion.paralelo', [
                                            $gestion->id_gestionacademica,
                                            $carrera->id_carrera,
                                            $paralelo['nombre'],
                                            (int) ($paralelo['turno'] ?? 0),
                                        ]) }}" class="h5 text-dark font-weight-bold d-block mb-1">
                                            Paralelo {{ $paralelo['nombre'] }}
                                        </a>
                                        <span class="badge badge-info">
                                            @switch((int) $paralelo['turno'])
                                                @case(1) Mañana @break
                                                @case(2) Tarde @break
                                                @case(3) Noche @break
                                                @default Sin turno registrado
                                            @endswitch
                                        </span>
                                        <span class="badge badge-light border ml-1">
                                            {{ $paralelo['grupos']->count() }} {{ \Illuminate\Support\Str::plural('materia', $paralelo['grupos']->count()) }}
                                        </span>
                                    </div>
                                    <a href="{{ route('inicio_inscripcion.paralelo', [
                                        $gestion->id_gestionacademica,
                                        $carrera->id_carrera,
                                        $paralelo['nombre'],
                                        (int) ($paralelo['turno'] ?? 0),
                                    ]) }}" class="btn btn-sm btn-outline-primary ml-2">
                                        <i class="fas fa-arrow-right"></i>
                                        <span class="sr-only">Ver materias del paralelo {{ $paralelo['nombre'] }}</span>
                                    </a>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header">
                        <h2 class="card-title font-weight-bold"><i class="fas fa-clipboard-list mr-1"></i> Resumen</h2>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-7">Gestión</dt>
                            <dd class="col-5">{{ $gestion->etiqueta }}</dd>
                            <dt class="col-7">Régimen de carrera</dt>
                            <dd class="col-5">{{ $regimenCarrera }}</dd>
                            <dt class="col-7">Materias con grupos</dt>
                            <dd class="col-5">{{ $cantidadMaterias }}</dd>
                            <dt class="col-7">Grupos registrados</dt>
                            <dd class="col-5">{{ $cantidadGrupos }}</dd>
                            <dt class="col-7">Turnos distintos</dt>
                            <dd class="col-5">{{ $cantidadTurnos }}</dd>
                        </dl>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="{{ route('planes.carreras.index') }}" class="btn btn-sm btn-outline-secondary btn-block">
                            <i class="fas fa-external-link-alt mr-1"></i> Ver ficha de carrera existente
                        </a>
                        @if ($puedeEditarGrupos)
                            <p class="small text-muted mt-2 mb-0">
                                La edición se abrirá en el formulario original de grupos y respeta sus permisos y validaciones.
                            </p>
                        @else
                            <p class="small text-muted mt-2 mb-0">
                                Esta gestión no está habilitada para editar grupos. El panel solo muestra los datos existentes.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
