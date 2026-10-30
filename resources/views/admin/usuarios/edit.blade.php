@extends('adminlte::page')
@section('title', 'Editar Usuario')

@section('content')
<div class="container-fluid pt-3">
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Editar Usuario: {{ $usuario->persona->nombre }}</h3>
        </div>
        
        <form action="{{ route('admin.usuarios.update', $usuario->id_persona) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label for="email">Email *</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            class="form-control @error('email') is-invalid @enderror" 
                            value="{{ old('email', $usuario->persona->email) }}" 
                            required>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="usuario">Usuario *</label>
                        <input 
                            type="text" 
                            name="usuario" 
                            id="usuario" 
                            class="form-control @error('usuario') is-invalid @enderror" 
                            value="{{ old('usuario', $usuario->usuario) }}" 
                            required>
                        @error('usuario')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="id_rol">Rol *</label>
                        <select 
                            name="id_rol" 
                            id="id_rol" 
                            class="form-control @error('id_rol') is-invalid @enderror" 
                            required>
                            <option value="">-- Seleccionar Rol --</option>
                            @foreach($roles as $rol)
                                <option 
                                    value="{{ $rol->id_rol }}" 
                                    {{ old('id_rol', $usuario->cuenta?->id_rol) == $rol->id_rol ? 'selected' : '' }}>
                                    {{ $rol->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_rol')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@stop
