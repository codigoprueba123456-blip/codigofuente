@extends('adminlte::page')

@section('title', 'Cambio de Carrera')

@section('content_header')
    <h1>Cambio de Carrera</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        {{-- Muestra mensajes de confirmación o error devueltos por el controlador. --}}
        @include('partials.sweetalert-messages')

        {{-- Envía la inscripción de origen y la nueva carrera para solicitar el cambio. --}}
        <form action="{{ route('cambio_carrera.store') }}" method="POST" id="formCambioCarrera">
            @csrf
            
            <div class="row">
                {{-- La búsqueda obtiene estudiantes desde el servidor; el campo oculto guarda el identificador elegido. --}}
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
                <div class="col-md-12 form-group">
                    <div id="estado_inscripcion" class="alert alert-secondary mb-0" role="status" aria-live="polite">
                        Seleccione la inscripción que desea cambiar.
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
                    {{-- Se habilita después de elegir estudiante y muestra solo carreras que todavía no tiene inscritas. --}}
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
                    <label for="deposito">Voucher registrado (opcional):</label>
                    <input type="text" id="deposito" class="form-control" value="" readonly maxlength="255" placeholder="Seleccione una inscripción">
                    <small class="form-text text-muted">Se muestra el voucher de la inscripción seleccionada, si existe. El cambio no genera un pago nuevo.</small>
                </div>

            </div>

            <!-- Envía el cambio cuando la selección está completa o regresa al listado sin guardar. -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" id="guardar_cambio" class="btn btn-default bg-light border" disabled>
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

@include('partials.sweetalert-assets')

@push('js')
<script>
    // Espera a que la vista esté cargada antes de conectar los campos con sus eventos.
    document.addEventListener('DOMContentLoaded', () => {
        const formulario = document.getElementById('formCambioCarrera');
        const entrada = document.getElementById('buscar_estudiante');
        const estudianteId = document.getElementById('id_persona');
        const inscripcionOrigen = document.getElementById('id_inscrito_origen');
        const carreraDestino = document.getElementById('id_carrera');
        const sugerencias = document.getElementById('sugerencias_estudiante');
        const resumen = document.getElementById('resumen_estudiante');
        const estadoInscripcion = document.getElementById('estado_inscripcion');
        const deposito = document.getElementById('deposito');
        const botonGuardar = document.getElementById('guardar_cambio');
        let espera;
        let carrerasInscritas = new Set();
        let confirmacionReenviada = false;

        // Deshabilita carreras que el estudiante ya tiene y limpia una opción anterior si dejó de ser válida.
        function actualizarCarreraDestino() {
            carreraDestino.querySelectorAll('option').forEach((opcion) => {
                opcion.disabled = carrerasInscritas.has(opcion.value);
            });
            if (carreraDestino.selectedOptions[0]?.disabled) {
                carreraDestino.value = '';
            }
            actualizarEstadoInscripcion();
        }

        // Actualiza el estado de la inscripción elegida, el voucher visible y la disponibilidad del botón Guardar.
        // No permite continuar si la inscripción tiene materias asignadas; primero deben retirarse.
        function actualizarEstadoInscripcion() {
            const seleccion = inscripcionOrigen.selectedOptions[0];
            const hayInscripcion = Boolean(inscripcionOrigen.value);
            const tieneMaterias = hayInscripcion && seleccion.dataset.tieneMaterias === 'true';
            deposito.value = hayInscripcion ? (seleccion.dataset.deposito || '') : '';
            carreraDestino.disabled = !hayInscripcion || tieneMaterias;

            if (!hayInscripcion) {
                estadoInscripcion.className = 'alert alert-secondary mb-0';
                estadoInscripcion.textContent = 'Seleccione la inscripción que desea cambiar.';
            } else if (tieneMaterias) {
                estadoInscripcion.className = 'alert alert-warning mb-0';
                const candado = document.createElement('i');
                candado.className = 'fas fa-lock mr-2';
                candado.setAttribute('aria-hidden', 'true');
                estadoInscripcion.replaceChildren(
                    candado,
                    document.createTextNode('Esta inscripción tiene materias asignadas. Retírelas antes de cambiar de carrera.')
                );
            } else {
                estadoInscripcion.className = 'alert alert-success mb-0';
                estadoInscripcion.textContent = 'Esta inscripción no tiene materias asignadas. Puede continuar con el cambio.';
            }

            const destinoSeleccionado = carreraDestino.selectedOptions[0];
            botonGuardar.disabled = !estudianteId.value
                || !hayInscripcion
                || tieneMaterias
                || !carreraDestino.value
                || Boolean(destinoSeleccionado?.disabled);
        }

        inscripcionOrigen.addEventListener('change', actualizarCarreraDestino);
        carreraDestino.addEventListener('change', actualizarEstadoInscripcion);

        entrada.addEventListener('input', () => {
            // Una nueva búsqueda invalida las selecciones anteriores para evitar enviar datos de otra persona.
            window.clearTimeout(espera);
            estudianteId.value = '';
            carrerasInscritas.clear();
            inscripcionOrigen.replaceChildren(new Option('Primero seleccione un estudiante', ''));
            inscripcionOrigen.disabled = true;
            carreraDestino.value = '';
            resumen.textContent = 'Seleccione un estudiante para revisar sus inscripciones y elegir cuál cambiar.';
            sugerencias.replaceChildren();
            actualizarCarreraDestino();

            const termino = entrada.value.trim();
            if (termino.length < 2) return;

            // Retrasa la consulta para no llamar al servidor por cada tecla escrita.
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
                            // Guarda la persona elegida y carga sus inscripciones vigentes para seleccionar cuál cambiar.
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
                            carrerasInscritas = new Set(
                                estudiante.carreras_inscritas.map((idCarrera) => String(idCarrera))
                            );
                            inscripcionOrigen.replaceChildren(new Option('Seleccione una inscripción', ''));
                            estudiante.inscripciones.forEach((inscripcion) => {
                                const opcionInscripcion = new Option(
                                    `${inscripcion.carrera} · Gestión ${inscripcion.gestion} - Periodo ${inscripcion.periodo}${inscripcion.tiene_materias ? ' · [Bloqueada: tiene materias]' : ''}`,
                                    inscripcion.id
                                );
                                opcionInscripcion.dataset.carrera = inscripcion.id_carrera;
                                opcionInscripcion.dataset.nombreCarrera = inscripcion.carrera;
                                opcionInscripcion.dataset.tieneMaterias = String(inscripcion.tiene_materias);
                                opcionInscripcion.dataset.deposito = inscripcion.deposito || '';
                                inscripcionOrigen.add(opcionInscripcion);
                            });
                            inscripcionOrigen.disabled = estudiante.inscripciones.length === 0;
                            actualizarCarreraDestino();
                            if (estudiante.inscripciones.length === 0) {
                                // Informa cuando la persona no tiene una inscripción vigente que pueda cambiar.
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
                    // Presenta el error de búsqueda dentro de la lista, sin dejar datos anteriores visibles.
                    const fallo = document.createElement('div');
                    fallo.className = 'list-group-item text-danger';
                    fallo.textContent = error.message || 'No se pudo buscar al estudiante.';
                    sugerencias.replaceChildren(fallo);
                }
            }, 250);
        });

        // Valida las selecciones y pide confirmación antes de enviar el cambio al servidor.
        formulario.addEventListener('submit', async (evento) => {
            if (confirmacionReenviada) {
                confirmacionReenviada = false;
                return;
            }

            if (!estudianteId.value || !inscripcionOrigen.value) {
                evento.preventDefault();
                if (!estudianteId.value) {
                    entrada.focus();
                } else {
                    inscripcionOrigen.focus();
                }
                return;
            }

            if (!carreraDestino.value) {
                evento.preventDefault();
                carreraDestino.focus();
                return;
            }

            const seleccion = inscripcionOrigen.selectedOptions[0];
            if (seleccion.dataset.tieneMaterias === 'true') {
                evento.preventDefault();
                inscripcionOrigen.focus();
                return;
            }

            const nombreEstudiante = resumen.querySelector('strong')?.textContent || entrada.value;
            const carreraAnterior = seleccion.dataset.nombreCarrera;
            const carreraNueva = carreraDestino.selectedOptions[0]?.textContent.trim();
            evento.preventDefault();

            // Si la biblioteca de confirmación no cargó, no envía el cambio sin aprobación de la persona.
            if (!window.Swal?.fire) {
                estadoInscripcion.className = 'alert alert-danger mb-0';
                estadoInscripcion.textContent = 'No se pudo abrir la ventana de confirmación. Recarga la página e inténtalo nuevamente.';
                return;
            }

            const respuesta = await window.Swal.fire({
                icon: 'warning',
                title: 'Confirmar cambio de carrera',
                text: `¿Confirma cambiar la carrera de ${nombreEstudiante} de ${carreraAnterior} a ${carreraNueva}? No se generará un pago nuevo.`,
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar carrera',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: {
                    popup: 'itsa-swal-modal',
                },
            });

            if (respuesta.isConfirmed) {
                confirmacionReenviada = true;
                formulario.requestSubmit(botonGuardar);
            }
        });

        // Cierra la lista de sugerencias al hacer clic fuera del campo de búsqueda.
        document.addEventListener('click', (evento) => {
            if (!entrada.parentElement.contains(evento.target)) {
                sugerencias.replaceChildren();
            }
        });
    });
</script>
@endpush