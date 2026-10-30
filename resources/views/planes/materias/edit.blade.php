@extends('adminlte::page')

@section('title', 'Editar materia')

@section('content')
<div class="container-fluid pt-3">
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Editar materia: {{ $materia->nombre }}</h3>
        </div>

        <form action="{{ route('planes.materias.update', $materia->id_materia) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="codigo">Código</label>
                        <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo', $materia->codigo) }}">
                    </div>

                    <div class="col-md-4 form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $materia->nombre) }}" required>
                        @error('nombre')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 form-group">
                        <label for="id_carrera">Carrera *</label>
                        <select name="id_carrera" id="id_carrera" class="form-control @error('id_carrera') is-invalid @enderror" required>
                            <option value="">Seleccione una carrera</option>
                            @foreach($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}" {{ old('id_carrera', $materia->id_carrera) == $carrera->id_carrera ? 'selected' : '' }}>
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
                        <input type="number" name="creditajemateria" id="creditajemateria" class="form-control" value="{{ old('creditajemateria', $materia->creditajemateria) }}" min="0">
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="horas">Horas</label>
                        <input type="number" name="horas" id="horas" class="form-control" value="{{ old('horas', $materia->horas) }}" min="0">
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="nivel">Nivel</label>
                        <input type="number" name="nivel" id="nivel" class="form-control" value="{{ old('nivel', $materia->nivel) }}" min="0">
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="numero">Número</label>
                        <input type="number" name="numero" id="numero" class="form-control" value="{{ old('numero', $materia->numero) }}" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="curricular" id="curricular" value="1" class="custom-control-input" {{ old('curricular', $materia->curricular) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="curricular">Es curricular</label>
                    </div>
                </div>

                <div class="card card-secondary mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Prerrequisitos</h5>
                    </div>
                    <div class="card-body">
                        @forelse($materiasDisponibles as $materiaDisponible)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="prerequisitos[]" value="{{ $materiaDisponible->id_materia }}" id="pre_{{ $materiaDisponible->id_materia }}"
                                       {{ in_array($materiaDisponible->id_materia, $materia->prerequisitos->pluck('id_materia')->toArray(), true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="pre_{{ $materiaDisponible->id_materia }}">
                                    {{ $materiaDisponible->nombre }} {{ $materiaDisponible->codigo ? '(' . $materiaDisponible->codigo . ')' : '' }}
                                </label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No hay otras materias disponibles para asociar como prerequisitos.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('planes.materias.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-warning">Actualizar</button>
            </div>
        </form>
    </div>
</div>
@endsection
