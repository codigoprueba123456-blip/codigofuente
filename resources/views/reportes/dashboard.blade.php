@extends('adminlte::page')

@section('title', 'Dashboard de reportes académicos')

@section('content_header')
    <h1>Reporte estadístico académico por carrera</h1>
    <p class="text-muted mb-0">Inscritos agrupados por carrera, curso, paralelo y turno.</p>
@stop

@section('content')
    {{-- La gestión mostrada acompaña el filtro elegido; si no se envió uno, se usa la gestión vigente del controlador. --}}
    @php
        $gestionSeleccionada = $gestiones->firstWhere(
            'id_gestionacademica',
            (int) ($filters['id_gestionacademica'] ?? $gestionActual?->id_gestionacademica)
        );
    @endphp

    {{-- Indicadores del conjunto filtrado: inscripciones activas, carreras y combinaciones carrera/curso/paralelo/turno. --}}
    <div class="row">
        <div class="col-md-4 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-user-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Inscripciones activas</span>
                    <span class="info-box-number">{{ number_format($estadisticas['inscripciones']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-graduation-cap"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Carreras en el reporte</span>
                    <span class="info-box-number">{{ number_format($estadisticas['carreras']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Combinaciones paralelo / turno</span>
                    <span class="info-box-number">{{ number_format($estadisticas['combinaciones']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Los filtros actualizan el resumen; la carrera es opcional y permite consultar todas las carreras. --}}
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-filter mr-1"></i> Selección del reporte
                @if ($gestionSeleccionada)
                    <span class="text-muted">· Gestión {{ $gestionSeleccionada->gestion }} / Periodo {{ $gestionSeleccionada->periodo }}</span>
                @endif
            </h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.dashboard') }}" class="form-row align-items-end">
                <div class="form-group col-md-5">
                    <label for="id_gestionacademica">Gestión académica</label>
                    <select name="id_gestionacademica" id="id_gestionacademica" class="form-control">
                        @if ($gestiones->isEmpty())
                            <option value="">No hay gestiones disponibles</option>
                        @endif
                        @foreach ($gestiones as $gestion)
                            <option value="{{ $gestion->id_gestionacademica }}" @selected((string) ($filters['id_gestionacademica'] ?? '') === (string) $gestion->id_gestionacademica)>
                                {{ $gestion->gestion }} · Periodo {{ $gestion->periodo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-5">
                    <label for="id_carrera">Carrera</label>
                    <select name="id_carrera" id="id_carrera" class="form-control">
                        <option value="">Todas las carreras</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id_carrera }}" @selected((string) ($filters['id_carrera'] ?? '') === (string) $carrera->id_carrera)>
                                {{ $carrera->nombreConResolucionMinisterial() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <button class="btn btn-success btn-block" type="submit">
                        <i class="fas fa-search mr-1"></i> Generar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- La exportación conserva los filtros vigentes y replica la estructura de la tabla. --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-table mr-1"></i> Inscritos por carrera, paralelo y turno
            </h3>
            <div class="card-tools">
                <a href="{{ route('reportes.dashboard', array_merge(request()->query(), ['formato' => 'excel'])) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2">
                <i class="fas fa-info-circle mr-1"></i>
                V: varones · M: mujeres · T: total. Las columnas de aprobados, reprobados, abandono y efectivos se dejan en blanco porque el sistema no almacena esos resultados académicos.
                El Excel respeta la gestión y la carrera seleccionadas y conserva la estructura de esta tabla.
            </div>

            {{-- Solo se calculan los conteos de inscritos por sexo; las otras categorías se dejan vacías porque no hay resultados académicos almacenados. --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm text-center">
                    <thead class="thead-light">
                        <tr>
                            <th rowspan="2" class="align-middle">Carrera</th>
                            <th rowspan="2" class="align-middle">Curso</th>
                            <th rowspan="2" class="align-middle">Paralelo</th>
                            <th rowspan="2" class="align-middle">Turno</th>
                            <th colspan="3">Inscritos</th>
                            <th colspan="3">Aprobados</th>
                            <th colspan="3">Reprobados</th>
                            <th colspan="3">Abandono</th>
                            <th colspan="3">Efectivos</th>
                        </tr>
                        <tr>
                            @for ($i = 0; $i < 5; $i++)
                                <th>V</th>
                                <th>M</th>
                                <th>T</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filasTabla as $fila)
                            <tr @class([
                                'table-primary font-weight-bold' => $fila->tipo_fila === 'subtotal',
                                'table-info font-weight-bold' => $fila->tipo_fila === 'total',
                            ])>
                                <td class="text-left">{{ $fila->carrera }}</td>
                                <td>{{ $fila->nivel }}</td>
                                <td>{{ $fila->paralelo }}</td>
                                <td>{{ $fila->turno }}</td>
                                <td>{{ $fila->inscritos_v }}</td>
                                <td>{{ $fila->inscritos_m }}</td>
                                <td>{{ $fila->inscritos_t }}</td>
                                @for ($i = 0; $i < 12; $i++)
                                    <td>&nbsp;</td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="19" class="text-center text-muted py-4">
                                    No hay inscripciones activas para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
