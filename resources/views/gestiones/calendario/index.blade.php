@extends('adminlte::page')

@section('title', 'Calendario Académico')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Calendario Academico <small class="text-muted">gestion del calendario academico</small></h1>
        </div>
    </div>
@stop

@section('content')
<div class="card mt-3">
    <!-- Cabecera con selector de Gestión Académica y Botonera -->
    <div class="card-header bg-light">
        <form action="{{ route('admin.actividad.index') }}" method="GET" id="filterForm">
            <div class="form-group mb-3" style="max-width: 350px;">
                <label for="id_gestionacademica" class="font-weight-normal text-muted mb-1">
                    Gestión academica: <span class="text-danger">*</span>
                </label>
                <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" onchange="document.getElementById('filterForm').submit();">
                    <option value="">-- Seleccionar --</option>
                    @foreach($gestiones as $gestion)
                        <option value="{{ $gestion->id_gestionacademica }}" {{ request('id_gestionacademica') == $gestion->id_gestionacademica ? 'selected' : '' }}>
                            {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="btn-toolbar" role="toolbar">
                    <a href="{{ route('admin.actividad.create', ['id_gestionacademica' => request('id_gestionacademica')]) }}" class="btn btn-default mr-2 mb-2">
                        <i class="fa fa-plus"></i> Nuevo
                    </a>
                    <button type="button" class="btn btn-default mb-2" onclick="window.location.reload();">
                        <i class="fa fa-sync"></i> Actualizar
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-default mb-2" title="Configuración">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body">
        <!-- Formulario de Búsqueda -->
        <form action="{{ route('admin.actividad.index') }}" method="GET" class="mb-3">
            <!-- Mantener el filtro de gestión si ya está seleccionado -->
            @if(request('id_gestionacademica'))
                <input type="hidden" name="id_gestionacademica" value="{{ request('id_gestionacademica') }}">
            @endif
            <div class="input-group">
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="Buscar...">
                <div class="input-group-append">
                    <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <!-- Tabla de Actividades responsive -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-sm">
                <thead>
                    <tr class="text-center">
                        <th style="width: 50px;">Nº</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Nombre</th>
                        <th>Descripcion</th>
                        <th>Funcionalidad</th>
                        <th>Habilitado para</th>
                        <th style="width: 100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actividades as $actividad)
                        <tr>
                            <td class="text-center font-weight-bold">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $actividad->inicio ? \Carbon\Carbon::parse($actividad->inicio)->format('d/m/Y H:i') : '-' }}</td>
                            <td class="text-center">{{ $actividad->fin ? \Carbon\Carbon::parse($actividad->fin)->format('d/m/Y H:i') : '-' }}</td>
                            <td>{{ $actividad->nombre }}</td>
                            <td>{{ $actividad->descripcion ?? '-' }}</td>
                            <td class="text-center">
                                @if($actividad->funcionalidad)
                                    {{ $actividad->funcionalidadRecurso?->nombre ?? 'Sección #' . $actividad->funcionalidad }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($actividad->carreras->isNotEmpty())
                                    <span class="badge badge-info">Por carrera</span>
                                @elseif($actividad->usuarios->isNotEmpty())
                                    <span class="badge badge-warning">Por usuario</span>
                                @else
                                    <span class="badge badge-success">General</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.actividad.edit', $actividad->id_actividad) }}" class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.actividad.destroy', $actividad->id_actividad) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Deseas eliminar esta actividad?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Ningún registro encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop