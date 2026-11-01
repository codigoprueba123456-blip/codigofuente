@extends('adminlte::page')

@section('title', 'Cambio de Carrera')

@section('content_header')
    <h1>Cambio de Carrera</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        @include('partials.sweetalert-messages')

        <form action="{{ route('cambio_carrera.store') }}" method="POST" id="formCambioCarrera">
            @csrf
            
            <div class="row">
                <div class="col-md-6 form-group position-relative">
                    <label for="buscar_estudiante">Estudiante: <span class="text-danger">*</span></label>
                    <input type="search" id="buscar_estudiante" name="estudiante_nombre" class="form-control" value="{{ old('estudiante_nombre') }}" placeholder="Buscar por nombre, apellido o CI" autocomplete="off" required>
                    <input type="hidden" name="id_persona" id="id_persona" value="{{ old('id_persona') }}">
                    <div id="sugerencias_estudiante" class="list-group position-absolute w-100 shadow-sm" style="z-index: 20; left: 0; top: 100%;"></div>
                </div>

                <div class="col-md-6 form-group">
                    <div id="resumen_estudiante" class="alert alert-info mb-0" aria-live="polite">
                        Seleccione un estudiante para confirmar su identidad antes de registrar el cambio.
                    </div>
                </div>

                <div class="col-md-4 form-group">
                    <label for="fecha">Fecha: <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', now()->toDateString()) }}" required>
                </div>

                <div class="col-md-4 form-group">
                    <label for="becado">Becado:</label>
                    <select name="becado" id="becado" class="form-control">
                        <option value="No" @selected(old('becado', 'No') === 'No')>No</option>
                        <option value="Si" @selected(old('becado') === 'Si')>Sí</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="deposito">Comprobante de pago: <span class="text-danger">*</span></label>
                    <input type="text" name="deposito" id="deposito" class="form-control" value="{{ old('deposito') }}" required maxlength="255">
                </div>

                <div class="col-md-4 form-group">
                    <label for="traspaso_convalidaciones">Traspaso/Convalidación:</label>
                    <select name="traspaso_convalidaciones" id="traspaso_convalidaciones" class="form-control">
                        <option value="No" @selected(old('traspaso_convalidaciones', 'No') === 'No')>No</option>
                        <option value="Si" @selected(old('traspaso_convalidaciones') === 'Si')>Sí</option>
                    </select>
                </div>

                <div class="col-md-4 form-group">
                    <label for="id_carrera">Nueva carrera: <span class="text-danger">*</span></label>
                    <select name="id_carrera" id="id_carrera" class="form-control" required>
                        <option value="">Seleccione una carrera</option>
                        @foreach ($carreras as $carrera)
                            @php
                                $resoluciones = $carrera->resolucionesMinisteriales();
                            @endphp
                            <option value="{{ $carrera->id_carrera }}" @selected((string) old('id_carrera') === (string) $carrera->id_carrera)>
                                {{ $carrera->nombre }}
                                @if($resoluciones)
                                    · R.M. {{ implode(' / ', $resoluciones) }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="id_gestionacademica">Gestión académica: <span class="text-danger">*</span></label>
                    <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" required>
                        <option value="">Seleccione una gestión</option>
                        @foreach ($gestiones as $gestion)
                            <option value="{{ $gestion->id_gestionacademica }}" @selected((string) old('id_gestionacademica') === (string) $gestion->id_gestionacademica)>{{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}{{ $gestion->vigente ? ' (Vigente)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-default bg-light border">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="{{ route('cambio_carrera.index') }}" class="btn btn-default bg-light border ml-2">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>

        </form>
    </div>
</div>
@stop

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formulario = document.getElementById('formCambioCarrera');
        const entrada = document.getElementById('buscar_estudiante');
        const estudianteId = document.getElementById('id_persona');
        const sugerencias = document.getElementById('sugerencias_estudiante');
        const resumen = document.getElementById('resumen_estudiante');
        let espera;

        entrada.addEventListener('input', () => {
            window.clearTimeout(espera);
            estudianteId.value = '';
            resumen.textContent = 'Seleccione un estudiante para confirmar su identidad antes de registrar el cambio.';
            sugerencias.replaceChildren();

            const termino = entrada.value.trim();
            if (termino.length < 2) return;

            espera = window.setTimeout(async () => {
                try {
                    const respuesta = await fetch(@json(route('cambio_carrera.buscar')) + '?q=' + encodeURIComponent(termino), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!respuesta.ok) {
                        throw new Error('No se pudo consultar la lista de estudiantes.');
                    }

                    const estudiantes = await respuesta.json();
                    if (estudiantes.length === 0) {
                        const vacio = document.createElement('div');
                        vacio.className = 'list-group-item text-muted';
                        vacio.textContent = 'No se encontraron estudiantes.';
                        sugerencias.append(vacio);
                        return;
                    }

                    estudiantes.forEach((estudiante) => {
                        const opcion = document.createElement('button');
                        opcion.type = 'button';
                        opcion.className = 'list-group-item list-group-item-action';
                        opcion.textContent = `${estudiante.nombre} · CI ${estudiante.ci}`;
                        opcion.addEventListener('click', () => {
                            estudianteId.value = estudiante.id;
                            entrada.value = `${estudiante.nombre} · CI ${estudiante.ci}`;
                            resumen.replaceChildren();

                            const nombre = document.createElement('strong');
                            nombre.className = 'd-block';
                            nombre.textContent = estudiante.nombre;
                            const ci = document.createElement('span');
                            ci.className = 'd-block';
                            ci.textContent = `CI: ${estudiante.ci}`;
                            const carrera = document.createElement('span');
                            carrera.className = 'd-block';
                            carrera.textContent = `Carrera actual: ${estudiante.carrera_actual}`;
                            const contacto = document.createElement('span');
                            contacto.className = 'd-block';
                            contacto.textContent = `Celular: ${estudiante.celular || 'No registrado'} · ${estudiante.email || 'Sin correo'}`;
                            resumen.append(nombre, ci, carrera, contacto);
                            sugerencias.replaceChildren();
                        });
                        sugerencias.append(opcion);
                    });
                } catch (error) {
                    const fallo = document.createElement('div');
                    fallo.className = 'list-group-item text-danger';
                    fallo.textContent = error.message || 'No se pudo buscar al estudiante.';
                    sugerencias.replaceChildren(fallo);
                }
            }, 250);
        });

        formulario.addEventListener('submit', (evento) => {
            if (!estudianteId.value) {
                evento.preventDefault();
                entrada.focus();
            }
        });

        document.addEventListener('click', (evento) => {
            if (!entrada.parentElement.contains(evento.target)) {
                sugerencias.replaceChildren();
            }
        });
    });
</script>
@endpush