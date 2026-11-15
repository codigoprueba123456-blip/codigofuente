@extends('adminlte::page')

@section('title', 'Grupos')

@section('content')
<div class="container-fluid px-3 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1 text-dark font-weight-semibold">Grupos</h3>
            <small class="text-muted">Gestión de grupos</small>
        </div>
        <span class="badge badge-success px-3 py-2">
            <i class="fas fa-users mr-1"></i> Panel de grupos
        </span>
    </div>

    <div class="card card-outline card-success shadow-sm">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('admin.grupos.index') }}" method="GET" id="filterForm">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-6">
                        <label for="id_gestionacademica" class="font-weight-bold text-secondary">
                            Gestión académica <span class="text-danger">*</span>
                        </label>
                        <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" required>
                            <option value="">Seleccionar gestión</option>
                            @foreach($gestiones as $gestion)
                                <option value="{{ $gestion->id_gestionacademica }}" {{ request('id_gestionacademica') == $gestion->id_gestionacademica ? 'selected' : '' }}>
                                    {{ $gestion->etiqueta }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_gestionacademica')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label for="id_carrera" class="font-weight-bold text-secondary">
                            Carrera <span class="text-danger">*</span>
                        </label>
                        <select name="id_carrera" id="id_carrera" class="form-control" required>
                            <option value="">Seleccionar carrera</option>
                            @foreach($carreras as $carrera)
                                @php($resoluciones = $carrera->resolucionesMinisteriales())
                                <option value="{{ $carrera->id_carrera }}" {{ request('id_carrera') == $carrera->id_carrera ? 'selected' : '' }}>
                                    {{ $carrera->nombre . ($resoluciones ? ' · R.M. ' . implode(' / ', $resoluciones) : '') }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_carrera')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center bg-light border rounded p-2 mb-3">
                    <div class="d-flex flex-wrap">
                        <a href="{{ route('admin.grupos.programar', ['id_gestionacademica' => request('id_gestionacademica'), 'id_carrera' => request('id_carrera')]) }}" class="btn btn-primary btn-sm mr-2 mb-1">
                            <i class="fas fa-users mr-1"></i> Programar grupos
                        </a>
                    </div>
                    @if($hasFilters)
                        <span class="text-muted small mt-1">
                            {{ $grupos->count() }} {{ \Illuminate\Support\Str::plural('grupo', $grupos->count()) }} encontrado(s)
                        </span>
                    @endif
                </div>

                @if($hasFilters)
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                        </div>
                        <input type="search" name="buscar" id="buscar" class="form-control" placeholder="Buscar grupo, materia o código" value="{{ request('buscar') }}" data-auto-search-input autocomplete="off">
                    </div>
                @endif
            </form>

            <div class="table-responsive border rounded">
                <table class="table table-sm table-striped table-hover text-center align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width: 90px;">Nivel</th>
                            <th style="width: 55px;">N.º</th>
                            <th style="min-width: 95px;">Turno</th>
                            <th style="min-width: 80px;">Código</th>
                            <th class="text-left" style="min-width: 280px;">Materia</th>
                            <th style="width: 95px;">Capacidad</th>
                            <th style="width: 90px;">Inscritos</th>
                            <th style="width: 85px;">Abierto</th>
                            <th style="width: 110px;">Estado</th>
                            <th style="width: 125px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($hasFilters)
                            @foreach($gruposPorNivel as $nivel => $gruposDelNivel)
                                @foreach($gruposDelNivel as $grupo)
                                    <tr>
                                        @if($loop->first)
                                            <th scope="rowgroup" rowspan="{{ $gruposDelNivel->count() }}" class="bg-light align-middle">
                                                {{ $nivel }}
                                            </th>
                                        @endif
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @switch($grupo->turno)
                                                @case(1) Mañana @break
                                                @case(2) Tarde @break
                                                @case(3) Noche @break
                                                @default {{ $grupo->turno ?? '-' }}
                                            @endswitch
                                        </td>
                                        <td><span class="badge badge-light border">{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::afterLast($grupo->codigo, '-')) }}</span></td>
                                        <td class="text-left">{{ $grupo->materia->nombre ?? '-' }}</td>
                                        <td>{{ $grupo->capacidad ?? '-' }}</td>
                                        <td>{{ $grupo->inscritos_count }}</td>
                                        <td>
                                            @if($grupo->abierto)
                                                <span class="badge badge-success">Sí</span>
                                            @else
                                                <span class="badge badge-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $grupo->activo ? 'success' : 'secondary' }}">
                                                {{ $grupo->activo ? 'Habilitado' : 'Deshabilitado' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-inline-flex">
                                                <a href="{{ route('admin.grupos.edit', $grupo->id_grupo) }}" class="btn btn-outline-primary btn-xs mr-1" title="Editar" aria-label="Editar grupo">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.grupos.estado', $grupo->id_grupo) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="activo" value="{{ $grupo->activo ? 0 : 1 }}">
                                                    <button type="submit" class="btn btn-outline-{{ $grupo->activo ? 'warning' : 'success' }} btn-xs" title="{{ $grupo->activo ? 'Deshabilitar' : 'Habilitar' }} grupo" aria-label="{{ $grupo->activo ? 'Deshabilitar' : 'Habilitar' }} grupo">
                                                        <i class="fas fa-{{ $grupo->activo ? 'ban' : 'check' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@include('partials.auto-search')

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('filterForm');
        const gestion = document.getElementById('id_gestionacademica');
        const carrera = document.getElementById('id_carrera');

        [gestion, carrera].forEach(function (select) {
            select.addEventListener('change', function () {
                form.requestSubmit();
            });
        });
    });
</script>
@endpush
