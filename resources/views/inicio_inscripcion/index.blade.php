@extends('adminlte::page')

@section('title', 'Inicio de Inscripción')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1"><i class="fas fa-door-open text-primary mr-2"></i>Inicio de Inscripción</h1>
            <p class="text-muted mb-0">Organiza gestiones y consulta la oferta académica registrada.</p>
        </div>
        <a href="{{ route('admin.gestionacademica.create') }}" class="btn btn-primary mt-2 mt-md-0">
            <i class="fas fa-plus mr-1"></i> Crear gestión
        </a>
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">
        <div class="row">
            @foreach ([
                ['label' => 'Gestiones registradas', 'value' => $gestiones->count(), 'icon' => 'fa-calendar-alt', 'color' => 'primary'],
                ['label' => 'Carreras activas', 'value' => $cantidadCarreras, 'icon' => 'fa-graduation-cap', 'color' => 'success'],
                ['label' => 'Materias activas', 'value' => $cantidadMaterias, 'icon' => 'fa-book-open', 'color' => 'info'],
                ['label' => 'Grupos registrados', 'value' => $cantidadGrupos, 'icon' => 'fa-users', 'color' => 'warning'],
            ] as $resumen)
                <div class="col-6 col-xl-3">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-{{ $resumen['color'] }} elevation-1">
                            <i class="fas {{ $resumen['icon'] }}"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ $resumen['label'] }}</span>
                            <span class="info-box-number">{{ $resumen['value'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 font-weight-bold mb-3">
                    <i class="fas fa-bolt text-warning mr-1"></i> Accesos rápidos
                </h2>
                <div class="row">
                    <div class="col-sm-6 col-xl-3 mb-2">
                        <a href="{{ route('planes.carreras.index') }}" class="btn btn-outline-success btn-block text-left">
                            <i class="fas fa-graduation-cap mr-2"></i> Carreras
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-2">
                        <a href="{{ route('planes.materias.index') }}" class="btn btn-outline-info btn-block text-left">
                            <i class="fas fa-book-open mr-2"></i> Materias
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-2">
                        <a href="{{ route('admin.grupos.index') }}" class="btn btn-outline-primary btn-block text-left">
                            <i class="fas fa-users mr-2"></i> Grupos y paralelos
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3 mb-2">
                        <a href="{{ route('admin.actividad.index') }}" class="btn btn-outline-secondary btn-block text-left">
                            <i class="fas fa-calendar-check mr-2"></i> Calendario y actividades
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h2 class="card-title font-weight-bold mb-1">
                        <i class="fas fa-layer-group mr-1"></i> Gestiones académicas
                    </h2>
                    <div class="text-muted small">Selecciona un año para revisar sus carreras, materias y grupos registrados.</div>
                </div>
                <a href="{{ route('admin.gestionacademica.index') }}" class="btn btn-sm btn-outline-primary mt-2 mt-md-0">
                    <i class="fas fa-sliders-h mr-1"></i> Administrar gestiones
                </a>
            </div>

            <div class="card-body">
                @if ($gestiones->isEmpty())
                    <div class="text-center py-5">
                        <i class="far fa-calendar-plus fa-3x text-muted mb-3"></i>
                        <h3 class="h5">Todavía no hay gestiones registradas</h3>
                        <p class="text-muted">Crea una gestión desde el formulario existente para que aparezca en este panel.</p>
                        <a href="{{ route('admin.gestionacademica.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Crear gestión
                        </a>
                    </div>
                @else
                    <div class="row">
                        @foreach ($gestiones as $gestion)
                            @php
                                $regimen = match ((int) $gestion->regimen) {
                                    1 => 'Anual',
                                    2 => 'Semestral',
                                    default => 'No especificado',
                                };
                            @endphp
                            <div class="col-md-6 col-xl-4 d-flex">
                                <article class="card border shadow-sm w-100 mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mr-3"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="fas fa-calendar-check"></i>
                                                </span>
                                                <div>
                                                    <div class="h3 mb-0 font-weight-bold">{{ $gestion->gestion }}</div>
                                                    <div class="text-muted">{{ $gestion->etiqueta }} · {{ $regimen }}</div>
                                                </div>
                                            </div>
                                            @if ($gestion->vigente && $gestion->activo)
                                                <span class="badge badge-success">Vigente</span>
                                            @elseif ($gestion->activo)
                                                <span class="badge badge-info">Habilitada</span>
                                            @else
                                                <span class="badge badge-secondary">Deshabilitada</span>
                                            @endif
                                        </div>

                                        <div class="row text-center border-top border-bottom py-3 mb-3">
                                            <div class="col-4 border-right">
                                                <div class="h5 mb-0">{{ $gestion->grupos_count }}</div>
                                                <small class="text-muted">Grupos</small>
                                            </div>
                                            <div class="col-4 border-right">
                                                <div class="h5 mb-0">{{ $gestion->actividades_count }}</div>
                                                <small class="text-muted">Actividades</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="h5 mb-0">{{ $gestion->periodo }}</div>
                                                <small class="text-muted">Período</small>
                                            </div>
                                        </div>

                                        <div class="small text-muted mb-3">
                                            <div><i class="far fa-calendar-alt mr-1"></i> Inicio:
                                                {{ $gestion->inicio ? \Illuminate\Support\Carbon::parse($gestion->inicio)->format('d/m/Y') : 'Sin fecha' }}
                                            </div>
                                            <div><i class="far fa-calendar-check mr-1"></i> Fin:
                                                {{ $gestion->fin ? \Illuminate\Support\Carbon::parse($gestion->fin)->format('d/m/Y') : 'Sin fecha' }}
                                            </div>
                                        </div>

                                        <a href="{{ route('inicio_inscripcion.gestion', $gestion->id_gestionacademica) }}"
                                            class="btn btn-outline-primary btn-block">
                                            <i class="fas fa-arrow-right mr-1"></i> Ver resumen de la gestión
                                        </a>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="alert alert-light border mt-3 mb-0">
            <i class="fas fa-info-circle text-primary mr-1"></i>
            Este panel resume los registros existentes. Para crear o editar gestiones, carreras, materias, actividades o grupos,
            utiliza sus formularios actuales.
        </div>
    </div>
@stop
