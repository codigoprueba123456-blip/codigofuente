@extends('adminlte::page')

@section('title', 'Inscriptos')

@section('content_header')
    <h1 class="m-0 text-dark">Gestión de Inscriptos</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info shadow-sm">
                <div class="inner">
                    <h3>{{ $totalInscripciones }}</h3>
                    <p>Total de Inscripciones</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success shadow-sm">
                <div class="inner">
                    <h3>{{ $totalEstudiantes }}</h3>
                    <p>Estudiantes</p>
                </div>
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning shadow-sm">
                <div class="inner">
                    <h3>{{ $totalBecados }}</h3>
                    <p>Inscripciones con Beca</p>
                </div>
                <div class="icon"><i class="fas fa-award"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-indigo shadow-sm">
                <div class="inner">
                    <h3>{{ $multiplesCarrerasCount }}</h3>
                    <p>Estudiantes en Múltiples Carreras</p>
                </div>
                <div class="icon"><i class="fas fa-layer-group"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header">
            <h3 class="card-title font-weight-bold text-primary">
                <i class="fas fa-filter mr-1"></i> Filtros de Inscripción
            </h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('inscriptos.index') }}">
                <div class="row">
                    <div class="col-12 col-md-4 mb-3">
                        <label for="id_gestionacademica" class="font-weight-bold">Gestión Académica</label>
                        <select name="id_gestionacademica" id="id_gestionacademica" class="form-control custom-select">
                            <option value="">-- Todas las gestiones --</option>
                            @foreach ($gestiones as $gestion)
                                <option value="{{ $gestion->id_gestionacademica }}" {{ request('id_gestionacademica') == $gestion->id_gestionacademica ? 'selected' : '' }}>
                                    Gestión {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="id_carrera" class="font-weight-bold">Carrera</label>
                        <select name="id_carrera" id="id_carrera" class="form-control custom-select">
                            <option value="">-- Todas las carreras --</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}" {{ request('id_carrera') == $carrera->id_carrera ? 'selected' : '' }}>
                                    {{ $carrera->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label for="nivel" class="font-weight-bold">Nivel de inscripción</label>
                        <select name="nivel" id="nivel" class="form-control custom-select">
                            <option value="">-- Todos --</option>
                            @foreach ($niveles as $nivel)
                                <option value="{{ $nivel }}" {{ request('nivel') == $nivel ? 'selected' : '' }}>{{ $nivel }}°</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-3 mb-3">
                        <label for="bth" class="font-weight-bold">Certificado BTH</label>
                        <select name="bth" id="bth" class="form-control custom-select">
                            <option value="">Todos</option>
                            <option value="1" {{ request('bth') === '1' ? 'selected' : '' }}>Con certificado BTH</option>
                            <option value="0" {{ request('bth') === '0' ? 'selected' : '' }}>Sin certificado BTH</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 mb-3">
                        <label for="traspaso_convalidacion" class="font-weight-bold">Traspaso / convalidación</label>
                        <select name="traspaso_convalidacion" id="traspaso_convalidacion" class="form-control custom-select">
                            <option value="">Todos</option>
                            <option value="1" {{ request('traspaso_convalidacion') === '1' ? 'selected' : '' }}>Sí</option>
                            <option value="0" {{ request('traspaso_convalidacion') === '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 mb-3">
                        <label for="turno" class="font-weight-bold">Turno</label>
                        <select name="turno" id="turno" class="form-control custom-select">
                            <option value="">-- Todos --</option>
                            @foreach ($turnos as $turno)
                                <option value="{{ $turno }}" {{ request('turno') == $turno ? 'selected' : '' }}>{{ $turno }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 mb-3">
                        <label for="paralelo" class="font-weight-bold">Paralelo</label>
                        <select name="paralelo" id="paralelo" class="form-control custom-select">
                            <option value="">-- Todos --</option>
                            @foreach ($paralelos as $paralelo)
                                <option value="{{ $paralelo }}" {{ request('paralelo') == $paralelo ? 'selected' : '' }}>{{ $paralelo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter mr-1"></i> Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>

            <div class="row align-items-center mt-2">
                <form method="GET" action="{{ route('inscriptos.index') }}" class="col-12 col-xl-6 mb-2 mb-xl-0">
                    @foreach (request()->only(['id_gestionacademica', 'id_carrera', 'nivel', 'bth', 'traspaso_convalidacion', 'turno', 'paralelo']) as $nombre => $valor)
                        @if (is_scalar($valor) && $valor !== '')
                            <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
                        @endif
                    @endforeach
                    <div class="input-group">
                        <input type="text" name="buscar_rapido" value="{{ request('buscar_rapido') }}" class="form-control" placeholder="Buscar por CI o nombre completo...">
                        <div class="input-group-append">
                            <button class="btn btn-default border" type="submit" aria-label="Buscar">
                                <i class="fas fa-search text-muted"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="col-12 col-xl-6 d-flex flex-wrap justify-content-xl-end">
                    <a href="{{ route('inscriptos.index', array_merge(request()->only(['id_gestionacademica', 'id_carrera', 'nivel', 'bth', 'traspaso_convalidacion', 'turno', 'paralelo', 'buscar_rapido']), ['formato' => 'excel'])) }}" class="btn btn-success shadow-sm mr-1 mb-1">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </a>
                    <a href="{{ route('inscriptos.index', array_merge(request()->only(['id_gestionacademica', 'id_carrera', 'nivel', 'bth', 'traspaso_convalidacion', 'turno', 'paralelo', 'buscar_rapido']), ['formato' => 'pdf'])) }}" target="_blank" rel="noopener" class="btn btn-danger shadow-sm mr-1 mb-1">
                        <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                    </a>
                    <a href="{{ route('inscriptos.index') }}" class="btn btn-default shadow-sm mb-1" title="Limpiar filtros">
                        <i class="fas fa-list mr-1"></i> Lista completa
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-nowrap mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center">Nº</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Primer Apellido</th>
                            <th>Segundo Apellido</th>
                            <th>Nombre</th>
                            <th>CI / DNI</th>
                            <th>Celular</th>
                            <th>Carrera</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inscritos as $index => $inscrito)
                            <tr>
                                <td class="text-center">{{ $inscritos->firstItem() + $index }}</td>
                                <td><span class="badge badge-secondary">{{ $inscrito->codigo ?? 'S/C' }}</span></td>
                                <td>{{ $inscrito->fecha ? date('d/m/Y', strtotime($inscrito->fecha)) : '' }}</td>
                                <td class="font-weight-bold">{{ $inscrito->primerapellido }}</td>
                                <td>{{ $inscrito->segundoapellido ?? '' }}</td>
                                <td class="font-weight-bold">{{ $inscrito->nombre }}</td>
                                <td>{{ $inscrito->dni ?? '—' }}</td>
                                <td>
                                    @if ($inscrito->celular)
                                        <a href="https://wa.me/{{ $inscrito->celular }}" target="_blank" rel="noopener" class="text-success">
                                            <i class="fab fa-whatsapp mr-1"></i>{{ $inscrito->celular }}
                                        </a>
                                    @else
                                        <span class="text-muted">Sin celular</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $inscrito->carrera ?? 'Sin carrera' }}
                                    @if ($inscrito->carreras_inscritas->count() > 1)
                                        <span class="badge badge-warning ml-1" title="Estudiante inscrito en varias carreras en esta gestión">
                                            Multi-carrera
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#modalExpediente{{ $inscrito->id_inscrito }}" title="Ver expediente">
                                        <i class="fas fa-eye"></i> Expediente
                                    </button>
                                    @if ($inscrito->carreras_inscritas->count() > 1)
                                        <button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#modalCarreras{{ $inscrito->id_inscrito }}" title="Ver carreras de este estudiante">
                                            <i class="fas fa-layer-group"></i> {{ $inscrito->carreras_inscritas->count() }} carreras
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle mr-1"></i> No se encontraron inscripciones con esos criterios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
            <small class="text-muted mb-2 mb-md-0">
                Mostrando del {{ $inscritos->firstItem() ?? 0 }} al {{ $inscritos->lastItem() ?? 0 }} de {{ $inscritos->total() }} inscripciones
            </small>
            {{ $inscritos->links('pagination::bootstrap-4') }}
        </div>
    </div>

    @foreach ($inscritos as $inscrito)
        @php
            $documentos = [
                'CI anverso' => $inscrito->path_ci,
                'CI reverso' => $inscrito->path_ci_reverso,
                'Certificado de nacimiento' => $inscrito->path_nacimiento,
                'Diploma de bachiller' => $inscrito->path_diploma,
            ];
            if ($inscrito->path_bth) {
                $documentos['Certificado BTH'] = $inscrito->path_bth;
            }
        @endphp
        <div class="modal fade" id="modalExpediente{{ $inscrito->id_inscrito }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-folder-open mr-2"></i>
                            Expediente: {{ $inscrito->primerapellido }} {{ $inscrito->segundoapellido }} {{ $inscrito->nombre }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 border-right">
                                <div class="text-center mb-3">
                                    @if ($inscrito->foto)
                                        <img src="{{ asset('storage/'.$inscrito->foto) }}" class="img-circle elevation-2" alt="Foto del estudiante" style="width: 100px; height: 100px; object-fit: cover;">
                                    @else
                                        <div class="elevation-2 d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded-circle" style="width: 100px; height: 100px;">
                                            <i class="fas fa-user-graduate fa-3x"></i>
                                        </div>
                                    @endif
                                </div>
                                <p><strong>DNI:</strong> {{ $inscrito->dni ?? 'No registrado' }}</p>
                                <p><strong>Código:</strong> {{ $inscrito->codigo ?? 'S/C' }}</p>
                                <p><strong>Gestión:</strong> {{ $inscrito->gestion }} - {{ $inscrito->periodo }}</p>
                                <p><strong>Carrera:</strong> {{ $inscrito->carrera ?? 'Sin carrera' }}</p>
                                @if ($inscrito->celular)
                                    <a href="https://wa.me/{{ $inscrito->celular }}" target="_blank" rel="noopener" class="btn btn-success btn-block btn-sm">
                                        <i class="fab fa-whatsapp mr-1"></i> Contactar por WhatsApp
                                    </a>
                                @endif
                            </div>
                            <div class="col-md-8">
                                <ul class="nav nav-tabs" role="tablist">
                                    @foreach ($documentos as $titulo => $ruta)
                                        <li class="nav-item">
                                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#documento-{{ $inscrito->id_inscrito }}-{{ $loop->index }}" role="tab">
                                                {{ $titulo }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="tab-content p-3 border border-top-0 bg-light">
                                    @foreach ($documentos as $titulo => $ruta)
                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }} text-center" id="documento-{{ $inscrito->id_inscrito }}-{{ $loop->index }}" role="tabpanel">
                                            <p class="text-muted">{{ $titulo }}</p>
                                            <div class="border bg-white p-2" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                                                @if ($ruta)
                                                    <img src="{{ asset('storage/'.$ruta) }}" class="img-fluid" style="max-height: 300px;" alt="{{ $titulo }}">
                                                @else
                                                    <span class="text-muted font-italic">Documento no adjuntado</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($inscrito->carreras_inscritas->count() > 1)
            <div class="modal fade" id="modalCarreras{{ $inscrito->id_inscrito }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title"><i class="fas fa-layer-group mr-2"></i>Inscripciones en varias carreras</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">{{ $inscrito->primerapellido }} {{ $inscrito->segundoapellido }} {{ $inscrito->nombre }}</p>
                            <p class="text-muted">Carreras registradas en Gestión {{ $inscrito->gestion }} - Periodo {{ $inscrito->periodo }}:</p>
                            <ul class="mb-0">
                                @foreach ($inscrito->carreras_inscritas as $nombreCarrera)
                                    <li>{{ $nombreCarrera }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@stop
