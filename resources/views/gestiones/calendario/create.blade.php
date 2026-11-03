@extends('adminlte::page')

@section('title', 'Nueva actividad')
@section('content_header')<h1>Nueva actividad del calendario</h1>@stop
@section('content')
<div class="card card-outline card-secondary">
    <form action="{{ route('admin.actividad.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label for="id_gestionacademica">Gestión académica</label>
                <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach($gestiones as $gestion)
                        <option value="{{ $gestion->id_gestionacademica }}" @selected(old('id_gestionacademica', $gestionSeleccionada ?? '') == $gestion->id_gestionacademica)>
                            {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                        </option>
                    @endforeach
                </select>
            </div>
            @include('gestiones.calendario._audiencia')
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre') }}" required>
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="form-control" rows="3">{{ old('descripcion') }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="inicio">Inicio</label>
                    <input type="datetime-local" name="inicio" id="inicio" class="form-control" value="{{ old('inicio') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label for="fin">Fin</label>
                    <input type="datetime-local" name="fin" id="fin" class="form-control @error('fin') is-invalid @enderror" value="{{ old('fin') }}" min="{{ old('inicio') }}">
                    <div id="fin-date-error" class="invalid-feedback d-block" role="alert" @if(!$errors->has('fin')) hidden @endif>
                        {{ $errors->first('fin') }}
                    </div>
                </div>
            </div>
            <div class="form-group mb-0">
                <label for="funcionalidades">Habilitar secciones</label>
                <select name="funcionalidades[]" id="funcionalidades" class="form-control @error('funcionalidades') is-invalid @enderror @error('funcionalidades.*') is-invalid @enderror" multiple size="6">
                    @foreach($funcionalidades as $funcionalidad)
                        <option value="{{ $funcionalidad->id_recurso }}" @selected(in_array((string) $funcionalidad->id_recurso, array_map('strval', old('funcionalidades', [])), true))>
                            Sección {{ $funcionalidad->id_recurso }} - {{ $funcionalidad->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('funcionalidades')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                @error('funcionalidades.*')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                <small class="form-text text-muted">Usa Ctrl (Windows) o Cmd (Mac) para seleccionar varias secciones. Quedan habilitadas durante las fechas indicadas y para la audiencia seleccionada.</small>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('admin.actividad.index') }}" class="btn btn-light border mr-2">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
        </div>
    </form>
</div>
@stop

@section('js')
    @parent
    @include('gestiones.calendario._validacion-fechas')
@stop
