@extends('adminlte::page')

@section('title', 'Gestiones Académicas')

@section('content')
<div class="container-fluid px-3 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="mb-1 text-dark fw-semibold">Gestiones Académicas</h3>
            <small class="text-muted">Administración de gestiones académicas</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
            <i class="fas fa-calendar-check me-1"></i> Panel académico
        </span>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label for="regimen" class="form-label fw-semibold text-secondary mb-2">
                        Régimen <span class="text-danger">*</span>
                    </label>
                    <select name="regimen" id="regimen" class="form-select form-select-sm border-secondary-subtle shadow-none">
                        <option value="">-- Seleccionar --</option>
                        <option value="1">Anual</option>
                        <option value="2">Semestral</option>
                    </select>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-light border rounded-3 px-3 py-2 mb-4">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.gestionacademica.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="fas fa-plus me-1"></i> Nuevo
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="window.location.reload();">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                    </button>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" aria-label="Configuración">
                    <i class="fas fa-cog"></i>
                </button>
            </div>

            <form action="{{ route('admin.gestionacademica.index') }}" method="GET" class="mb-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" name="buscar" class="form-control border-start-0 shadow-none" placeholder="Buscar gestión, periodo o código" value="{{ request('buscar') }}">
                    <button class="btn btn-outline-primary" type="submit">
                        Buscar
                    </button>
                </div>
            </form>

            <div class="table-responsive rounded-3 border">
                <table class="table table-sm table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">Nº</th>
                            <th>Código</th>
                            <th>Gestión</th>
                            <th>Periodo</th>
                            <th>Régimen</th>
                            <th>Modalidad Evaluación</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Vigente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gestiones as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->id_gestionacademica }}</td>
                                <td>{{ $item->gestion }}</td>
                                <td>{{ $item->periodo }}</td>
                                <td>{{ [1 => 'Anual', 2 => 'Semestral'][$item->regimen] ?? $item->regimen }}</td>
                                <td>{{ [1 => 'Parciales', 2 => 'Bimestres'][$item->modalidadevaluacion] ?? '-' }}</td>
                                <td>{{ $item->inicio ? \Carbon\Carbon::parse($item->inicio)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $item->fin ? \Carbon\Carbon::parse($item->fin)->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if($item->vigente)
                                        <span class="badge bg-success rounded-pill">Sí</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">No</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-start ps-3 py-3 text-muted bg-light">
                                    <i class="fas fa-info-circle me-2"></i> Ningún registro encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection