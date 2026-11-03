@extends('adminlte::page')

@section('title', 'Editar gestión académica')
@section('content_header')<h1>Editar gestión académica</h1>@stop
@section('content')
<div class="card card-outline card-secondary">
    <form action="{{ route('admin.gestionacademica.update', $gestion->id_gestionacademica) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="gestion">Gestión</label>
                    <input type="number" name="gestion" id="gestion" class="form-control" value="{{ old('gestion', $gestion->gestion) }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label for="periodo">Periodo</label>
                    <input type="number" name="periodo" id="periodo" class="form-control" value="{{ old('periodo', $gestion->periodo) }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label for="regimen">Régimen</label>
                    <select name="regimen" id="regimen" class="form-control" required>
                        <option value="1" @selected(old('regimen', $gestion->regimen) == 1)>Anual</option>
                        <option value="2" @selected(old('regimen', $gestion->regimen) == 2)>Semestral</option>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label for="modalidadevaluacion">Modalidad de evaluación</label>
                    <select name="modalidadevaluacion" id="modalidadevaluacion" class="form-control">
                        <option value="">-- Seleccionar --</option>
                        <option value="1" @selected(old('modalidadevaluacion', $gestion->modalidadevaluacion) == 1)>Parciales</option>
                        <option value="2" @selected(old('modalidadevaluacion', $gestion->modalidadevaluacion) == 2)>Bimestres</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="inicio">Inicio</label>
                    <input type="date" name="inicio" id="inicio" class="form-control" value="{{ old('inicio', $gestion->inicio ? \Carbon\Carbon::parse($gestion->inicio)->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-6 form-group">
                    <label for="fin">Fin</label>
                    <input type="date" name="fin" id="fin" class="form-control @error('fin') is-invalid @enderror" value="{{ old('fin', $gestion->fin ? \Carbon\Carbon::parse($gestion->fin)->format('Y-m-d') : '') }}" min="{{ old('inicio', $gestion->inicio ? \Carbon\Carbon::parse($gestion->inicio)->format('Y-m-d') : '') }}">
                    <div id="fin-date-error" class="invalid-feedback d-block" role="alert" @if(!$errors->has('fin')) hidden @endif>
                        {{ $errors->first('fin') }}
                    </div>
                </div>
                <div class="col-12 form-group mb-0">
                    <div class="custom-control custom-checkbox">
                        <input type="hidden" name="vigente" value="0">
                        <input type="checkbox" name="vigente" id="vigente" value="1" class="custom-control-input" @checked(old('vigente', $gestion->vigente))>
                        <label for="vigente" class="custom-control-label">Gestión vigente</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('admin.gestionacademica.index') }}" class="btn btn-light border mr-2">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar cambios</button>
        </div>
    </form>
</div>
@stop

@section('js')
    @parent
    <script>
        (() => {
            const inicio = document.getElementById('inicio');
            const fin = document.getElementById('fin');
            const error = document.getElementById('fin-date-error');
            const mensaje = 'La fecha de fin no puede ser anterior a la fecha de inicio.';

            const validarFechas = () => {
                fin.min = inicio.value;
                const fechaInvalida = inicio.value && fin.value && fin.value < inicio.value;
                fin.setCustomValidity(fechaInvalida ? mensaje : '');
                fin.classList.toggle('is-invalid', Boolean(fechaInvalida));
                error.textContent = fechaInvalida ? mensaje : '';
                error.hidden = !fechaInvalida;
            };

            inicio.addEventListener('change', validarFechas);
            fin.addEventListener('input', validarFechas);
            fin.addEventListener('change', validarFechas);
            validarFechas();
        })();
    </script>
@stop
