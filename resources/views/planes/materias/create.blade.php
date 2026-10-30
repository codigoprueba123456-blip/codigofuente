@extends('adminlte::page')

@section('title', 'Crear materia')

@section('content')
<div class="container-fluid pt-3">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Registrar nueva materia</h3>
        </div>

        <form action="{{ route('planes.materias.store') }}" method="POST">
            @csrf

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="codigo">Código</label>
                        <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo') }}" placeholder="Ej. MAT-101">
                    </div>

                    <div class="col-md-4 form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                        @error('nombre')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 form-group">
                        <label for="id_carrera">Carrera *</label>
                        <select name="id_carrera" id="id_carrera" class="form-control @error('id_carrera') is-invalid @enderror" required>
                            <option value="">Seleccione una carrera</option>
                            @foreach($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}" {{ old('id_carrera') == $carrera->id_carrera ? 'selected' : '' }}>
                                    {{ $carrera->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_carrera')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="creditajemateria">Créditos</label>
                        <input type="number" name="creditajemateria" id="creditajemateria" class="form-control" value="{{ old('creditajemateria') }}" min="0">
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="horas">Horas</label>
                        <input type="number" name="horas" id="horas" class="form-control" value="{{ old('horas') }}" min="0">
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="nivel">Nivel</label>
                        <input type="number" name="nivel" id="nivel" class="form-control" value="{{ old('nivel') }}" min="0">
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="numero">Número</label>
                        <input type="number" name="numero" id="numero" class="form-control" value="{{ old('numero') }}" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="curricular" id="curricular" value="1" class="custom-control-input" {{ old('curricular') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="curricular">Es curricular</label>
                    </div>
                </div>

                <div class="card card-secondary mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Prerrequisitos</h5>
                    </div>
                    <div class="card-body">
                        @forelse($materiasDisponibles as $materia)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="prerequisitos[]" value="{{ $materia->id_materia }}" id="pre_{{ $materia->id_materia }}">
                                <label class="form-check-label" for="pre_{{ $materia->id_materia }}">{{ $materia->nombre }} {{ $materia->codigo ? '(' . $materia->codigo . ')' : '' }}</label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hay otras materias disponibles para asociar como prerequisitos.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('planes.materias.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection
