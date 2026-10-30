@extends('adminlte::page')

@section('title', 'Grupos')

@section('content')
<div class="container-fluid px-3 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="mb-1 text-dark fw-semibold">Grupos</h3>
            <small class="text-muted">Gestión de grupos</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
            <i class="fas fa-users me-1"></i> Panel de grupos
        </span>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <form action="{{ route('admin.grupos.index') }}" method="GET" id="filterForm">
                <div class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label for="id_gestionacademica" class="form-label fw-semibold text-secondary mb-2">
                            Gestión Académica <span class="text-danger">*</span>
                        </label>
                        <select name="id_gestionacademica" id="id_gestionacademica" class="form-select form-select-sm border-secondary-subtle shadow-none" onchange="document.getElementById('filterForm').submit();">
                            <option value="">-- Seleccionar --</option>
                            @foreach($gestiones as $gestion)
                                <option value="{{ $gestion->id_gestionacademica }}" {{ request('id_gestionacademica') == $gestion->id_gestionacademica ? 'selected' : '' }}>
                                    {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="id_carrera" class="form-label fw-semibold text-secondary mb-2">
                            Carrera <span class="text-danger">*</span>
                        </label>
                        <select name="id_carrera" id="id_carrera" class="form-select form-select-sm border-secondary-subtle shadow-none" onchange="document.getElementById('filterForm').submit();">
                            <option value="">-- Seleccionar --</option>
                            @foreach($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}" {{ request('id_carrera') == $carrera->id_carrera ? 'selected' : '' }}>
                                    {{ $carrera->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-light border rounded-3 px-3 py-2 mb-4">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.grupos.programar', ['id_gestionacademica' => request('id_gestionacademica'), 'id_carrera' => request('id_carrera')]) }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                            <i class="fas fa-users me-1"></i> Programar Grupos
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="window.location.reload();">
                            <i class="fas fa-sync-alt me-1"></i> Actualizar
                        </button>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" aria-label="Configuración">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>

                <div class="input-group input-group-sm mb-4">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" name="buscar" class="form-control border-start-0 shadow-none" placeholder="Buscar grupo, materia o código" value="{{ request('buscar') }}">
                    <button class="btn btn-outline-primary" type="submit">
                        Buscar
                    </button>
                </div>
            </form>

            <div class="table-responsive rounded-3 border">
                <table class="table table-sm table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Nivel</th>
                            <th style="width: 60px;">Nº</th>
                            <th>Turno</th>
                            <th>Código</th>
                            <th>Materia</th>
                            <th>Capacidad</th>
                            <th>Inscritos</th>
                            <th>Abierto</th>
                            <th style="width: 90px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grupos as $grupo)
                            <tr>
                                <td>{{ $grupo->materia->nivel ?? '-' }}</td>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @switch($grupo->turno)
                                        @case(1) Mañana @break
                                        @case(2) Tarde @break
                                        @case(3) Noche @break
                                        @default {{ $grupo->turno ?? '-' }}
                                    @endswitch
                                </td>
                                <td>{{ $grupo->codigo }}</td>
                                <td class="text-start ps-3">{{ $grupo->materia->nombre ?? '-' }}</td>
                                <td>{{ $grupo->capacidad }}</td>
                                <td>{{ $grupo->inscritos_count ?? 0 }}</td>
                                <td>
                                    @if($grupo->abierto)
                                        <span class="badge bg-success rounded-pill">Sí</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">No</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('admin.grupos.edit', $grupo->id_grupo) }}" class="btn btn-link btn-sm p-0 text-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.grupos.destroy', $grupo->id_grupo) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm p-0 text-danger" title="Eliminar" onclick="return confirm('¿Eliminar grupo?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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