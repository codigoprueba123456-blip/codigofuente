@extends('adminlte::page')

@section('title', 'Programar grupo')
@section('content_header')<h1>Programar grupo</h1>@stop
@section('content')
<div class="card card-outline card-secondary">
    <form action="{{ route('admin.grupos.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="id_gestionacademica">Gestión académica</label>
                    <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($gestiones as $gestion)
                            <option value="{{ $gestion->id_gestionacademica }}" @selected(old('id_gestionacademica', request('id_gestionacademica')) == $gestion->id_gestionacademica)>
                                {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="id_materia">Materia</label>
                    <select name="id_materia" id="id_materia" class="form-control">
                        <option value="">-- Seleccionar --</option>
                        @foreach($materias as $materia)
                            <option value="{{ $materia->id_materia }}" @selected(old('id_materia') == $materia->id_materia)>
                                {{ $materia->codigo ? $materia->codigo . ' - ' : '' }}{{ $materia->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="codigo">Código del grupo</label>
                    <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo') }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label for="capacidad">Capacidad</label>
                    <input type="number" min="1" name="capacidad" id="capacidad" class="form-control" value="{{ old('capacidad') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label for="turno">Turno</label>
                    <select name="turno" id="turno" class="form-control">
                        <option value="">-- Seleccionar --</option>
                        <option value="1" @selected(old('turno') == 1)>Mañana</option>
                        <option value="2" @selected(old('turno') == 2)>Tarde</option>
                        <option value="3" @selected(old('turno') == 3)>Noche</option>
                    </select>
                </div>
                <div class="col-12 form-group mb-0">
                    <div class="custom-control custom-checkbox">
                        <input type="hidden" name="abierto" value="0">
                        <input type="checkbox" name="abierto" id="abierto" value="1" class="custom-control-input" @checked(old('abierto', true))>
                        <label for="abierto" class="custom-control-label">Grupo abierto</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('admin.grupos.index') }}" class="btn btn-light border mr-2">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
        </div>
    </form>
</div>
@stop
