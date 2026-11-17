@extends('adminlte::page')

@section('title', 'Estudiante Regular')

@section('content_header')
    <h1 class="m-0 text-dark">Estudiante Regular</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">

        @include('partials.sweetalert-messages')

        @if(!$gestionActual)
            <div class="alert alert-warning">No hay una gestión académica configurada para inscribir.</div>
        @endif

        <form action="{{ route('estudiante_regular.store') }}" method="POST" id="formEstudianteRegular">
            @csrf

            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-primary">
                        <i class="fas fa-user-graduate mr-1"></i> Datos del estudiante regular
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="estudiante_search" class="font-weight-bold">Buscar estudiante (C.I. o nombre): <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="estudiante_search"
                                class="form-control @error('id_persona') is-invalid @enderror"
                                placeholder="Buscar solo estudiantes por C.I. o nombre"
                                value="{{ old('estudiante_busqueda') }}"
                                autocomplete="off"
                                aria-autocomplete="list"
                                aria-controls="estudianteResultados"
                            >
                            <input type="hidden" name="id_persona" id="id_persona" value="{{ old('id_persona') }}">
                            <div id="estudianteResultados" class="list-group mt-1" role="listbox"></div>
                            @error('id_persona')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="estudianteSeleccionado" class="col-12 mb-3 d-none">
                            <div class="border rounded p-3">
                                <div class="font-weight-bold" id="nombreEstudianteSeleccionado"></div>
                                <div class="text-muted" id="ciEstudianteSeleccionado"></div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="fecha" class="font-weight-bold">Fecha: <span class="text-danger">*</span></label>
                            <input type="date" name="fecha" id="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', date('Y-m-d')) }}" required>
                            @error('fecha')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="becado" class="font-weight-bold">Becado:</label>
                            <select name="becado" id="becado" class="form-control custom-select @error('becado') is-invalid @enderror">
                                <option value="0" {{ old('becado', '0') == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('becado') == '1' ? 'selected' : '' }}>Sí</option>
                            </select>
                            @error('becado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="traspaso_convalidacion" class="font-weight-bold">Traspaso/Convalidación:</label>
                            <select name="traspaso_convalidacion" id="traspaso_convalidacion" class="form-control custom-select @error('traspaso_convalidacion') is-invalid @enderror" required>
                                <option value="0" {{ old('traspaso_convalidacion', '0') == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('traspaso_convalidacion') == '1' ? 'selected' : '' }}>Sí</option>
                            </select>
                            @error('traspaso_convalidacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Gestión académica:</label>
                            <input type="text" class="form-control" value="{{ $gestionActual ? $gestionActual->gestion.' - '.$gestionActual->periodo : '' }}" readonly>
                        </div>

                        <div class="col-12 mb-3">
                        <label class="font-weight-bold">Carreras, nivel que cursará y vouchers <span class="text-danger">*</span></label>
                            <div id="carrerasEstudiante" class="border rounded p-3 text-muted">Busca y selecciona un estudiante para cargar las carreras asociadas a su historial.</div>
                            @error('inscripciones')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light">
                    <button type="submit" id="guardarInscripcion" class="btn btn-primary font-weight-bold mr-2" disabled>
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <a href="{{ route('estudiante_regular.index') }}" class="btn btn-secondary font-weight-bold">
                        <i class="fas fa-undo mr-1"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@push('css')
<style>
    #estudianteResultados:not(:empty) {
        max-height: 240px;
        overflow-y: auto;
        border-radius: 4px;
    }

    #estudianteResultados .estudiante-sugerencia {
        color: #f5f8fa;
        background-color: #40545f;
        border-color: #647984;
    }

    #estudianteResultados .estudiante-sugerencia:hover,
    #estudianteResultados .estudiante-sugerencia:focus {
        color: #ffffff;
        background-color: #126b78;
        border-color: #54c4c5;
        outline: 2px solid #54c4c5;
        outline-offset: -2px;
    }

    #estudianteResultados .estudiante-ci {
        color: #d8e5e9;
    }

    .registrar-carrera.registrar-si {
        color: #155724;
        border-color: #28a745;
        background-color: #e8f5eb;
    }

    .registrar-carrera.registrar-no {
        color: #721c24;
        border-color: #dc3545;
        background-color: #fbeaec;
    }
</style>
@endpush

@push('js')
<script>
    const estudianteInput = document.getElementById('estudiante_search');
    const estudianteId = document.getElementById('id_persona');
    const resultados = document.getElementById('estudianteResultados');
    const carrerasContenedor = document.getElementById('carrerasEstudiante');
    const botonGuardar = document.getElementById('guardarInscripcion');
    const inscripcionesAnteriores = @json(old('inscripciones', []));
    const etiquetasNivelesPorRegimen = @json(\App\Models\Carrera::etiquetasNivelesPorRegimen());
    const urlOpcionesTurnos = @json(route('estudiante_regular.turnos'));
    const nombresTurno = { 1: 'Mañana', 2: 'Tarde', 3: 'Noche' };
    let temporizadorBusqueda;
    let solicitudBusqueda = 0;
    let indiceInscripcion = 0;

    function limpiarSeleccion(mensaje = 'Busca y selecciona un estudiante para cargar las carreras asociadas a su historial.') {
        estudianteId.value = '';
        document.getElementById('estudianteSeleccionado').classList.add('d-none');
        carrerasContenedor.replaceChildren();
        carrerasContenedor.textContent = mensaje;
        botonGuardar.disabled = true;
    }

    function mostrarCarreras(estudiante) {
        estudianteId.value = estudiante.id;
        document.getElementById('nombreEstudianteSeleccionado').textContent = estudiante.nombre;
        document.getElementById('ciEstudianteSeleccionado').textContent = `C.I.: ${estudiante.ci || 'Sin C.I.'}`;
        document.getElementById('traspaso_convalidacion').value = estudiante.traspaso_convalidacion ? '1' : '0';
        document.getElementById('estudianteSeleccionado').classList.remove('d-none');
        resultados.replaceChildren();
        carrerasContenedor.replaceChildren();
        indiceInscripcion = 0;
        const idsCarreraAgregadas = new Set(estudiante.carreras.map(carrera => Number(carrera.id)));
        const carrerasDisponibles = estudiante.carreras_disponibles || [];
        const preferenciaTurnoEstudiante = {
            turno: estudiante.turno_sugerido,
            paralelo: estudiante.paralelo_sugerido,
        };
        let selectCarrera;
        let botonAgregar;

        estudiante.carreras.forEach(carrera => {
            if (carrera.ya_inscrito) {
                const filaActual = document.createElement('div');
                filaActual.className = 'border-bottom py-2 text-muted';
                filaActual.textContent = `${nombreConResolucion(carrera)}: ya inscrito en esta gestión.`;
                carrerasContenedor.appendChild(filaActual);
                return;
            }

            agregarFilaCarrera(carrera, false);
        });

        inscripcionesAnteriores
            .filter(inscripcion => Number(inscripcion.nueva) === 1)
            .forEach(inscripcion => {
                const carrera = carrerasDisponibles.find(item => Number(item.id) === Number(inscripcion.id_carrera));
                if (carrera) {
                    agregarFilaCarrera(carrera, true, () => {
                        idsCarreraAgregadas.delete(Number(carrera.id));
                        if (selectCarrera && !selectCarrera.querySelector(`option[value="${carrera.id}"]`)) {
                            selectCarrera.append(new Option(nombreConResolucion(carrera), carrera.id));
                        }
                        if (botonAgregar) botonAgregar.disabled = false;
                    }, {
                        turno: inscripcion.turno || preferenciaTurnoEstudiante.turno,
                        paralelo: inscripcion.paralelo || preferenciaTurnoEstudiante.paralelo,
                    });
                    idsCarreraAgregadas.add(Number(carrera.id));
                }
            });

        const carrerasParaAgregar = carrerasDisponibles.filter(carrera => !idsCarreraAgregadas.has(Number(carrera.id)));
        if (carrerasDisponibles.length) {
            const controles = document.createElement('div');
            controles.className = 'pt-3';

            botonAgregar = document.createElement('button');
            botonAgregar.type = 'button';
            botonAgregar.className = 'btn btn-outline-primary font-weight-bold';
            botonAgregar.innerHTML = '<i class="fas fa-plus mr-1"></i> Añadir otra carrera';

            const selectorCarrera = document.createElement('div');
            selectorCarrera.className = 'form-row align-items-end mt-2 d-none';
            const columnaSelector = document.createElement('div');
            columnaSelector.className = 'col-md-8';
            const etiquetaCarrera = document.createElement('label');
            etiquetaCarrera.className = 'font-weight-bold';
            etiquetaCarrera.textContent = 'Nueva carrera:';
            selectCarrera = document.createElement('select');
            selectCarrera.className = 'form-control custom-select';
            selectCarrera.append(new Option('Selecciona una carrera...', ''));
            carrerasParaAgregar.forEach(carrera => {
                selectCarrera.append(new Option(nombreConResolucion(carrera), carrera.id));
            });
            botonAgregar.disabled = carrerasParaAgregar.length === 0;
            columnaSelector.append(etiquetaCarrera, selectCarrera);

            const columnaConfirmar = document.createElement('div');
            columnaConfirmar.className = 'col-md-4 mt-2 mt-md-0';
            const botonConfirmar = document.createElement('button');
            botonConfirmar.type = 'button';
            botonConfirmar.className = 'btn btn-primary';
            botonConfirmar.textContent = 'Agregar carrera';
            columnaConfirmar.appendChild(botonConfirmar);
            selectorCarrera.append(columnaSelector, columnaConfirmar);

            botonAgregar.addEventListener('click', () => selectorCarrera.classList.toggle('d-none'));
            botonConfirmar.addEventListener('click', () => {
                const carrera = carrerasParaAgregar.find(item => Number(item.id) === Number(selectCarrera.value));
                if (!carrera) return;

                agregarFilaCarrera(carrera, true, () => {
                    idsCarreraAgregadas.delete(Number(carrera.id));
                    if (selectCarrera && !selectCarrera.querySelector(`option[value="${carrera.id}"]`)) {
                        selectCarrera.append(new Option(nombreConResolucion(carrera), carrera.id));
                    }
                    if (botonAgregar) botonAgregar.disabled = false;
                }, preferenciaTurnoEstudiante);
                idsCarreraAgregadas.add(Number(carrera.id));
                selectCarrera.querySelector(`option[value="${carrera.id}"]`)?.remove();
                selectCarrera.value = '';
                if (!selectCarrera.options.length || selectCarrera.options.length === 1) {
                    botonAgregar.disabled = true;
                    selectorCarrera.classList.add('d-none');
                }
                actualizarBotonGuardar();
            });

            controles.append(botonAgregar, selectorCarrera);
            carrerasContenedor.appendChild(controles);
        }

        if (!estudiante.carreras.length && !carrerasDisponibles.length) {
            carrerasContenedor.textContent = 'No hay carreras disponibles para inscribir.';
        } else if (
            !estudiante.carreras.some(carrera => !carrera.ya_inscrito)
            && !inscripcionesAnteriores.some(inscripcion => Number(inscripcion.nueva) === 1)
            && !carrerasDisponibles.length
        ) {
            const estado = document.createElement('div');
            estado.className = 'text-muted';
            estado.textContent = 'El estudiante ya tiene inscripción en todas sus carreras durante esta gestión.';
            carrerasContenedor.appendChild(estado);
        }
        actualizarBotonGuardar();
    }

    function agregarFilaCarrera(carrera, nueva, alQuitar = null, preferenciaTurno = null) {
        const indice = indiceInscripcion++;
        const fila = document.createElement('div');
        fila.className = 'border-bottom py-3';
        const esAnual = Number(carrera.regimen) === {{ \App\Models\Carrera::REGIMEN_ANUAL }};
        fila.dataset.requiereTurno = esAnual ? 'true' : 'false';
        const seleccionAnterior = inscripcionesAnteriores.find(item => Number(item.id_carrera) === Number(carrera.id));
        const turnoInicial = seleccionAnterior?.turno || preferenciaTurno?.turno || carrera.turno_sugerido || '';
        const paraleloInicial = seleccionAnterior?.paralelo || preferenciaTurno?.paralelo || carrera.paralelo_sugerido || '';
        const filaCampos = document.createElement('div');
        filaCampos.className = 'row align-items-start';

        const nombre = document.createElement('div');
        nombre.className = 'col-md-3 font-weight-bold text-primary';
        const nombreCarrera = document.createElement('div');
        nombreCarrera.textContent = nombreConResolucion(carrera);
        nombre.appendChild(nombreCarrera);
        if (carrera.historial?.length) {
            const resumen = document.createElement('small');
            resumen.className = 'd-block text-muted font-weight-normal mt-1';
            resumen.textContent = `Historial: ${carrera.historial.map(registro =>
                `${registro.gestion}/${registro.periodo} · ${registro.nivel ? nombreNivel(registro.nivel, registro.regimen) : 'nivel sin registrar'}`
            ).join(', ')}`;
            nombre.appendChild(resumen);
        } else if (nueva) {
            const resumen = document.createElement('small');
            resumen.className = 'd-block text-muted font-weight-normal mt-1';
            resumen.textContent = 'Nueva carrera para este estudiante';
            nombre.appendChild(resumen);
        }
        filaCampos.appendChild(nombre);

        const registrar = document.createElement('div');
        registrar.className = 'col-md-2';
        const etiquetaRegistrar = document.createElement('label');
        etiquetaRegistrar.className = 'font-weight-bold';
        etiquetaRegistrar.textContent = 'Registrar:';
        etiquetaRegistrar.htmlFor = `registrar-${indice}`;
        const seleccionRegistrar = document.createElement('select');
        seleccionRegistrar.id = `registrar-${indice}`;
        seleccionRegistrar.name = `inscripciones[${indice}][registrar]`;
        seleccionRegistrar.className = 'form-control registrar-carrera';
        seleccionRegistrar.append(new Option('Sí', '1'), new Option('No', '0'));
        seleccionRegistrar.value = seleccionAnterior?.registrar ?? '1';
        registrar.append(etiquetaRegistrar, seleccionRegistrar);

        const nivel = document.createElement('div');
        nivel.className = esAnual ? 'col-md-2' : 'col-md-3';
        const etiquetaNivel = document.createElement('label');
        etiquetaNivel.className = 'font-weight-bold';
        const etiquetasNivel = etiquetasNivelesPorRegimen[carrera.regimen] || {};
        etiquetaNivel.textContent = esAnual ? 'Año que cursará:' : 'Semestre que cursará:';
        etiquetaNivel.htmlFor = `nivel-${indice}`;
        const seleccionNivel = document.createElement('select');
        seleccionNivel.id = `nivel-${indice}`;
        seleccionNivel.name = `inscripciones[${indice}][nivelinicio]`;
        seleccionNivel.className = 'form-control';
        seleccionNivel.required = true;
        seleccionNivel.append(new Option(`Selecciona ${esAnual ? 'el año' : 'el semestre'}...`, ''));
        Object.entries(etiquetasNivel).forEach(([valor, etiqueta]) => {
            seleccionNivel.append(new Option(etiqueta, valor));
        });
        seleccionNivel.value = seleccionAnterior?.nivelinicio || carrera.nivel_sugerido || (nueva ? 1 : '');
        nivel.append(etiquetaNivel, seleccionNivel);

        let seleccionTurno;
        let seleccionParalelo;
        let estadoGrupos;
        let columnaTurno;
        let columnaParalelo;
        if (esAnual) {
            columnaTurno = document.createElement('div');
            columnaTurno.className = 'col-md-2';
            const etiquetaTurno = document.createElement('label');
            etiquetaTurno.className = 'font-weight-bold';
            etiquetaTurno.textContent = 'Turno:';
            etiquetaTurno.htmlFor = `turno-${indice}`;
            seleccionTurno = document.createElement('select');
            seleccionTurno.id = `turno-${indice}`;
            seleccionTurno.name = `inscripciones[${indice}][turno]`;
            seleccionTurno.className = 'form-control custom-select turno-carrera';
            seleccionTurno.required = true;
            seleccionTurno.disabled = true;
            seleccionTurno.append(new Option('Cargando...', ''));
            columnaTurno.append(etiquetaTurno, seleccionTurno);

            columnaParalelo = document.createElement('div');
            columnaParalelo.className = 'col-md-3';
            const etiquetaParalelo = document.createElement('label');
            etiquetaParalelo.className = 'font-weight-bold';
            etiquetaParalelo.textContent = 'Paralelo:';
            etiquetaParalelo.htmlFor = `paralelo-${indice}`;
            seleccionParalelo = document.createElement('select');
            seleccionParalelo.id = `paralelo-${indice}`;
            seleccionParalelo.name = `inscripciones[${indice}][paralelo]`;
            seleccionParalelo.className = 'form-control custom-select paralelo-carrera';
            seleccionParalelo.required = true;
            seleccionParalelo.disabled = true;
            seleccionParalelo.append(new Option('Selecciona un turno primero...', ''));
            estadoGrupos = document.createElement('small');
            estadoGrupos.className = 'form-text text-muted';
            columnaParalelo.append(etiquetaParalelo, seleccionParalelo, estadoGrupos);
        }

        const filaVoucher = document.createElement('div');
        filaVoucher.className = 'row align-items-end mt-2';
        const deposito = document.createElement('div');
        deposito.className = nueva ? 'col-md-9' : 'col-md-12';
        const etiqueta = document.createElement('label');
        etiqueta.className = 'font-weight-bold';
        etiqueta.textContent = 'Voucher (N° de depósito):';
        etiqueta.htmlFor = `deposito-${indice}`;
        const campo = document.createElement('input');
        campo.type = 'text';
        campo.id = `deposito-${indice}`;
        campo.name = `inscripciones[${indice}][deposito]`;
        campo.className = 'form-control';
        campo.placeholder = 'Ingresar voucher de esta carrera';
        campo.required = true;
        campo.value = seleccionAnterior?.deposito || '';
        const idCampo = document.createElement('input');
        idCampo.type = 'hidden';
        idCampo.name = `inscripciones[${indice}][id_carrera]`;
        idCampo.value = carrera.id;
        const nuevaCampo = document.createElement('input');
        nuevaCampo.type = 'hidden';
        nuevaCampo.name = `inscripciones[${indice}][nueva]`;
        nuevaCampo.value = nueva ? '1' : '0';
        deposito.append(etiqueta, campo, idCampo, nuevaCampo);
        filaVoucher.appendChild(deposito);

        if (nueva) {
            const columnaAccion = document.createElement('div');
            columnaAccion.className = 'col-md-3 d-flex justify-content-md-end mt-2 mt-md-0';
            const botonQuitar = document.createElement('button');
            botonQuitar.type = 'button';
            botonQuitar.className = 'btn btn-outline-danger';
            botonQuitar.textContent = 'Quitar';
            botonQuitar.setAttribute('aria-label', `Quitar ${carrera.nombre}`);
            botonQuitar.addEventListener('click', () => {
                fila.remove();
                alQuitar?.();
                actualizarBotonGuardar();
            });
            columnaAccion.appendChild(botonQuitar);
            filaVoucher.appendChild(columnaAccion);
        }

        const actualizarEstado = () => {
            const registrarCarrera = seleccionRegistrar.value === '1';
            seleccionRegistrar.classList.toggle('registrar-si', registrarCarrera);
            seleccionRegistrar.classList.toggle('registrar-no', !registrarCarrera);
            [seleccionNivel, campo, seleccionTurno, seleccionParalelo].filter(Boolean).forEach(input => {
                input.disabled = !registrarCarrera;
                input.required = registrarCarrera;
            });
            nivel.classList.toggle('d-none', !registrarCarrera);
            deposito.classList.toggle('d-none', !registrarCarrera);
            columnaTurno?.classList.toggle('d-none', !registrarCarrera);
            columnaParalelo?.classList.toggle('d-none', !registrarCarrera);
            estadoGrupos?.classList.toggle('d-none', !registrarCarrera);
            actualizarBotonGuardar();
        };
        seleccionRegistrar.addEventListener('change', actualizarEstado);
        seleccionNivel.addEventListener('change', () => {
            if (esAnual && seleccionRegistrar.value === '1') {
                cargarOpcionesTurnos(
                    seleccionTurno.value || turnoInicial,
                    seleccionParalelo.value || paraleloInicial
                );
            }
        });
        seleccionTurno?.addEventListener('change', () => {
            actualizarParalelos(turnoInicial === Number(seleccionTurno.value) ? paraleloInicial : '');
            actualizarBotonGuardar();
        });
        seleccionParalelo?.addEventListener('change', actualizarBotonGuardar);

        let solicitudGrupos = 0;
        let opcionesGrupos = [];
        function actualizarParalelos(paraleloPreferido = '') {
            if (!seleccionParalelo || !seleccionTurno) return;
            const paralelos = opcionesGrupos.filter(opcion =>
                String(opcion.turno) === seleccionTurno.value
            );
            seleccionParalelo.replaceChildren();
            if (paralelos.length !== 1) {
                seleccionParalelo.add(new Option('Selecciona un paralelo...', ''));
            }
            paralelos.forEach(opcion => {
                seleccionParalelo.add(new Option(`Paralelo ${opcion.paralelo}`, opcion.paralelo));
            });
            seleccionParalelo.value = paralelos.some(opcion => opcion.paralelo === paraleloPreferido)
                ? paraleloPreferido
                : (paralelos.length === 1 ? paralelos[0].paralelo : '');
            seleccionParalelo.disabled = seleccionRegistrar.value !== '1' || paralelos.length === 0;
            if (paralelos.length === 0) {
                seleccionParalelo.replaceChildren(new Option('Sin paralelos disponibles', ''));
            }
        }

        async function cargarOpcionesTurnos(turnoPreferido = '', paraleloPreferido = '') {
            if (!esAnual || !seleccionTurno || !seleccionParalelo) return;
            const solicitud = ++solicitudGrupos;
            opcionesGrupos = [];
            seleccionTurno.disabled = true;
            seleccionParalelo.disabled = true;
            seleccionTurno.replaceChildren(new Option('Cargando...', ''));
            seleccionParalelo.replaceChildren(new Option('Cargando...', ''));
            estadoGrupos.textContent = 'Consultando grupos abiertos de la gestión vigente...';

            try {
                const url = new URL(urlOpcionesTurnos, window.location.origin);
                url.searchParams.set('id_carrera', carrera.id);
                url.searchParams.set('nivelinicio', seleccionNivel.value);
                const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!respuesta.ok) {
                    throw new Error('No se pudieron cargar los grupos de esta carrera.');
                }
                const datos = await respuesta.json();
                if (solicitud !== solicitudGrupos) return;
                opcionesGrupos = Array.isArray(datos.opciones) ? datos.opciones : [];

                const turnos = [...new Set(opcionesGrupos.map(opcion => Number(opcion.turno)))].sort((a, b) => a - b);
                seleccionTurno.replaceChildren();
                if (turnos.length !== 1) {
                    seleccionTurno.add(new Option('Selecciona un turno...', ''));
                }
                turnos.forEach(turno => {
                    seleccionTurno.add(new Option(nombresTurno[turno] || `Turno ${turno}`, turno));
                });
                const turnoPrevioDisponible = turnos.includes(Number(turnoPreferido));
                seleccionTurno.value = turnoPrevioDisponible
                    ? String(turnoPreferido)
                    : (turnos.length === 1 ? String(turnos[0]) : '');
                seleccionTurno.disabled = seleccionRegistrar.value !== '1' || turnos.length === 0;
                actualizarParalelos(
                    turnoPrevioDisponible && Number(turnoPreferido) === Number(seleccionTurno.value)
                        ? paraleloPreferido
                        : ''
                );
                estadoGrupos.textContent = opcionesGrupos.length
                    ? 'Opciones tomadas de los grupos abiertos de la gestión vigente.'
                    : 'No hay grupos abiertos para esta carrera y año en la gestión vigente.';
                actualizarBotonGuardar();
            } catch (error) {
                if (solicitud !== solicitudGrupos) return;
                seleccionTurno.replaceChildren(new Option('No disponible', ''));
                seleccionParalelo.replaceChildren(new Option('No disponible', ''));
                estadoGrupos.textContent = error.message || 'No se pudieron cargar los grupos.';
                actualizarBotonGuardar();
            }
        }

        actualizarEstado();
        filaCampos.append(registrar, nivel);
        if (esAnual) {
            filaCampos.append(columnaTurno, columnaParalelo);
        }
        fila.append(filaCampos, filaVoucher);
        carrerasContenedor.appendChild(fila);
        if (esAnual && seleccionRegistrar.value === '1') {
            cargarOpcionesTurnos(turnoInicial, paraleloInicial);
        }
    }

    function nombreNivel(nivel, regimen) {
        return etiquetasNivelesPorRegimen[regimen]?.[nivel] || `Nivel ${nivel}`;
    }

    function actualizarBotonGuardar() {
        botonGuardar.disabled = ![...carrerasContenedor.querySelectorAll('.registrar-carrera')]
            .some(select => {
                if (select.value !== '1') return false;
                const fila = select.closest('[data-requiere-turno]');
                if (fila?.dataset.requiereTurno !== 'true') return true;
                return Boolean(
                    fila.querySelector('.turno-carrera')?.value
                    && fila.querySelector('.paralelo-carrera')?.value
                );
            });
    }

    function nombreConResolucion(carrera) {
        const resoluciones = (carrera.resoluciones || []).filter(Boolean);
        return resoluciones.length
            ? `${carrera.nombre} · R.M. ${resoluciones.join(' / ')}`
            : carrera.nombre;
    }

    async function buscarEstudiantes(parametros) {
        const solicitud = ++solicitudBusqueda;
        const url = new URL(@json(route('estudiante_regular.buscar')), window.location.origin);
        Object.entries(parametros).forEach(([clave, valor]) => url.searchParams.set(clave, valor));
        const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
        const estudiantes = await respuesta.json();
        if (solicitud !== solicitudBusqueda) return estudiantes;
        return estudiantes;
    }

    function seleccionarEstudiante(estudiante) {
        estudianteInput.value = `${estudiante.nombre} - C.I.: ${estudiante.ci || 'Sin C.I.'}`;
        mostrarCarreras(estudiante);
    }

    estudianteInput.addEventListener('input', () => {
        clearTimeout(temporizadorBusqueda);
        solicitudBusqueda++;
        limpiarSeleccion('Busca y selecciona un estudiante para cargar las carreras asociadas a su historial.');
        resultados.replaceChildren();
        const termino = estudianteInput.value.trim();
        if (termino.length < 2) return;

        temporizadorBusqueda = setTimeout(async () => {
            resultados.textContent = 'Buscando...';
            try {
                const estudiantes = await buscarEstudiantes({ q: termino });
                if (!estudiantes.length) {
                    resultados.textContent = 'No se encontraron estudiantes.';
                    return;
                }
                resultados.replaceChildren();
                estudiantes.forEach(estudiante => {
                    const opcion = document.createElement('button');
                    opcion.type = 'button';
                    opcion.className = 'list-group-item list-group-item-action estudiante-sugerencia';
                    opcion.setAttribute('role', 'option');
                    const nombre = document.createElement('span');
                    nombre.className = 'font-weight-bold';
                    nombre.textContent = estudiante.nombre;
                    const ci = document.createElement('span');
                    ci.className = 'estudiante-ci small';
                    ci.textContent = `C.I.: ${estudiante.ci || 'Sin C.I.'}`;
                    opcion.append(nombre, ci);
                    opcion.addEventListener('click', () => seleccionarEstudiante(estudiante));
                    resultados.appendChild(opcion);
                });
            } catch (error) {
                resultados.textContent = 'No fue posible buscar estudiantes. Intenta nuevamente.';
            }
        }, 250);
    });

    document.getElementById('formEstudianteRegular').addEventListener('submit', function (event) {
        const filasParaRegistrar = [...carrerasContenedor.querySelectorAll('.registrar-carrera')]
            .filter(select => select.value === '1');
        if (filasParaRegistrar.length === 0) {
            event.preventDefault();
            if (typeof window.itsaAlert === 'function') {
                window.itsaAlert('warning', 'Selecciona una carrera', 'Marca Registrar: Sí en al menos una carrera para continuar.');
            }
            return;
        }

        const faltaTurno = filasParaRegistrar.some(select => {
            const fila = select.closest('[data-requiere-turno]');
            return fila?.dataset.requiereTurno === 'true'
                && (!fila.querySelector('.turno-carrera')?.value || !fila.querySelector('.paralelo-carrera')?.value);
        });
        if (faltaTurno) {
            event.preventDefault();
            if (typeof window.itsaAlert === 'function') {
                window.itsaAlert('warning', 'Selecciona turno y paralelo', 'Cada carrera anual que se registre necesita un turno y paralelo abierto.');
            }
            return;
        }

        botonGuardar.disabled = true;
        botonGuardar.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
    });

    if (estudianteId.value) {
        buscarEstudiantes({ id_persona: estudianteId.value }).then(estudiantes => {
            if (estudiantes.length) seleccionarEstudiante(estudiantes[0]);
        });
    }
</script>
@endpush
