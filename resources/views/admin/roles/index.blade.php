@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Roles <small class="text-muted text-sm">gestión de roles</small></h1>
    </div>
@stop

@section('content')
<div class="card card-outline card-secondary">
    <div class="card-body">
        
        {{-- Barra de Herramientas (Botones Nuevo y Actualizar) --}}
        <div class="d-flex justify-content-between mb-3">
            <div>
                <a href="{{ route('admin.roles.create') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-plus"></i> Nuevo
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload();">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
            <div>
                <button class="btn btn-outline-secondary btn-sm" title="Configuración">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>

        {{-- Buscador --}}
        <div class="input-group mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar...">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="bg-light text-center">
                    <tr>
                        <th style="width: 50px;">N°</th>
                        <th style="width: 220px;">Nombre</th>
                        <th>Recursos</th>
                        <th style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $index => $role)
                        <tr>
                            <td class="text-center align-middle">{{ $index + 1 }}</td>
                            <td class="align-middle font-weight-bold">{{ $role->nombre }}</td>
                            <td>
                                {{-- Caja desplegable/scrollable para los recursos del rol --}}
                                <div class="border rounded p-2 bg-white" style="max-height: 130px; overflow-y: auto;">
                                    @if(isset($role->recursos) && count($role->recursos) > 0)
                                        @foreach($role->recursos as $recurso)
                                            <div class="text-muted small py-1 border-bottom last-border-0">
                                                {{ $recurso->nombre }}
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-muted small">Administrador &gt; Empleados</div>
                                        <div class="text-muted small">Administrador &gt; Empresas</div>
                                        <div class="text-muted small">Administrador &gt; Instituto</div>
                                        <div class="text-muted small">Administrador &gt; Logs</div>
                                        <div class="text-muted small">Administrador &gt; Plantillas</div>
                                        <div class="text-muted small">Administrador &gt; Roles</div>
                                        <div class="text-muted small">Administrador &gt; Usuarios</div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <a href="{{ route('admin.roles.edit', $role->id_rol) }}" class="btn btn-default btn-sm" title="Editar">
                                    <i class="fas fa-edit text-secondary"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No hay roles registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if(method_exists($roles, 'links'))
            <div class="d-flex justify-content-end mt-3">
                {{ $roles->links() }}
            </div>
        @endif

    </div>
</div>
@stop

<style>
    .last-border-0:last-child {
        border-bottom: none !important;
    }
</style>