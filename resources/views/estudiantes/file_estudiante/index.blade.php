@extends('adminlte::page')

@section('title', 'File Estudiante')

@section('content_header')
    <h1>File Estudiante <small class="text-muted">tus datos personales</small></h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (!$persona || !$persona->estudiante)
            <div class="alert alert-warning mb-0">
                Esta cuenta todavía no está vinculada a una ficha de estudiante.
            </div>
        @else
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('estudiantes.file_estudiante.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" class="form-control" value="{{ $persona->nombre }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="primerapellido">Primer apellido:</label>
                        <input type="text" id="primerapellido" class="form-control" value="{{ $persona->primerapellido }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="segundoapellido">Segundo apellido:</label>
                        <input type="text" id="segundoapellido" class="form-control" value="{{ $persona->segundoapellido }}" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="ci">CI:</label>
                        <input type="text" id="ci" class="form-control" value="{{ $persona->ci }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="lugarexpedicion">Lugar de expedición:</label>
                        <input type="text" id="lugarexpedicion" class="form-control" value="{{ $persona->lugarexpedicion }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="fechanacimiento">Fecha de nacimiento:</label>
                        <input type="date" id="fechanacimiento" class="form-control" value="{{ $persona->fechanacimiento }}" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="lugarnacimiento">Lugar de nacimiento:</label>
                        <input type="text" id="lugarnacimiento" class="form-control" value="{{ $persona->lugarnacimiento }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="nacionalidad">Nacionalidad:</label>
                        <input type="text" id="nacionalidad" class="form-control" value="{{ $persona->nacionalidad }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="sexo">Sexo:</label>
                        <input type="text" id="sexo" class="form-control" value="{{ $persona->sexo }}" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="direccion">Dirección: <span class="text-danger">*</span></label>
                        <input type="text" name="direccion" id="direccion" class="form-control" value="{{ old('direccion', $persona->direccion) }}" required maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono', $persona->telefono) }}" maxlength="20">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="celular">Celular:</label>
                        <input type="text" name="celular" id="celular" class="form-control" value="{{ old('celular', $persona->celular) }}" maxlength="20">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="email">Correo electrónico:</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $persona->email) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="nombrecontacto">Nombre de contacto:</label>
                        <input type="text" name="nombrecontacto" id="nombrecontacto" class="form-control" value="{{ old('nombrecontacto', $persona->estudiante->nombrecontacto) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="celularcontacto">Celular de contacto:</label>
                        <input type="text" name="celularcontacto" id="celularcontacto" class="form-control" value="{{ old('celularcontacto', $persona->estudiante->celularcontacto) }}" maxlength="20">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="parentescocontacto">Parentesco de contacto:</label>
                        <input type="text" name="parentescocontacto" id="parentescocontacto" class="form-control" value="{{ old('parentescocontacto', $persona->estudiante->parentescocontacto) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="nombrecolegio">Nombre del colegio:</label>
                        <input type="text" name="nombrecolegio" id="nombrecolegio" class="form-control" value="{{ old('nombrecolegio', $persona->estudiante->nombrecolegio) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="caractercolegio">Carácter del colegio:</label>
                        <select name="caractercolegio" id="caractercolegio" class="form-control">
                            <option value="">Seleccione...</option>
                            @foreach (['Fiscal', 'Particular', 'Convenio'] as $caracter)
                                <option value="{{ $caracter }}" @selected(old('caractercolegio', $persona->estudiante->caractercolegio) === $caracter)>{{ $caracter }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="egresocolegio">Año de egreso del colegio:</label>
                        <input type="number" name="egresocolegio" id="egresocolegio" class="form-control" value="{{ old('egresocolegio', $persona->estudiante->egresocolegio) }}" min="1900" max="2100">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </form>
        @endif
    </div>
</div>
@stop
