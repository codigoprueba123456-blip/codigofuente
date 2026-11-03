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
                        Seleccione un estudiante para revisar sus inscripciones y elegir cuál cambiar.
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label for="id_inscrito_origen">Carrera que se cambiará: <span class="text-danger">*</span></label>
                    <select name="id_inscrito_origen" id="id_inscrito_origen" class="form-control" required disabled>
                        <option value="">Primero seleccione un estudiante</option>
                    </select>
                    @error('id_inscrito_origen')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 form-group">
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
                    @error('id_carrera')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
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
                    @error('deposito')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4 form-group">
                    <label for="monto">Monto del cambio (Bs.): <span class="text-danger">*</span></label>
                    <input type="number" name="monto" id="monto" class="form-control" value="{{ old('monto') }}" min="0" max="9999999999.99" step="0.01" required>
                    @error('monto')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
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
        const inscripcionOrigen = document.getElementById('id_inscrito_origen');
        const carreraDestino = document.getElementById('id_carrera');
        const sugerencias = document.getElementById('sugerencias_estudiante');
        const resumen = document.getElementById('resumen_estudiante');
        let espera;

        function actualizarCarreraDestino() {
            const carreraOrigen = inscripcionOrigen.selectedOptions[0]?.dataset.carrera;
            carreraDestino.querySelectorAll('option').forEach((opcion) => {
                opcion.disabled = Boolean(carreraOrigen && opcion.value === carreraOrigen);
            });
            if (carreraDestino.selectedOptions[0]?.disabled) {
                carreraDestino.value = '';
            }
        }

        inscripcionOrigen.addEventListener('change', actualizarCarreraDestino);

        entrada.addEventListener('input', () => {
            window.clearTimeout(espera);
            estudianteId.value = '';
            inscripcionOrigen.replaceChildren(new Option('Primero seleccione un estudiante', ''));
            inscripcionOrigen.disabled = true;
            actualizarCarreraDestino();
            resumen.textContent = 'Seleccione un estudiante para revisar sus inscripciones y elegir cuál cambiar.';
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
                            const contacto = document.createElement('span');
                            contacto.className = 'd-block';
                            contacto.textContent = `Celular: ${estudiante.celular || 'No registrado'} · ${estudiante.email || 'Sin correo'}`;
                            resumen.append(nombre, ci, contacto);
                            inscripcionOrigen.replaceChildren(new Option('Seleccione una inscripción', ''));
                            estudiante.inscripciones.forEach((inscripcion) => {
                                const opcionInscripcion = new Option(
                                    `${inscripcion.carrera} · Gestión ${inscripcion.gestion} - Periodo ${inscripcion.periodo}`,
                                    inscripcion.id
                                );
                                opcionInscripcion.dataset.carrera = inscripcion.id_carrera;
                                inscripcionOrigen.add(opcionInscripcion);
                            });
                            inscripcionOrigen.disabled = estudiante.inscripciones.length === 0;
                            actualizarCarreraDestino();
                            if (estudiante.inscripciones.length === 0) {
                                inscripcionOrigen.replaceChildren(new Option('No tiene inscripciones vigentes para cambiar', ''));
                                const aviso = document.createElement('span');
                                aviso.className = 'd-block text-warning mt-2';
                                aviso.textContent = 'No se encontraron carreras vigentes en ninguna gestión.';
                                resumen.append(aviso);
                            }
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
            if (!estudianteId.value || !inscripcionOrigen.value) {
                evento.preventDefault();
                if (!estudianteId.value) {
                    entrada.focus();
                } else {
                    inscripcionOrigen.focus();
                }
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