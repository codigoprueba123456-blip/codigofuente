@extends('adminlte::page')
@section('content')
<div class="card mt-3">
    <div class="card-header">
        <a href="{{ route('admin.empleados.create') }}" class="btn btn-default"><i class="fa fa-plus"></i> Nuevo</a>
        <a href="{{ route('admin.empleados.index') }}" class="btn btn-default"><i class="fa fa-sync"></i> Actualizar</a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.empleados.index') }}" method="GET" class="mb-3" data-auto-search>
            <div class="input-group">
                <input type="search" name="q" class="form-control" placeholder="Buscar..." value="{{ request('q') }}" data-auto-search-input autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Primer Apellido</th><th>Segundo Apellido</th><th>Nombre</th><th>CI</th><th>Dirección</th>
                        <th>Teléfono</th><th>Celular</th><th>Email</th><th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($empleados as $emp)
                    <tr>
                        <td>{{ $emp->persona->primerapellido }}</td>
                        <td>{{ $emp->persona->segundoapellido }}</td>
                        <td>{{ $emp->persona->nombre }}</td>
                        <td>{{ $emp->persona->ci }}</td>
                        <td>{{ $emp->persona->direccion }}</td>
                        <td>{{ $emp->persona->telefono }}</td>
                        <td>{{ $emp->persona->celular }}</td>
                        <td>{{ $emp->persona->email }}</td>
                        <td>
                            <span class="badge badge-{{ $emp->activo ? 'success' : 'secondary' }}">
                                {{ $emp->activo ? 'Habilitado' : 'Deshabilitado' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.empleados.edit', $emp->id_persona) }}" class="btn btn-sm btn-default" aria-label="Editar empleado"><i class="fa fa-edit"></i></a>
                            <form action="{{ route('admin.empleados.estado', $emp->id_persona) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="activo" value="{{ $emp->activo ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm btn-{{ $emp->activo ? 'warning' : 'success' }}" aria-label="{{ $emp->activo ? 'Deshabilitar' : 'Habilitar' }} empleado">
                                    <i class="fa fa-{{ $emp->activo ? 'ban' : 'check' }}"></i>
                                    {{ $emp->activo ? 'Deshabilitar' : 'Habilitar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop

@include('partials.auto-search')