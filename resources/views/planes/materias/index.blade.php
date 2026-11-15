@extends('adminlte::page')

@section('title', 'Gestión de Materias')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Materia <small class="text-muted">gestión de materias</small></h1>
        </div>
    </div>
@stop

@section('content')
<div class="card mt-3">
    <!-- Cabecera con selector de Carrera y Botonera -->
    <div class="card-header bg-light">
        <div class="form-group mb-2">
            <label for="carrera_select" class="font-weight-normal text-muted mb-1">Carrera: *</label>
            <form action="{{ route('planes.materias.index') }}" method="GET" style="max-width: 450px;">
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                <select name="id_carrera" id="carrera_select" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las carreras</option>
                    @foreach($carreras as $carrera)
                        @php
                            $resoluciones = $carrera->resolucionesMinisteriales();
                        @endphp
                        <option value="{{ $carrera->id_carrera }}" {{ (string) request('id_carrera') === (string) $carrera->id_carrera ? 'selected' : '' }}>
                            {{ $carrera->nombre . ($resoluciones ? ' · R.M. ' . implode(' / ', $resoluciones) : '') }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
            <div class="btn-toolbar" role="toolbar">
                <a href="{{ route('planes.materias.create') }}" class="btn btn-default mr-2 mb-2">
                    <i class="fa fa-plus"></i> Nuevo
                </a>
                <a href="{{ route('planes.materias.index') }}" class="btn btn-default mb-2">
                    <i class="fa fa-sync"></i> Actualizar
                </a>
            </div>
            <div>
                <button type="button" class="btn btn-default mb-2" title="Configuración">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Formulario de Búsqueda -->
        <form action="{{ route('planes.materias.index') }}" method="GET" class="mb-3" data-auto-search>
            @if(request('id_carrera'))
                <input type="hidden" name="id_carrera" value="{{ request('id_carrera') }}">
            @endif
            <div class="input-group">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar..." data-auto-search-input autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <!-- Tabla de Materias responsive -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-sm">
                <thead>
                    <tr class="text-center">
                        <th>Nivel</th>
                        <th>Nº</th>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Horas</th>
                        <th>Creditaje materia</th>
                        <th>Curricular</th>
                        <th>Prerrequisitos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materias as $materia)
                    <tr>
                        <td class="text-center">{{ $materia->nivel ?? '-' }}</td>
                        <td class="text-center font-weight-bold">{{ $loop->iteration }}</td>
                        <td>{{ $materia->codigo ?? '-' }}</td>
                        <td>{{ $materia->nombre }}</td>
                        <td class="text-center">{{ $materia->horas ?? '-' }}</td>
                        <td class="text-center">{{ $materia->creditajemateria ?? '-' }}</td>
                        <td>{{ $materia->curricular ?? ($materia->carrera->nombre ?? '-') }}</td>
                        <td>
                            @if($materia->prerequisitos->isNotEmpty())
                                @foreach($materia->prerequisitos as $prereq)
                                    <span class="badge badge-secondary mr-1">{{ $prereq->nombre }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Ningún registro encontrado</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $materia->activo ? 'success' : 'secondary' }}">
                                {{ $materia->activo ? 'Habilitada' : 'Deshabilitada' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="{{ route('planes.materias.edit', $materia->id_materia) }}" class="btn btn-sm btn-info" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('planes.materias.estado', $materia->id_materia) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="activo" value="{{ $materia->activo ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-sm btn-{{ $materia->activo ? 'warning' : 'success' }}" title="{{ $materia->activo ? 'Deshabilitar' : 'Habilitar' }}">
                                        <i class="fas fa-{{ $materia->activo ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-3">Ningún registro encontrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@stop

@include('partials.auto-search')