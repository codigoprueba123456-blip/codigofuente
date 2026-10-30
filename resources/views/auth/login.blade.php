@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('auth_header', 'Iniciar Sesión')

@section('auth_body')
    <form action="{{ route('login') }}" method="post">
        @csrf

        {{-- Campo Usuario --}}
        <div class="input-group mb-3">
            <input type="text" name="usuario" class="form-control @error('usuario') is-invalid @enderror"
                   value="{{ old('usuario') }}" placeholder="Usuario" autofocus required>

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-user"></span>
                </div>
            </div>

            @error('usuario')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Campo Contraseña --}}
        <div class="input-group mb-3">
            <input type="password" name="contrasena" class="form-control @error('contrasena') is-invalid @enderror"
                   placeholder="Contraseña" required>

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>

            @error('contrasena')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Botón de Ingreso y Recordar --}}
        <div class="row">
            <div class="col-7">
                <div class="icheck-primary">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">
                        Recordarme
                    </label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-primary btn-block">
                    <span class="fas fa-sign-in-alt mr-1"></span> Ingresar
                </button>
            </div>
        </div>
    </form>
@stop

@section('auth_footer')
    {{-- Se deja vacío para eliminar los enlaces de "I forgot my password" y "Register" --}}
@stop