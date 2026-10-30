@extends('adminlte::page')

@section('content')
<div class="container-fluid pt-3">
    <div class="card card-warning"> <div class="card-header">
            <h3 class="card-title">Editar Empleado: {{ $empleado->persona->nombre }}</h3>
        </div>
        
        <form action="{{ route('admin.empleados.update', $empleado->id_persona) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="row">
                    <div class="form-group">
                        <label>foto</label>
                        <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp" data-image-preview="foto-preview">
                        @include('partials.image-preview', [
                            'id' => 'foto-preview',
                            'src' => $empleado->persona->foto ? url('storage/' . $empleado->persona->foto) : null,
                            'alt' => 'Previsualización de la foto del empleado',
                            'emptyLabel' => 'El empleado no tiene una foto guardada.',
                        ])
                    </div>
                </div>
                <div class="row">
                    @foreach(range(1, 4) as $slot)
                        @php($documentoActual = $empleado->documentos->firstWhere('slot', $slot))
                        <div class="col-md-3 form-group">
                            <label for="documento_{{ $slot }}">Imagen de documento {{ $slot }}</label>
                            <input type="file" name="documentos[{{ $slot }}]" id="documento_{{ $slot }}" class="form-control @error('documentos.' . $slot) is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" data-image-preview="documento-preview-{{ $slot }}">
                            @include('partials.image-preview', [
                                'id' => 'documento-preview-' . $slot,
                                'src' => $documentoActual ? url('storage/' . $documentoActual->ruta) : null,
                                'alt' => 'Previsualización del documento ' . $slot,
                                'emptyLabel' => 'No hay un documento guardado en este espacio.',
                            ])
                            @if($documentoActual)
                                <a href="{{ url('storage/' . $documentoActual->ruta) }}" target="_blank" rel="noopener">
                                    Ver imagen actual
                                </a>
                            @endif
                            @error('documentos.' . $slot)
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                    <div class="col-12">
                        <small class="form-text text-muted">Puedes adjuntar hasta 4 imágenes JPG, PNG o WEBP (máximo 5 MB cada una). Si seleccionas una imagen, reemplazará la actual en ese espacio.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $empleado->persona->nombre) }}" maxlength="255" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Primer Apellido *</label>
                        <input type="text" name="primerapellido" class="form-control" value="{{ old('primerapellido', $empleado->persona->primerapellido) }}" maxlength="255" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Segundo Apellido</label>
                        <input type="text" name="segundoapellido" class="form-control" value="{{ old('segundoapellido', $empleado->persona->segundoapellido) }}" maxlength="255">
                    </div>
                </div>

                 <div class="row">
                    <div class="col-md-4 form-group">
                        <label>DNI *</label>
                        <input type="text" name="dni" class="form-control" value="{{ old('dni', $empleado->persona->dni) }}" maxlength="50" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Lugar Expedición *</label>
                        <input type="text" name="lugarexpedicion" class="form-control" value="{{ old('lugarexpedicion', $empleado->persona->lugarexpedicion) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Fecha Nacimiento *</label>
                        <input type="date" name="fechanacimiento" class="form-control" value="{{ old('fechanacimiento', $empleado->persona->fechanacimiento) }}" max="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Lugar Nacimiento *</label>
                        <input type="text" name="lugarnacimiento" class="form-control" value="{{ old('lugarnacimiento', $empleado->persona->lugarnacimiento) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Nacionalidad *</label>
                        <input type="text" name="nacionalidad" class="form-control" value="{{ old('nacionalidad', $empleado->persona->nacionalidad) }}" maxlength="100">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Sexo *</label>
                        <select name="sexo" class="form-control">
                            <option value="">Seleccione...</option>
                            <option value="MASCULINO">Masculino</option>
                            <option value="FEMENINO">Femenino</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Dirección *</label>
                        <input type="text" name="direccion" class="form-control" value="{{ old('direccion', $empleado->persona->direccion) }}" maxlength="255">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" value="{{ old('telefono', $empleado->persona->telefono) }}" minlength="7" maxlength="15" inputmode="numeric">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Celular</label>
                        <input type="tel" name="celular" class="form-control" value="{{ old('celular', $empleado->persona->celular) }}" minlength="7" maxlength="15" inputmode="numeric">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $empleado->persona->email) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Abreviatura Profesión *</label>
                        <input type="text" name="abreviaturaprofesion" class="form-control" value="{{ old('abreviaturaprofesion', $empleado->abreviaturaprofesion) }}" maxlength="100">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Código</label>
                        <input type="text" name="codigo" class="form-control" value="{{ old('codigo', $empleado->codigo) }}" maxlength="255">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="usuario">Usuario *</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" value="{{ old('usuario', $usuario->usuario ?? '') }}" maxlength="255" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="id_rol">Rol *</label>
                        <select name="id_rol" id="id_rol" class="form-control" required>
                            <option value="">Seleccione un rol</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id_rol }}" {{ old('id_rol', $usuario?->cuenta?->id_rol) == $rol->id_rol ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="password">Nueva contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" minlength="8" autocomplete="new-password">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="password_confirmation">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="8" autocomplete="new-password">
                    </div>
                </div>

            <div class="card-footer text-right">
                <a href="{{ route('admin.empleados.index') }}" class="btn btn-default">Cancelar</a>
                <button type="submit" class="btn btn-warning text-white">Actualizar Cambios</button>
            </div>
        </form>
    </div>
</div>
@stop