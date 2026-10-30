@extends('adminlte::page')

@section('title', 'Estudiante Regular')

@section('content_header')
    <h1 class="m-0 text-dark">Estudiante Regular</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

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
                            <label class="font-weight-bold">Carreras, nivel autorizado y vouchers <span class="text-danger">*</span></label>
                            <div id="carrerasEstudiante" class="border rounded p-3 text-muted">Busca y selecciona un estudiante para cargar su historial y las carreras disponibles.</div>
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
    let temporizadorBusqueda;
    let solicitudBusqueda = 0;

    function limpiarSeleccion(mensaje = 'Busca y selecciona un estudiante para cargar su historial y las carreras disponibles.') {
        estudianteId.value = '';
        document.getElementById('estudianteSeleccionado').classList.add('d-none');
        carrerasContenedor.replaceChildren();
        carrerasContenedor.textContent = mensaje;
        botonGuardar.disabled = true;
    }

    function mostrarCarreras(estudiante) {
        estudianteId.value = estudiante.id;
        document.getElementById('nombreEstudianteSeleccionado').textContent = estudiante.nombre;
        document.getElementById('ciEstudianteSeleccionado').textContent = `C.I.: ${estudiante.dni || 'Sin C.I.'}`;
        document.getElementById('traspaso_convalidacion').value = estudiante.traspaso_convalidacion ? '1' : '0';
        document.getElementById('estudianteSeleccionado').classList.remove('d-none');
        resultados.replaceChildren();
        carrerasContenedor.replaceChildren();

        if (!estudiante.carreras.length) {
            carrerasContenedor.textContent = 'No hay carreras disponibles para inscribir.';
            botonGuardar.disabled = true;
            return;
        }

        let carrerasPendientes = 0;
        estudiante.carreras.forEach((carrera, indice) => {
            if (carrera.ya_inscrito) {
                const filaActual = document.createElement('div');
                filaActual.className = 'border-bottom py-2 text-muted';
                filaActual.textContent = `${carrera.nombre}: ya inscrito en esta gestión.`;
                carrerasContenedor.appendChild(filaActual);
                return;
            }

            const fila = document.createElement('div');
            fila.className = 'row align-items-end border-bottom py-3';

            const nombre = document.createElement('div');
            nombre.className = 'col-md-4 font-weight-bold text-primary';
            const nombreCarrera = document.createElement('div');
            nombreCarrera.textContent = carrera.nombre;
            nombre.appendChild(nombreCarrera);
            if (carrera.historial?.length) {
                const resumen = document.createElement('small');
                resumen.className = 'd-block text-muted font-weight-normal mt-1';
                resumen.textContent = `Historial: ${carrera.historial.map(registro =>
                    `${registro.gestion}/${registro.periodo} · ${registro.nivel || 'nivel sin registrar'}°`
                ).join(', ')}`;
                nombre.appendChild(resumen);
            }
            fila.appendChild(nombre);

            carrerasPendientes++;
            const nivel = document.createElement('div');
            nivel.className = 'col-md-3';
            const etiquetaNivel = document.createElement('label');
            etiquetaNivel.className = 'font-weight-bold';
            etiquetaNivel.textContent = 'Nivel autorizado:';
            etiquetaNivel.htmlFor = `nivel-${carrera.id}`;
            const seleccionNivel = document.createElement('select');
            seleccionNivel.id = `nivel-${carrera.id}`;
            seleccionNivel.name = `inscripciones[${indice}][nivelinicio]`;
            seleccionNivel.className = 'form-control';
            seleccionNivel.required = true;
            seleccionNivel.append(new Option('Seleccione nivel...', ''));
            for (let numero = 1; numero <= 10; numero++) {
                seleccionNivel.append(new Option(`${numero}° nivel`, numero));
            }
            const nivelAnterior = inscripcionesAnteriores.find(item => Number(item.id_carrera) === Number(carrera.id))?.nivelinicio;
            seleccionNivel.value = nivelAnterior || carrera.nivel_sugerido || '';
            nivel.append(etiquetaNivel, seleccionNivel);

            const deposito = document.createElement('div');
            deposito.className = 'col-md-5';
            const etiqueta = document.createElement('label');
            etiqueta.className = 'font-weight-bold';
            etiqueta.textContent = 'Voucher (N° de depósito):';
            etiqueta.htmlFor = `deposito-${carrera.id}`;
            const campo = document.createElement('input');
            campo.type = 'text';
            campo.id = `deposito-${carrera.id}`;
            campo.name = `inscripciones[${indice}][deposito]`;
            campo.className = 'form-control';
            campo.placeholder = 'Ingresar voucher de esta carrera';
            campo.required = true;
            campo.value = inscripcionesAnteriores.find(item => Number(item.id_carrera) === Number(carrera.id))?.deposito || '';
            const idCampo = document.createElement('input');
            idCampo.type = 'hidden';
            idCampo.name = `inscripciones[${indice}][id_carrera]`;
            idCampo.value = carrera.id;
            deposito.append(etiqueta, campo, idCampo);
            fila.append(nivel, deposito);
            carrerasContenedor.appendChild(fila);
        });

        if (carrerasPendientes === 0) {
            const estado = document.createElement('div');
            estado.className = 'text-muted';
            estado.textContent = 'El estudiante ya tiene inscripción en todas las carreras disponibles para esta gestión.';
            carrerasContenedor.appendChild(estado);
        }
        botonGuardar.disabled = carrerasPendientes === 0;
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
        estudianteInput.value = `${estudiante.nombre} - C.I.: ${estudiante.dni || 'Sin C.I.'}`;
        mostrarCarreras(estudiante);
    }

    estudianteInput.addEventListener('input', () => {
        clearTimeout(temporizadorBusqueda);
        solicitudBusqueda++;
        limpiarSeleccion('Busca y selecciona un estudiante para cargar su historial y las carreras disponibles.');
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
                    ci.textContent = `C.I.: ${estudiante.dni || 'Sin C.I.'}`;
                    opcion.append(nombre, ci);
                    opcion.addEventListener('click', () => seleccionarEstudiante(estudiante));
                    resultados.appendChild(opcion);
                });
            } catch (error) {
                resultados.textContent = 'No fue posible buscar estudiantes. Intenta nuevamente.';
            }
        }, 250);
    });

    document.getElementById('formEstudianteRegular').addEventListener('submit', function () {
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
