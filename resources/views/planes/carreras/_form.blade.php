@php($editing = isset($carrera))

<form action="{{ $editing ? route('planes.carreras.update', $carrera->id_carrera) : route('planes.carreras.store') }}" method="POST">
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="form-group">
            <input type="hidden" name="activo" value="0">
            <div class="custom-control custom-switch">
                <input type="checkbox" name="activo" id="activo" value="1" class="custom-control-input" @checked(old('activo', $carrera->activo ?? true))>
                <label class="custom-control-label" for="activo">Carrera habilitada para inscripciones</label>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                <label for="codigo">Código *</label>
                <input type="text" name="codigo" id="codigo" class="form-control @error('codigo') is-invalid @enderror" value="{{ old('codigo', $carrera->codigo ?? '') }}" required>
                @error('codigo')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-8 form-group">
                <label for="nombre">Nombre de la carrera *</label>
                <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $carrera->nombre ?? '') }}" required>
                @error('nombre')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                <label for="id_jefecarrera">Jefe de carrera</label>
                <select name="id_jefecarrera" id="id_jefecarrera" class="form-control @error('id_jefecarrera') is-invalid @enderror">
                    <option value="">Sin asignar</option>
                    @foreach($jefesCarrera as $empleado)
                        <option value="{{ $empleado->id_persona }}" {{ old('id_jefecarrera', $carrera->id_jefecarrera ?? '') == $empleado->id_persona ? 'selected' : '' }}>
                            {{ $empleado->persona->nombre ?? 'Empleado' }} {{ $empleado->persona->primerapellido ?? '' }} {{ $empleado->persona->segundoapellido ?? '' }}
                        </option>
                    @endforeach
                </select>
                @error('id_jefecarrera')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="instituto_nombre">Instituto</label>
                @if($instituto)
                    <input type="text" id="instituto_nombre" class="form-control" value="{{ $instituto->nombreinstituto }}" readonly>
                    <small class="form-text text-muted">Se asignará el instituto configurado en Administración.</small>
                @else
                    <input type="text" id="instituto_nombre" class="form-control" value="No hay un instituto configurado" readonly>
                @endif
                @error('id_instituto')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="nivelacademico">Nivel académico</label>
                <select name="nivelacademico" id="nivelacademico" class="form-control @error('nivelacademico') is-invalid @enderror">
                    <option value="">Seleccione un nivel</option>
                    <option value="{{ \App\Models\Carrera::NIVEL_TECNICO_SUPERIOR }}" @selected(old('nivelacademico', $carrera->nivelacademico ?? '') == \App\Models\Carrera::NIVEL_TECNICO_SUPERIOR)>Técnico Superior</option>
                    <option value="{{ \App\Models\Carrera::NIVEL_TECNICO_MEDIO }}" @selected(old('nivelacademico', $carrera->nivelacademico ?? '') == \App\Models\Carrera::NIVEL_TECNICO_MEDIO)>Técnico Medio</option>
                </select>
                @error('nivelacademico')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 form-group">
                <label for="regimen">Régimen</label>
                <select name="regimen" id="regimen" class="form-control @error('regimen') is-invalid @enderror">
                    <option value="">Seleccione un régimen</option>
                    <option value="{{ \App\Models\Carrera::REGIMEN_ANUAL }}" @selected(old('regimen', $carrera->regimen ?? '') == \App\Models\Carrera::REGIMEN_ANUAL)>Anual</option>
                    <option value="{{ \App\Models\Carrera::REGIMEN_SEMESTRAL }}" @selected(old('regimen', $carrera->regimen ?? '') == \App\Models\Carrera::REGIMEN_SEMESTRAL)>Semestral</option>
                </select>
                @error('regimen')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="col-md-3 form-group">
                <label for="version">Versión</label>
                <input type="number" name="version" id="version" class="form-control" min="0" value="{{ old('version', $carrera->version ?? '') }}">
            </div>
        </div>

        <div class="row">
            @for($number = 1; $number <= 3; $number++)
                <div class="col-md-4 form-group">
                    <label for="resolucionministerial{{ $number }}">Resolución ministerial {{ $number }}</label>
                    <input type="text" name="resolucionministerial{{ $number }}" id="resolucionministerial{{ $number }}" class="form-control" value="{{ old('resolucionministerial' . $number, $carrera->{'resolucionministerial' . $number} ?? '') }}">
                </div>
            @endfor
        </div>
    </div>

    <div class="card-footer text-right">
        <a href="{{ route('planes.carreras.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $editing ? 'Actualizar carrera' : 'Guardar carrera' }}</button>
    </div>
</form>
