@extends('adminlte::page')

@section('title', 'Nueva gestión académica')
@section('content_header')<h1>Nueva gestión académica</h1>@stop
@section('content')
<div class="card card-outline card-secondary">
    <form action="{{ route('admin.gestionacademica.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="gestion">Gestión</label>
                    <input type="number" name="gestion" id="gestion" class="form-control" value="{{ old('gestion') }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label for="periodo">Periodo</label>
                    <input type="number" name="periodo" id="periodo" class="form-control" value="{{ old('periodo') }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label for="regimen">Régimen</label>
                    <select name="regimen" id="regimen" class="form-control" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="1" @selected(old('regimen') == 1)>Anual</option>
                        <option value="2" @selected(old('regimen') == 2)>Semestral</option>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label for="modalidadevaluacion">Modalidad de evaluación</label>
                    <select name="modalidadevaluacion" id="modalidadevaluacion" class="form-control">
                        <option value="">-- Seleccionar --</option>
                        <option value="1" @selected(old('modalidadevaluacion') == 1)>Parciales</option>
                        <option value="2" @selected(old('modalidadevaluacion') == 2)>Bimestres</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="inicio">Inicio</label>
                    <input type="date" name="inicio" id="inicio" class="form-control" value="{{ old('inicio') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label for="fin">Fin</label>
                    <input type="date" name="fin" id="fin" class="form-control" value="{{ old('fin') }}">
                </div>
                <div class="col-12 form-group mb-0">
                    <div class="custom-control custom-checkbox">
                        <input type="hidden" name="vigente" value="0">
                        <input type="checkbox" name="vigente" id="vigente" value="1" class="custom-control-input" @checked(old('vigente', true))>
                        <label for="vigente" class="custom-control-label">Gestión vigente</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('admin.gestionacademica.index') }}" class="btn btn-light border mr-2">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
        </div>
    </form>
</div>
@stop
