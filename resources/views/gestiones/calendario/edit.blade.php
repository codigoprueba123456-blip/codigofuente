@extends('adminlte::page')

@section('title', 'Editar actividad')
@section('content_header')<h1>Editar actividad del calendario</h1>@stop
@section('content')
<div class="card card-outline card-secondary">
    <form action="{{ route('admin.actividad.update', $actividad->id_actividad) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="id_gestionacademica">Gestión académica</label>
                <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach($gestiones as $gestion)
                        <option value="{{ $gestion->id_gestionacademica }}" @selected(old('id_gestionacademica', $actividad->id_gestionacademica) == $gestion->id_gestionacademica)>
                            {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                        </option>
                    @endforeach
                </select>
            </div>
            @include('gestiones.calendario._audiencia')
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $actividad->nombre) }}" required>
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="form-control" rows="3">{{ old('descripcion', $actividad->descripcion) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="inicio">Inicio</label>
                    <input type="datetime-local" name="inicio" id="inicio" class="form-control" value="{{ old('inicio', $actividad->inicio ? \Carbon\Carbon::parse($actividad->inicio)->format('Y-m-d\TH:i') : '') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label for="fin">Fin</label>
                    <input type="datetime-local" name="fin" id="fin" class="form-control" value="{{ old('fin', $actividad->fin ? \Carbon\Carbon::parse($actividad->fin)->format('Y-m-d\TH:i') : '') }}">
                </div>
            </div>
            <div class="form-group mb-0">
                <label for="funcionalidad">Habilitar sección</label>
                <select name="funcionalidad" id="funcionalidad" class="form-control">
                    <option value="">No habilita una sección</option>
                    @if($actividad->funcionalidad && !$funcionalidades->contains('id_recurso', $actividad->funcionalidad))
                        <option value="{{ $actividad->funcionalidad }}" selected>Sección anterior #{{ $actividad->funcionalidad }}</option>
                    @endif
                    @foreach($funcionalidades as $funcionalidad)
                        <option value="{{ $funcionalidad->id_recurso }}" @selected(old('funcionalidad', $actividad->funcionalidad) == $funcionalidad->id_recurso)>
                            Sección {{ $funcionalidad->id_recurso }} - {{ $funcionalidad->nombre }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">El número se asigna automáticamente a cada módulo. La sección queda habilitada solo durante las fechas indicadas y para la audiencia seleccionada.</small>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('admin.actividad.index') }}" class="btn btn-light border mr-2">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar cambios</button>
        </div>
    </form>
</div>
@stop
