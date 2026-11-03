@extends('adminlte::page')

@section('title', 'Editar inscripción')

@section('content_header')
    <h1 class="m-0 text-dark">Editar ficha de inscripción</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo guardar la ficha.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('inscriptos.update', $inscripcion->id_inscrito) }}">
        @csrf
        @method('PUT')

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Datos personales</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="nombre">Nombres *</label>
                        <input id="nombre" name="nombre" class="form-control" value="{{ old('nombre', $inscripcion->nombre) }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="primerapellido">Primer apellido *</label>
                        <input id="primerapellido" name="primerapellido" class="form-control" value="{{ old('primerapellido', $inscripcion->primerapellido) }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="segundoapellido">Segundo apellido</label>
                        <input id="segundoapellido" name="segundoapellido" class="form-control" value="{{ old('segundoapellido', $inscripcion->segundoapellido) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="ci">Carnet de identidad *</label>
                        <input id="ci" name="ci" class="form-control" value="{{ old('ci', $inscripcion->ci) }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="lugarexpedicion">Lugar de expedición</label>
                        <input id="lugarexpedicion" name="lugarexpedicion" class="form-control" value="{{ old('lugarexpedicion', $inscripcion->lugarexpedicion) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="fechanacimiento">Fecha de nacimiento</label>
                        <input type="date" id="fechanacimiento" name="fechanacimiento" class="form-control" value="{{ old('fechanacimiento', $inscripcion->fechanacimiento ? \Illuminate\Support\Carbon::parse($inscripcion->fechanacimiento)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="lugarnacimiento">Lugar de nacimiento</label>
                        <input id="lugarnacimiento" name="lugarnacimiento" class="form-control" value="{{ old('lugarnacimiento', $inscripcion->lugarnacimiento) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="nacionalidad">Nacionalidad</label>
                        <input id="nacionalidad" name="nacionalidad" class="form-control" value="{{ old('nacionalidad', $inscripcion->nacionalidad) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="sexo">Sexo</label>
                        <input id="sexo" name="sexo" class="form-control" value="{{ old('sexo', $inscripcion->sexo) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="direccion">Dirección</label>
                        <input id="direccion" name="direccion" class="form-control" value="{{ old('direccion', $inscripcion->direccion) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="number" id="telefono" name="telefono" class="form-control" value="{{ old('telefono', $inscripcion->telefono) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="celular">Celular</label>
                        <input type="number" id="celular" name="celular" class="form-control" value="{{ old('celular', $inscripcion->celular) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $inscripcion->email) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Datos académicos y de contacto</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="nombrecolegio">Colegio de procedencia</label>
                        <input id="nombrecolegio" name="nombrecolegio" class="form-control" value="{{ old('nombrecolegio', $inscripcion->nombrecolegio) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="caractercolegio">Tipo de colegio</label>
                        <select id="caractercolegio" name="caractercolegio" class="form-control">
                            <option value="">Sin especificar</option>
                            @foreach (['Fiscal', 'Particular', 'Convenio'] as $tipoColegio)
                                <option value="{{ $tipoColegio }}" {{ old('caractercolegio', $inscripcion->caractercolegio) === $tipoColegio ? 'selected' : '' }}>{{ $tipoColegio }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="egresocolegio">Año de egreso</label>
                        <input type="number" id="egresocolegio" name="egresocolegio" min="1900" max="2100" class="form-control" value="{{ old('egresocolegio', $inscripcion->egresocolegio) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="nombrecontacto">Contacto de emergencia</label>
                        <input id="nombrecontacto" name="nombrecontacto" class="form-control" value="{{ old('nombrecontacto', $inscripcion->nombrecontacto) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="celularcontacto">Celular de contacto</label>
                        <input type="number" id="celularcontacto" name="celularcontacto" class="form-control" value="{{ old('celularcontacto', $inscripcion->celularcontacto) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="parentescocontacto">Parentesco</label>
                        <input id="parentescocontacto" name="parentescocontacto" class="form-control" value="{{ old('parentescocontacto', $inscripcion->parentescocontacto) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="id_carrera">Carrera *</label>
                        <select id="id_carrera" name="id_carrera" class="form-control" required>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id_carrera }}" {{ (string) old('id_carrera', $inscripcion->id_carrera) === (string) $carrera->id_carrera ? 'selected' : '' }}>{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="id_gestionacademica">Gestión académica *</label>
                        <select id="id_gestionacademica" name="id_gestionacademica" class="form-control" required>
                            @foreach ($gestiones as $gestion)
                                <option value="{{ $gestion->id_gestionacademica }}" {{ (string) old('id_gestionacademica', $inscripcion->id_gestionacademica) === (string) $gestion->id_gestionacademica ? 'selected' : '' }}>
                                    {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="nivelinicio">Año de estudio *</label>
                        <select id="nivelinicio" name="nivelinicio" class="form-control" required>
                            @foreach ([1 => 'Primer año', 2 => 'Segundo año', 3 => 'Tercer año'] as $nivel => $nombreNivel)
                                <option value="{{ $nivel }}" {{ (string) old('nivelinicio', $inscripcion->nivelinicio ?? 1) === (string) $nivel ? 'selected' : '' }}>{{ $nombreNivel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="fecha">Fecha de inscripción *</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" value="{{ old('fecha', $inscripcion->fecha ? \Illuminate\Support\Carbon::parse($inscripcion->fecha)->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="deposito">Comprobante / voucher *</label>
                        <input id="deposito" name="deposito" class="form-control" value="{{ old('deposito', $inscripcion->deposito) }}" required>
                        <small class="form-text text-muted">Solo se aceptan comprobantes que no estén usados por otra inscripción activa.</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="becado">Beca</label>
                        <select id="becado" name="becado" class="form-control">
                            <option value="0" {{ (string) old('becado', $inscripcion->becado) === '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (string) old('becado', $inscripcion->becado) === '1' ? 'selected' : '' }}>Sí</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="diplomabachiller">Diploma de bachiller registrado</label>
                        <select id="diplomabachiller" name="diplomabachiller" class="form-control">
                            <option value="0" {{ (string) old('diplomabachiller', $inscripcion->diplomabachiller) === '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (string) old('diplomabachiller', $inscripcion->diplomabachiller) === '1' ? 'selected' : '' }}>Sí</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="traspaso_convalidacion">Traspaso / convalidación</label>
                        <select id="traspaso_convalidacion" name="traspaso_convalidacion" class="form-control">
                            <option value="0" {{ (string) old('traspaso_convalidacion', $inscripcion->traspaso_convalidacion) === '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (string) old('traspaso_convalidacion', $inscripcion->traspaso_convalidacion) === '1' ? 'selected' : '' }}>Sí</option>
                        </select>
                    </div>
                </div>
                <p class="text-muted mb-0"><small>Los cambios personales se reflejarán en las demás carreras del mismo estudiante. No se muestran ni modifican fotografías o documentos.</small></p>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
                <a href="{{ route('inscriptos.index') }}" class="btn btn-default">Cancelar</a>
            </div>
        </div>
    </form>
@stop
