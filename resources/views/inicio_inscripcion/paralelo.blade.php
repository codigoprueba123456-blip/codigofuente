@extends('adminlte::page')

@section('title', 'Paralelo '.$paralelo.' · '.$carrera->nombreConResolucionMinisterial())

@section('content_header')
    <div>
        <a href="{{ route('inicio_inscripcion.carrera', [$gestion->id_gestionacademica, $carrera->id_carrera]) }}"
            class="text-muted small">
            <i class="fas fa-arrow-left mr-1"></i> Volver a {{ $carrera->nombreConResolucionMinisterial() }}
        </a>
        <h1 class="mt-2 mb-1">
            <i class="fas fa-users text-success mr-2"></i>Paralelo {{ $paralelo }}
            <span class="badge badge-info align-middle">
                @switch($turno)
                    @case(1) Mañana @break
                    @case(2) Tarde @break
                    @case(3) Noche @break
                    @default Sin turno registrado
                @endswitch
            </span>
        </h1>
        <p class="text-muted mb-0">{{ $carrera->nombreConResolucionMinisterial() }} · Gestión {{ $gestion->gestion }}</p>
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">
        <div class="alert alert-light border">
            <i class="fas fa-info-circle text-primary mr-1"></i>
            Selecciona una materia para ver sus grupos, cupos e inscritos.
        </div>

        <div class="row">
            @foreach ($materias as $item)
                @php
                    $materia = $item['materia'];
                    $nivel = $materia->nivel
                        ? \App\Models\Carrera::etiquetaNivel($carrera->regimen, $materia->nivel)
                        : 'Nivel no especificado';
                    $grupo = $item['grupos']->first();
                @endphp
                <div class="col-md-6 col-xl-4 d-flex">
                    <article class="card border shadow-sm w-100 mb-3">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start mb-3">
                                <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mr-3"
                                    style="width: 42px; height: 42px; flex: 0 0 42px;">
                                    <i class="fas fa-book"></i>
                                </span>
                                <div>
                                    <h2 class="h5 mb-1">{{ $materia->nombre }}</h2>
                                    <div class="text-muted small">{{ $materia->codigo ?? 'Sin código' }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <span class="badge badge-light border">{{ $nivel }}</span>
                                @if ($item['grupos']->count() === 1)
                                    <span class="badge badge-light border">
                                        Cupo: {{ $grupo->capacidad ?? 'No registrado' }}
                                    </span>
                                @else
                                    <span class="badge badge-light border">
                                        {{ $item['grupos']->count() }} grupos
                                    </span>
                                @endif
                            </div>

                            <a href="{{ route('inicio_inscripcion.materia', [
                                $gestion->id_gestionacademica,
                                $carrera->id_carrera,
                                $paralelo,
                                $turno,
                                $materia->id_materia,
                            ]) }}" class="btn btn-outline-primary btn-block mt-auto">
                                <i class="fas fa-arrow-right mr-1"></i> Ver materia
                            </a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
@stop
