@extends('adminlte::page')

@section('title', 'Cambiar contraseña')

@section('content_header')
    <h1>Cambiar contraseña</h1>
@stop

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Configura tu contraseña</h3>
            </div>
            <form action="{{ route('account.password.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card-body">
                    <p class="text-muted">
                        Ingresa tu contraseña actual y luego elige una nueva de al menos 8 caracteres.
                    </p>

                    <div class="form-group">
                        <label for="current_password">Contraseña actual</label>
                        <input
                            type="password"
                            name="current_password"
                            id="current_password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            autocomplete="current-password"
                            required
                        >
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Nueva contraseña</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="new-password"
                            minlength="8"
                            maxlength="128"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="password_confirmation">Confirmar nueva contraseña</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                            minlength="8"
                            maxlength="128"
                            required
                        >
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('home') }}" class="btn btn-light border">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-key mr-1"></i> Guardar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop
