@extends('adminlte::page')

@section('title', 'Gestión '.$gestion->gestion)

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <a href="{{ route('inicio_inscripcion.index') }}" class="text-muted small">
                <i class="fas fa-arrow-left mr-1"></i> Volver al inicio
            </a>
            <h1 class="mt-2 mb-1">
                <i class="fas fa-calendar-alt text-primary mr-2"></i>Gestión {{ $gestion->gestion }}
            </h1>
            <p class="text-muted mb-0">
                {{ $gestion->etiqueta }} · {{ (int) $gestion->regimen === 2 ? 'Régimen semestral' : 'Régimen anual' }}
            </p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('admin.gestionacademica.edit', $gestion->id_gestionacademica) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit mr-1"></i> Editar gestión
            </a>
            @if ($puedeProgramarGrupos)
                <a href="{{ route('admin.grupos.programar') }}" class="btn btn-primary">
                    <i class="fas fa-users-cog mr-1"></i> Programar grupos
                </a>
            @endif
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header">
                        <h2 class="card-title font-weight-bold">
                            <i class="fas fa-graduation-cap mr-1"></i> Carreras de la gestión
                        </h2>
                    </div>
                    <div class="card-body">
                        @if ($carreras->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                                <h3 class="h5">No hay carreras ni grupos asociados a esta gestión</h3>
                                <p class="text-muted">
                                    Las carreras activas aparecerán aquí cuando estén registradas en el sistema.
                                </p>
                                <a href="{{ route('admin.grupos.index', ['id_gestionacademica' => $gestion->id_gestionacademica]) }}"
                                    class="btn btn-outline-primary">
                                    <i class="fas fa-list mr-1"></i> Abrir administración de grupos
                                </a>
                            </div>
                        @else
                            <p class="text-muted small">
                                Elige una carrera para consultar sus paralelos, turnos y materias.
                                La resolución mostrada es la registrada actualmente en su ficha.
                            </p>

                            @forelse ($carrerasActivasPorResolucion as $resolucion => $items)
                                <section class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                        <h3 class="h5 mb-0">
                                            <i class="fas fa-file-alt text-primary mr-1"></i>{{ $resolucion }}
                                        </h3>
                                        <span class="badge badge-success">{{ $items->count() }} carreras activas</span>
                                    </div>
                                    <div class="row">
                                        @foreach ($items as $item)
                                            @php
                                                $carrera = $item['carrera'];
                                                $regimenCarrera = (int) $carrera->regimen === 2 ? 'Semestral' : 'Anual';
                                            @endphp
                                            <div class="col-xl-6 d-flex">
                                                <article class="card border shadow-sm w-100 mb-3">
                                                    <div class="card-body py-3">
                                                        <div class="d-flex align-items-center">
                                                            <span class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mr-3"
                                                                style="width: 40px; height: 40px; flex: 0 0 40px;">
                                                                <i class="fas fa-graduation-cap"></i>
                                                            </span>
                                                            <div class="flex-grow-1">
                                                                <h4 class="h5 mb-1">{{ $carrera->nombreConResolucionMinisterial() }}</h4>
                                                                <div class="text-muted small">
                                                                    {{ $carrera->codigo ?? 'Sin código' }} · {{ $regimenCarrera }}
                                                                </div>
                                                            </div>
                                                            @if ($item['grupos']->isNotEmpty())
                                                                <a href="{{ route('inicio_inscripcion.carrera', [$gestion->id_gestionacademica, $carrera->id_carrera]) }}"
                                                                    class="btn btn-sm btn-outline-primary ml-2">
                                                                    <i class="fas fa-arrow-right"></i>
                                                                    <span class="sr-only">Ver paralelos y turnos de {{ $carrera->nombreConResolucionMinisterial() }}</span>
                                                                </a>
                                                            @endif
                                                        </div>

                                                        <div class="d-flex flex-wrap align-items-center mt-3">
                                                            <span class="badge badge-light border mr-2 mb-1">
                                                                {{ $item['materias']->count() }} materias
                                                            </span>
                                                            <span class="badge badge-light border mr-2 mb-1">
                                                                {{ $item['grupos']->count() }} grupos
                                                            </span>
                                                            @foreach ($item['turnos'] as $turno)
                                                                <span class="badge badge-info mr-1 mb-1">
                                                                    @switch((int) $turno)
                                                                        @case(1) Mañana @break
                                                                        @case(2) Tarde @break
                                                                        @case(3) Noche @break
                                                                        @default Turno {{ $turno }}
                                                                    @endswitch
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                        @if ($item['grupos']->isEmpty())
                                                            <div class="small text-muted mt-2">
                                                                Sin materias ni grupos en esta gestión.
                                                            </div>
                                                        @endif
                                                    </div>
                                                </article>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @empty
                                <div class="alert alert-light border">No hay carreras habilitadas con grupos en esta gestión.</div>
                            @endforelse

                            @if ($carrerasDeshabilitadasPorResolucion->isNotEmpty())
                                <details class="border rounded p-3 mt-4">
                                    <summary class="font-weight-bold text-secondary">
                                        <i class="fas fa-eye-slash mr-1"></i>
                                        Carreras deshabilitadas ({{ $carrerasDeshabilitadasPorResolucion->flatten(1)->count() }})
                                    </summary>
                                    <div class="pt-3">
                                        @foreach ($carrerasDeshabilitadasPorResolucion as $resolucion => $items)
                                            <h3 class="h6 text-muted mt-3">{{ $resolucion }}</h3>
                                            <div class="row">
                                                @foreach ($items as $item)
                                                    @php
                                                        $carrera = $item['carrera'];
                                                        $regimenCarrera = (int) $carrera->regimen === 2 ? 'Semestral' : 'Anual';
                                                    @endphp
                                                    <div class="col-xl-6 d-flex">
                                                        <article class="card border w-100 mb-3">
                                                            <div class="card-body">
                                                                <h4 class="h5 mb-1">{{ $carrera->nombreConResolucionMinisterial() }}</h4>
                                                                <div class="text-muted small mb-2">
                                                                    {{ $carrera->codigo ?? 'Sin código' }} · {{ $regimenCarrera }}
                                                                </div>
                                                                <span class="badge badge-secondary mb-2">Deshabilitada</span>
                                                                <div class="small text-muted">
                                                                    {{ $item['materias']->count() }} materias · {{ $item['grupos']->count() }} grupos
                                                                </div>
                                                                <a href="{{ route('inicio_inscripcion.carrera', [$gestion->id_gestionacademica, $carrera->id_carrera]) }}"
                                                                    class="btn btn-sm btn-outline-secondary btn-block mt-3">
                                                                    <i class="fas fa-eye mr-1"></i> Consultar grupos y cupos
                                                                </a>
                                                            </div>
                                                        </article>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-outline card-info shadow-sm">
                    <div class="card-header">
                        <h2 class="card-title font-weight-bold"><i class="fas fa-calendar-day mr-1"></i> Fechas y estado</h2>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5">Inicio</dt>
                            <dd class="col-7">{{ $gestion->inicio ? \Illuminate\Support\Carbon::parse($gestion->inicio)->format('d/m/Y') : 'Sin fecha' }}</dd>
                            <dt class="col-5">Fin</dt>
                            <dd class="col-7">{{ $gestion->fin ? \Illuminate\Support\Carbon::parse($gestion->fin)->format('d/m/Y') : 'Sin fecha' }}</dd>
                            <dt class="col-5">Vigencia</dt>
                            <dd class="col-7">{{ $gestion->vigente ? 'Vigente' : 'No vigente' }}</dd>
                            <dt class="col-5">Estado</dt>
                            <dd class="col-7">{{ $gestion->activo ? 'Habilitada' : 'Deshabilitada' }}</dd>
                        </dl>
                        <div class="alert alert-light border small mb-0">
                            Las fechas y estados se muestran tal como están registrados. Este panel no cambia ni calcula la habilitación.
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-secondary shadow-sm">
                    <div class="card-header">
                        <h2 class="card-title font-weight-bold"><i class="fas fa-calendar-check mr-1"></i> Actividades</h2>
                        <span class="badge badge-secondary float-right">{{ $actividades->count() }}</span>
                    </div>
                    <div class="card-body">
                        @forelse ($actividades as $actividad)
                            <div class="border-bottom pb-2 mb-2">
                                <div class="font-weight-bold">{{ $actividad->nombre }}</div>
                                <div class="small text-muted">{{ $actividad->descripcion ?: 'Sin descripción' }}</div>
                                <div class="small text-muted">
                                    {{ $actividad->inicio ? $actividad->inicio->format('d/m/Y H:i') : 'Sin inicio' }}
                                    &ndash;
                                    {{ $actividad->fin ? $actividad->fin->format('d/m/Y H:i') : 'Sin fin' }}
                                </div>
                                <span class="badge badge-{{ $actividad->activo ? 'success' : 'secondary' }}">
                                    {{ $actividad->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hay actividades registradas para esta gestión.</p>
                        @endforelse
                    </div>
                    <div class="card-footer bg-white">
                        <a href="{{ route('admin.actividad.index') }}" class="btn btn-sm btn-outline-secondary btn-block">
                            <i class="fas fa-external-link-alt mr-1"></i> Abrir calendario existente
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
