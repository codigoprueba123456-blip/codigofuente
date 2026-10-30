@extends('adminlte::page')

@section('title', 'Configuración')

@section('content_header')
    <h1>Configuración del Instituto</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Datos Generales</h3>
    </div>
    @if(session('success'))
        <div class="alert alert-success m-3 mb-0" role="alert">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger m-3 mb-0" role="alert">
            <p class="mb-1">No se pudieron guardar los datos. Revisa los siguientes campos:</p>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('admin.instituto.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label for="nombreinstituto">Nombre del instituto *</label>
                <input type="text" id="nombreinstituto" name="nombreinstituto" class="form-control @error('nombreinstituto') is-invalid @enderror" value="{{ old('nombreinstituto', $instituto->nombreinstituto) }}" maxlength="255" required>
                @error('nombreinstituto')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="logo">Logo de la institución</label>
                <input
                    type="file"
                    id="logo"
                    name="logo"
                    class="form-control @error('logo') is-invalid @enderror"
                    accept="image/*"
                    data-image-preview="logo-preview"
                >
                @include('partials.image-preview', [
                    'id' => 'logo-preview',
                    'src' => $instituto->logo ? url('storage/' . $instituto->logo) : null,
                    'alt' => 'Previsualización del logo de la institución',
                    'emptyLabel' => 'El instituto no tiene un logo guardado.',
                ])
                @error('logo')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nombre regulador *</label>
                        <input type="text" name="nombreregulador" class="form-control @error('nombreregulador') is-invalid @enderror" value="{{ old('nombreregulador', $instituto->nombreregulador) }}" maxlength="255" required>
                        @error('nombreregulador')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ubicación *</label>
                        <input type="text" name="ubicacion" class="form-control @error('ubicacion') is-invalid @enderror" value="{{ old('ubicacion', $instituto->ubicacion) }}" maxlength="255" required>
                        @error('ubicacion')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Carácter *</label>
                        <select name="caracter" class="form-control @error('caracter') is-invalid @enderror" required>
                            <option value="">Seleccione...</option>
                            <option value="FISCAL" {{ old('caracter', $instituto->caracter) === 'FISCAL' ? 'selected' : '' }}>FISCAL</option>
                            <option value="PRIVADO" {{ old('caracter', $instituto->caracter) === 'PRIVADO' ? 'selected' : '' }}>PRIVADO</option>
                        </select>
                        @error('caracter')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nombre impuestos</label>
                        <input type="text" name="nombreimpuestos" class="form-control @error('nombreimpuestos') is-invalid @enderror" value="{{ old('nombreimpuestos', $instituto->nombreimpuestos) }}" maxlength="255">
                        @error('nombreimpuestos')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>NIT</label>
                        <input type="text" name="nit" class="form-control @error('nit') is-invalid @enderror" value="{{ old('nit', $instituto->nit) }}" maxlength="255">
                        @error('nit')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Actividad económica</label>
                        <input type="text" name="actividadeconomica" class="form-control @error('actividadeconomica') is-invalid @enderror" value="{{ old('actividadeconomica', $instituto->actividadeconomica) }}" maxlength="255">
                        @error('actividadeconomica')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Precio crédito *</label>
                        <input type="number" name="preciocredito" class="form-control @error('preciocredito') is-invalid @enderror" min="0" value="{{ old('preciocredito', $instituto->preciocredito) }}" required>
                        @error('preciocredito')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Resolución ministerial *</label>
                        <input type="text" name="resolucionministerial" class="form-control @error('resolucionministerial') is-invalid @enderror" value="{{ old('resolucionministerial', $instituto->resolucionministerial) }}" maxlength="255" required>
                        @error('resolucionministerial')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Abreviatura *</label>
                        <input type="text" name="abreviatura" class="form-control @error('abreviatura') is-invalid @enderror" value="{{ old('abreviatura', $instituto->abreviatura) }}" maxlength="255" required>
                        @error('abreviatura')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Token</label>
                        <input type="text" name="token" class="form-control @error('token') is-invalid @enderror" value="{{ old('token', $instituto->token) }}" maxlength="255">
                        @error('token')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="#" class="btn btn-default">Cancelar</a>
        </div>
    </form>
</div>
@stop