@extends('adminlte::page')

@section('title', 'Lista de Postulantes')

@section('content_header')
    <h1 class="m-0 text-dark">Gestión de Postulantes</h1>
@stop

@section('content')

    {{-- TARJETAS DE ESTADÍSTICAS (4 KPI CARDS) --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info shadow-sm">
                <div class="inner">
                    <h3>{{ $totalPostulantes ?? 0 }}</h3>
                    <p>Total Registrados</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success shadow-sm">
                <div class="inner">
                    <h3>{{ $conCelular ?? 0 }}</h3>
                    <p>Contacto WhatsApp</p>
                </div>
                <div class="icon"><i class="fab fa-whatsapp"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning shadow-sm">
                <div class="inner">
                    <h3>{{ $conDocumentos ?? 0 }}</h3>
                    <p>Archivos Adjuntos</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-indigo shadow-sm">
                <div class="inner">
                    <h3>{{ $multiplesCarrerasCount ?? 0 }}</h3>
                    <p>Múltiples Carreras</p>
                </div>
                <div class="icon"><i class="fas fa-layer-group"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary shadow-sm">
        <div class="card-body">

            {{-- FILTROS DE GESTIÓN, CARRERA Y CATEGORÍA --}}
            <form method="GET" action="{{ route('admisiones.postulantes.index') }}" id="formFiltros">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="id_gestionacademica" class="font-weight-bold">Gestión Académica:</label>
                        <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Todas las Gestiones --</option>
                            @foreach ($gestiones as $g)
                                <option value="{{ $g->id_gestionacademica }}" {{ request('id_gestionacademica') == $g->id_gestionacademica ? 'selected' : '' }}>
                                    Gestión {{ $g->gestion }} - Periodo {{ $g->periodo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="id_carrera" class="font-weight-bold">Carrera:</label>
                        <select name="id_carrera" id="id_carrera" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Todas las Carreras --</option>
                            @foreach ($carreras as $c)
                                <option value="{{ $c->id_carrera }}" {{ request('id_carrera') == $c->id_carrera ? 'selected' : '' }}>
                                    {{ $c->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="categoria" class="font-weight-bold">Categoría de Postulación:</label>
                        <select name="categoria" id="categoria" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Todas las Categorías --</option>
                            <option value="multiples" {{ request('categoria') == 'multiples' ? 'selected' : '' }}>Postulantes en Múltiples Carreras (2+)</option>
                            <option value="unica" {{ request('categoria') == 'unica' ? 'selected' : '' }}>Postulantes en Carrera Única</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="estado" class="font-weight-bold">Estado:</label>
                        <select name="estado" id="estado" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Todos los estados --</option>
                            <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente de inscripción</option>
                            <option value="inscrito" {{ request('estado') === 'inscrito' ? 'selected' : '' }}>Inscrito como estudiante</option>
                        </select>
                    </div>
                </div>
            </form>

            {{-- BÚSQUEDA Y EXPORTACIÓN --}}
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <form method="GET" action="{{ route('admisiones.postulantes.index') }}" class="input-group col-md-6 p-0 mb-2 mb-md-0">
                    @if (request('id_gestionacademica'))
                        <input type="hidden" name="id_gestionacademica" value="{{ request('id_gestionacademica') }}">
                    @endif
                    @if (request('id_carrera'))
                        <input type="hidden" name="id_carrera" value="{{ request('id_carrera') }}">
                    @endif
                    @if (request('categoria'))
                        <input type="hidden" name="categoria" value="{{ request('categoria') }}">
                    @endif
                    @if (request('estado'))
                        <input type="hidden" name="estado" value="{{ request('estado') }}">
                    @endif

                    <input type="text" name="buscar_rapido" value="{{ request('buscar_rapido') }}" class="form-control" placeholder="Buscar por nombre o CI...">
                    <div class="input-group-append">
                        <button class="btn btn-default border" type="submit">
                            <i class="fas fa-search text-muted"></i>
                        </button>
                    </div>
                </form>

                <div class="mb-2 mb-md-0">
                    <a href="{{ route('admisiones.postulantes.index', array_merge(request()->all(), ['exportar' => 'filtrado', 'formato' => 'excel'])) }}" class="btn btn-success shadow-sm mr-1">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                    </a>
                    <a href="{{ route('admisiones.postulantes.index', array_merge(request()->all(), ['exportar' => 'filtrado', 'formato' => 'pdf'])) }}" target="_blank" class="btn btn-danger shadow-sm">
                        <i class="fas fa-print mr-1"></i> Imprimir PDF
                    </a>
                </div>
            </div>

            {{-- TABLA DE POSTULANTES --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover text-nowrap">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%" class="text-center">Nº</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Gestión / Periodo</th>
                            <th>Apellidos y Nombres</th>
                            <th>CI</th>
                            <th>Celular</th>
                            <th>Carrera</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($postulantes as $index => $p)
                            <tr>
                                <td class="text-center">{{ $postulantes->firstItem() + $index }}</td>
                                <td><span class="badge badge-secondary">{{ $p->codigo ?? 'S/C' }}</span></td>
                                <td>{{ $p->fecha ? date('d/m/Y', strtotime($p->fecha)) : '' }}</td>
                                <td>{{ $p->gestionAcademica?->gestion ?? '—' }} / {{ $p->gestionAcademica?->periodo ?? '—' }}</td>
                                <td class="font-weight-bold">
                                    {{ $p->primerapellido }} {{ $p->segundoapellido ?? '' }}, {{ $p->nombre }}
                                </td>
                                <td>{{ $p->ci }}</td>
                                <td>
                                    @if (!empty($p->celular))
                                        <a href="https://wa.me/{{ $p->celular }}" target="_blank" class="text-success">
                                            <i class="fab fa-whatsapp mr-1"></i> {{ $p->celular }}
                                        </a>
                                    @else
                                        <span class="text-muted">Sin celular</span>
                                    @endif
                                </td>
                                <td>{{ $p->carrera->nombre ?? 'Sin Carrera' }}</td>
                                <td>
                                    @if ($p->inscripcion_origen_exists)
                                        <span class="badge badge-success">Inscrito como estudiante</span>
                                    @else
                                        <span class="badge badge-secondary">Pendiente de inscripción</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#modalExpediente{{ $p->id_postulante ?? $loop->index }}" title="Ver Documentos">
                                        <i class="fas fa-eye"></i> Ver Documentos
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle mr-1"></i> No se encontraron registros de postulantes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINACIÓN --}}
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                <small class="text-muted mb-2 mb-md-0">
                    Mostrando del {{ $postulantes->firstItem() ?? 0 }} al {{ $postulantes->lastItem() ?? 0 }} de {{ $postulantes->total() }} registros
                </small>
                <div>
                    {{ $postulantes->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>

        </div>
    </div>

    {{-- MODALES PARA PREVISUALIZAR LOS DOCUMENTOS --}}
    @foreach ($postulantes as $index => $p)
        <div class="modal fade" id="modalExpediente{{ $p->id_postulante ?? $loop->index }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-folder-open mr-2"></i> Documentos del Postulante: {{ $p->primerapellido }} {{ $p->segundoapellido }} {{ $p->nombre }} ({{ $p->codigo ?? 'POS' }})
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 border-right">
                                <div class="text-center mb-3">
                                    @if (!empty($p->foto))
                                        <img src="{{ asset('storage/' . $p->foto) }}" class="img-circle elevation-2" alt="Foto Postulante" style="width: 100px; height: 100px; object-fit: cover;">
                                    @else
                                        <div class="elevation-2 d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded-circle" style="width: 100px; height: 100px;">
                                            <i class="fas fa-user fa-3x"></i>
                                        </div>
                                    @endif
                                </div>
                                <p><strong>CI:</strong> {{ $p->ci }}</p>
                                <p><strong>Celular:</strong> {{ $p->celular ?? 'No registrado' }}</p>
                                <p><strong>Colegio:</strong> {{ $p->nombrecolegio ?? 'N/A' }} ({{ $p->egresocolegio ?? '' }})</p>
                                <p><strong>Carrera:</strong> {{ $p->carrera->nombre ?? 'N/A' }}</p>
                                <hr>
                                @if (!empty($p->celular))
                                    <a href="https://wa.me/{{ $p->celular }}" target="_blank" class="btn btn-success btn-block btn-sm mb-2">
                                        <i class="fab fa-whatsapp mr-1"></i> Contactar por WhatsApp
                                    </a>
                                @endif
                            </div>
                            <div class="col-md-8">
                                <ul class="nav nav-tabs" id="docTabs{{ $index }}" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="ci-tab-{{ $index }}" data-toggle="tab" href="#ci-content-{{ $index }}" role="tab">CI anverso</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="ci-reverso-tab-{{ $index }}" data-toggle="tab" href="#ci-reverso-content-{{ $index }}" role="tab">CI reverso</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="nac-tab-{{ $index }}" data-toggle="tab" href="#nac-content-{{ $index }}" role="tab">Cert. Nacimiento</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="dip-tab-{{ $index }}" data-toggle="tab" href="#dip-content-{{ $index }}" role="tab">Diploma Bachiller</a>
                                    </li>
                                </ul>

                                <div class="tab-content p-3 border border-top-0 bg-light" id="docContent{{ $index }}">
                                    <div class="tab-pane fade show active text-center" id="ci-content-{{ $index }}" role="tabpanel">
                                        <p class="text-muted">Carnet de identidad, anverso:</p>
                                        <div class="border bg-white p-2" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                                            @if (!empty($p->path_ci_frontal))
                                                <img src="{{ asset('storage/' . $p->path_ci_frontal) }}" class="img-fluid" style="max-height: 300px;" alt="Anverso del carnet de identidad">
                                            @else
                                                <span class="text-muted font-italic">No adjuntado</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="tab-pane fade text-center" id="ci-reverso-content-{{ $index }}" role="tabpanel">
                                        <p class="text-muted">Carnet de identidad, reverso:</p>
                                        <div class="border bg-white p-2" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                                            @if (!empty($p->path_ci_reverso))
                                                <img src="{{ asset('storage/' . $p->path_ci_reverso) }}" class="img-fluid" style="max-height: 300px;" alt="Reverso del carnet de identidad">
                                            @else
                                                <span class="text-muted font-italic">No adjuntado</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="tab-pane fade text-center" id="nac-content-{{ $index }}" role="tabpanel">
                                        <p class="text-muted">Certificado de Nacimiento:</p>
                                        <div class="border bg-white p-2" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                                            @if (!empty($p->path_nacimiento))
                                                <img src="{{ asset('storage/' . $p->path_nacimiento) }}" class="img-fluid" style="max-height: 300px;">
                                            @else
                                                <span class="text-muted font-italic">No adjuntado</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="tab-pane fade text-center" id="dip-content-{{ $index }}" role="tabpanel">
                                        <p class="text-muted">Diploma de Bachiller:</p>
                                        <div class="border bg-white p-2" style="min-height: 250px; display: flex; align-items: center; justify-content: center;">
                                            @if (!empty($p->path_diploma))
                                                <img src="{{ asset('storage/' . $p->path_diploma) }}" class="img-fluid" style="max-height: 300px;">
                                            @else
                                                <span class="text-muted font-italic">No adjuntado</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@stop