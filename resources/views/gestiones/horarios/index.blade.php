@extends('adminlte::page')

@section('title', 'Horarios')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Encabezado -->
    <div class="d-flex align-items-baseline mb-3">
        <h4 class="text-secondary fw-normal mb-0 me-2" style="font-size: 1.5rem; color: #555;">Asignación Docente</h4>
        <small class="text-muted" style="font-size: 0.9rem;">asignar docentes</small>
    </div>

    <!-- Contenedor Principal -->
    <div class="card border rounded-1 p-3 bg-white" style="border-color: #dcdcdc !important;">
        
        <form action="{{ route('admin.asignacion-docente.index') }}" method="GET" id="filterForm">
            <!-- Filtros: Gestión Académica y Carrera -->
            <div class="row mb-3 g-3">
                <div class="col-md-4">
                    <label for="id_gestionacademica" class="form-label text-secondary mb-1" style="font-size: 0.9rem;">
                        Gestión Académica: <span class="text-danger">*</span>
                    </label>
                    <select name="id_gestionacademica" id="id_gestionacademica" class="form-select form-select-sm shadow-none" style="border-color: #ccc;" onchange="document.getElementById('filterForm').submit();">
                        <option value="">-- Seleccionar --</option>
                        @foreach($gestiones as $gestion)
                            <option value="{{ $gestion->id_gestionacademica }}" {{ request('id_gestionacademica') == $gestion->id_gestionacademica ? 'selected' : '' }}>
                                {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="id_carrera" class="form-label text-secondary mb-1" style="font-size: 0.9rem;">
                        Carrera: <span class="text-danger">*</span>
                    </label>
                    <select name="id_carrera" id="id_carrera" class="form-select form-select-sm shadow-none" style="border-color: #ccc;" onchange="document.getElementById('filterForm').submit();">
                        <option value="">-- Seleccionar --</option>
                        @foreach($carreras as $carrera)
                            <option value="{{ $carrera->id_carrera }}" {{ request('id_carrera') == $carrera->id_carrera ? 'selected' : '' }}>
                                {{ $carrera->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Toolbar (Botón Actualizar) -->
            <div class="d-flex justify-content-between align-items-center bg-light border p-1 rounded-1 mb-3" style="background-color: #f5f5f5 !important;">
                <div>
                    <button type="button" class="btn btn-sm btn-light border px-3 shadow-sm" onclick="window.location.reload();" style="background-color: #fafafa; font-size: 0.85rem;">
                        <i class="fas fa-sync-alt text-secondary me-1"></i> Actualizar
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-light border shadow-sm" style="background-color: #fafafa;">
                        <i class="fas fa-cog text-secondary"></i>
                    </button>
                </div>
            </div>

            <!-- Buscador -->
            <div class="input-group input-group-sm mb-3">
                <input type="text" name="buscar" class="form-control shadow-none" placeholder="" value="{{ request('buscar') }}" style="border-color: #ccc;">
                <button class="btn btn-light border shadow-none px-3" type="submit" style="border-color: #ccc; background-color: #f8f9fa;">
                    <i class="fas fa-search text-secondary"></i>
                </button>
            </div>
        </form>

        <!-- Tabla de Datos -->
        <div class="table-responsive">
            <table class="table table-sm table-bordered text-center align-middle mb-0" style="font-size: 0.88rem; border-color: #e0e0e0;">
                <thead style="background-color: #f2f2f2;">
                    <tr class="text-secondary fw-bold">
                        <th>Nivel</th>
                        <th style="width: 40px;">Nº</th>
                        <th>Turno</th>
                        <th>Codigo</th>
                        <th>Materia</th>
                        <th>Docente</th>
                        <th>Capacidad</th>
                        <th>Inscritos</th>
                        <th>Abierto</th>
                        <th style="width: 80px;">Acciones</th>
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
                            <td class="text-start ps-2">{{ $grupo->materia->nombre ?? '-' }}</td>
                            <td class="text-start ps-2 fw-semibold text-primary">
                                {{ $grupo->docente ? $grupo->docente->nombre . ' ' . $grupo->docente->primerapellido : 'Sin Asignar' }}
                            </td>
                            <td>{{ $grupo->capacidad }}</td>
                            <td>{{ $grupo->inscritos_count ?? 0 }}</td>
                            <td>
                                @if($grupo->abierto)
                                    <span class="badge bg-success">Sí</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                <!-- Botón para abrir modal de asignación -->
                                <button type="button" class="btn btn-xs btn-outline-primary border-0 p-0 me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignModal{{ $grupo->id_grupo }}" 
                                        title="Asignar Docente">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal de Asignación individual por Grupo -->
                        <div class="modal fade" id="assignModal{{ $grupo->id_grupo }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('admin.asignacion-docente.asignar', $grupo->id_grupo) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header py-2">
                                            <h6 class="modal-title">Asignar Docente a {{ $grupo->codigo }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body text-start">
                                            <p class="mb-2"><strong>Materia:</strong> {{ $grupo->materia->nombre ?? '-' }}</p>
                                            <div class="mb-3">
                                                <label for="id_persona" class="form-label">Seleccionar Docente:</label>
                                                <select name="id_persona" class="form-select form-select-sm" required>
                                                    <option value="">-- Seleccionar --</option>
                                                    @foreach($docentes as $docente)
                                                        <option value="{{ $docente->id_persona }}" {{ $grupo->id_persona == $docente->id_persona ? 'selected' : '' }}>
                                                            {{ $docente->nombre }} {{ $docente->primerapellido }} {{ $docente->segundoapellido }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer py-1">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-sm btn-primary">Guardar Asignación</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="10" class="text-start ps-3 py-2 text-muted" style="background-color: #fff;">
                                Ningún registro encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection