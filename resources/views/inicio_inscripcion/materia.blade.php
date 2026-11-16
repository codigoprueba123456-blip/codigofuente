@extends('adminlte::page')

@section('title', $materia->nombre.' · '.$carrera->nombreConResolucionMinisterial())

@section('content_header')
    <div>
        <a href="{{ route('inicio_inscripcion.paralelo', [
            $gestion->id_gestionacademica,
            $carrera->id_carrera,
            $paralelo,
            $turno,
        ]) }}" class="text-muted small">
            <i class="fas fa-arrow-left mr-1"></i> Volver al paralelo {{ $paralelo }}
        </a>
        <h1 class="mt-2 mb-1">
            <i class="fas fa-book text-primary mr-2"></i>{{ $materia->nombre }}
        </h1>
        <p class="text-muted mb-0">
            {{ $carrera->nombreConResolucionMinisterial() }} · Paralelo {{ $paralelo }} ·
            @switch($turno)
                @case(1) Mañana @break
                @case(2) Tarde @break
                @case(3) Noche @break
                @default Sin turno registrado
            @endswitch
            · Gestión {{ $gestion->gestion }}
        </p>
    </div>
@stop

@section('content')
    @php
        $nivel = $materia->nivel
            ? \App\Models\Carrera::etiquetaNivel($carrera->regimen, $materia->nivel)
            : 'Nivel no especificado';
        $resoluciones = $carrera->resolucionesMinisteriales();
    @endphp

    <div class="container-fluid px-0">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header">
                        <h2 class="card-title font-weight-bold">
                            <i class="fas fa-users mr-1"></i> Grupos y cupos
                        </h2>
                        <span class="badge badge-light border float-right">{{ $nivel }}</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Código de grupo</th>
                                        <th class="text-center">Cupo</th>
                                        <th class="text-center">Inscritos</th>
                                        <th class="text-center">Disponibles</th>
                                        <th>Estado</th>
                                        @if ($puedeEditarGrupos)
                                            <th class="text-center">Acción</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($grupos as $grupo)
                                        @php
                                            $disponibles = $grupo->capacidad === null
                                                ? null
                                                : max(0, (int) $grupo->capacidad - (int) $grupo->inscritos_count);
                                        @endphp
                                        <tr>
                                            <td class="font-weight-bold">{{ $grupo->codigo }}</td>
                                            <td class="text-center">{{ $grupo->capacidad ?? 'No registrado' }}</td>
                                            <td class="text-center">{{ $grupo->inscritos_count }}</td>
                                            <td class="text-center">{{ $disponibles ?? '—' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $grupo->activo ? 'success' : 'secondary' }}">
                                                    {{ $grupo->activo ? 'Habilitado' : 'Deshabilitado' }}
                                                </span>
                                                <span class="badge badge-{{ $grupo->abierto ? 'info' : 'dark' }}">
                                                    {{ $grupo->abierto ? 'Abierto' : 'Cerrado' }}
                                                </span>
                                            </td>
                                            @if ($puedeEditarGrupos)
                                                <td class="text-center">
                                                    <a href="{{ route('admin.grupos.edit', $grupo->id_grupo) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Editar en el módulo existente">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-outline card-secondary shadow-sm">
                    <div class="card-header">
                        <h2 class="card-title font-weight-bold">
                            <i class="fas fa-clipboard-list mr-1"></i> Datos de la materia
                        </h2>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5">Código</dt>
                            <dd class="col-7">{{ $materia->codigo ?? 'Sin código' }}</dd>
                            <dt class="col-5">Nivel</dt>
                            <dd class="col-7">{{ $nivel }}</dd>
                            <dt class="col-5">Carrera</dt>
                            <dd class="col-7">{{ $carrera->nombreConResolucionMinisterial() }}</dd>
                            <dt class="col-5">Régimen</dt>
                            <dd class="col-7">{{ (int) $carrera->regimen === 2 ? 'Semestral' : 'Anual' }}</dd>
                            <dt class="col-5">Resolución</dt>
                            <dd class="col-7">
                                {{ $resoluciones ? 'R.M. '.implode(' / ', $resoluciones) : 'Sin resolución registrada' }}
                            </dd>
                        </dl>
                    </div>
                    <div class="card-footer bg-white">
                        @if ($puedeEditarGrupos)
                            <p class="small text-muted mb-0">
                                La edición de grupos abre el formulario existente y conserva sus validaciones y permisos.
                            </p>
                        @else
                            <p class="small text-muted mb-0">
                                Esta gestión se muestra en modo de consulta.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
