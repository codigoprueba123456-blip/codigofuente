@extends('adminlte::page')

@section('title', 'Estado de Inscripción')

@section('content_header')
    <h1>Estado de Inscripción <small class="text-muted">el estado de inscripción del estudiante</small></h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        @if (!$gestionActual)
            <div class="alert alert-warning">No hay una gestión académica configurada.</div>
        @else
            <p class="text-muted mb-3">Gestión {{ $gestionActual->gestion }} · Periodo {{ $gestionActual->periodo }}</p>
            <form method="GET" action="{{ route('inscripcion_manual.index') }}" id="busquedaEstudiante" class="row align-items-end mb-3">
                <div class="col-md-6 form-group position-relative">
                    <label for="q">Estudiante: <span class="text-danger">*</span></label>
                    <input type="search" name="q" id="q" class="form-control" value="{{ $termino }}" autocomplete="off" placeholder="Nombre, apellido o CI">
                    <div id="sugerenciasEstudiante" class="list-group position-absolute w-100 shadow-sm" style="z-index: 20; left: 0; top: 100%;"></div>
                </div>
                <input type="hidden" name="id_inscrito" id="id_inscrito" value="{{ $inscripcionSeleccionada->id_inscrito ?? '' }}">
                <div class="col-md-6 form-group" id="selectorCarreraContenedor" @unless ($inscripcionSeleccionada) hidden @endunless>
                    <label for="selectorCarrera">Carrera: <span class="text-danger">*</span></label>
                    <select id="selectorCarrera" class="form-control" @disabled(!$inscripcionSeleccionada)>
                        @unless ($inscripcionSeleccionada)
                            <option value="">Seleccione un estudiante primero</option>
                        @endunless
                        @foreach ($inscripcionesPersona as $opcion)
                            <option value="{{ $opcion->id_inscrito }}" @selected((int) $opcion->id_inscrito === (int) ($inscripcionSeleccionada->id_inscrito ?? 0))>{{ $opcion->carrera }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div id="mensajeBusqueda" class="small text-muted mb-3"></div>

            @if ($inscripcionSeleccionada)
                <p class="text-muted mb-2">{{ trim($inscripcionSeleccionada->nombre.' '.$inscripcionSeleccionada->primerapellido.' '.$inscripcionSeleccionada->segundoapellido) }} · CI {{ $inscripcionSeleccionada->dni }} · {{ $inscripcionSeleccionada->nivelinicio }}° año</p>
                <div class="alert alert-info py-2">
                    Carrera seleccionada: <strong>{{ $inscripcionSeleccionada->carrera }}</strong>
                    · Gestión {{ $gestionActual->gestion }} / Periodo {{ $gestionActual->periodo }}
                </div>
            @endif
            <div id="resumenEstudiante" class="alert alert-info py-2" hidden aria-live="polite"></div>

            <div class="btn-toolbar mb-3" role="toolbar" aria-label="Acciones de materias">
                @if ($inscripcionSeleccionada)
                    <a href="{{ route('inscripcion_manual.oferta', ['inscrito' => $inscripcionSeleccionada->id_inscrito]) }}" class="btn btn-default bg-light border">
                        <i class="far fa-list-alt"></i> Oferta de Materias
                    </a>
                    <a href="{{ route('inscripcion_manual.materias', ['inscrito' => $inscripcionSeleccionada->id_inscrito]) }}" class="btn btn-default bg-light border ml-2">
                        <i class="fas fa-asterisk"></i> Todas las Materias
                    </a>
                @else
                    <button type="button" class="btn btn-default bg-light border" disabled><i class="far fa-list-alt"></i> Oferta de Materias</button>
                    <button type="button" class="btn btn-default bg-light border ml-2" disabled><i class="fas fa-asterisk"></i> Todas las Materias</button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nivel</th>
                            <th>N.º</th>
                            <th>Código</th>
                            <th>Materia</th>
                            <th>Grupo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!$inscripcionSeleccionada)
                            <tr><td colspan="6" class="text-center text-muted">Ningún registro encontrado.</td></tr>
                        @elseif ($materiasActuales->isEmpty())
                            <tr><td colspan="6" class="text-center text-muted">El estudiante aún no tiene materias asignadas en esta gestión.</td></tr>
                        @else
                            @foreach ($materiasActuales as $materia)
                                <tr>
                                    <td>{{ $materia->nivel }}</td>
                                    <td>{{ $materia->numero ?? '—' }}</td>
                                    <td>{{ $materia->codigo }}</td>
                                    <td>{{ $materia->nombre }}</td>
                                    <td>
                                        @if ($materia->paralelo)
                                            {{ $materia->paralelo }} [{{ $materia->turno ?: 'turno pendiente' }}{{ $materia->docente_nombre ? ', '.implode(' ', array_filter([$materia->docente_apellido, $materia->docente_segundo_apellido, $materia->docente_nombre])) : ', docente pendiente' }}]
                                        @else
                                            Pendiente de paralelo
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('inscripcion_manual.retirar', ['inscrito' => $inscripcionSeleccionada->id_inscrito, 'materia' => $materia->id_materia]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="q" value="{{ $termino }}">
                                            <button type="submit" class="btn btn-sm btn-default border"><i class="fas fa-times"></i> Retirar materia</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@stop

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formulario = document.getElementById('busquedaEstudiante');
        const entrada = document.getElementById('q');
        const sugerencias = document.getElementById('sugerenciasEstudiante');
        const selector = document.getElementById('selectorCarrera');
        const selectorContenedor = document.getElementById('selectorCarreraContenedor');
        const idInscrito = document.getElementById('id_inscrito');
        const mensaje = document.getElementById('mensajeBusqueda');
        const resumen = document.getElementById('resumenEstudiante');
        let espera;

        const mostrarCarreras = (estudiante) => {
            selector.replaceChildren();
            const opcionInicial = document.createElement('option');
            opcionInicial.value = '';
            opcionInicial.textContent = 'Seleccione la carrera para continuar';
            opcionInicial.disabled = true;
            opcionInicial.selected = true;
            selector.append(opcionInicial);
            estudiante.inscripciones.forEach((inscripcion) => {
                const opcion = document.createElement('option');
                opcion.value = inscripcion.id_inscrito;
                opcion.textContent = `${inscripcion.carrera} · ${inscripcion.nivel}° año`;
                selector.append(opcion);
            });
            entrada.value = `${estudiante.nombre} · CI ${estudiante.dni || 'sin CI'}`;
            sugerencias.replaceChildren();
            selector.disabled = false;
            selectorContenedor.hidden = false;
            idInscrito.value = '';
            resumen.hidden = false;
            resumen.textContent = `Estudiante seleccionado: ${estudiante.nombre} · CI ${estudiante.dni || 'sin CI'}. Seleccione la carrera que desea gestionar.`;
            if (mensaje) mensaje.textContent = 'Seleccione una carrera para cargar sus materias.';
        };

        entrada.addEventListener('input', () => {
            window.clearTimeout(espera);
            idInscrito.value = '';
            selector.disabled = true;
            sugerencias.replaceChildren();
            resumen.hidden = true;
            resumen.textContent = '';
            if (mensaje) mensaje.textContent = '';
            const termino = entrada.value.trim();
            if (termino.length < 2) return;

            espera = window.setTimeout(async () => {
                const respuesta = await fetch(@json(route('inscripcion_manual.buscar')) + '?q=' + encodeURIComponent(termino), {
                    headers: { 'Accept': 'application/json' },
                });
                if (!respuesta.ok) return;
                const estudiantes = await respuesta.json();
                estudiantes.forEach((estudiante) => {
                    const opcion = document.createElement('button');
                    opcion.type = 'button';
                    opcion.className = 'list-group-item list-group-item-action';
                    opcion.textContent = `${estudiante.nombre} · CI ${estudiante.dni || 'sin CI'}`;
                    opcion.addEventListener('click', () => mostrarCarreras(estudiante));
                    sugerencias.append(opcion);
                });
                if (mensaje) mensaje.textContent = estudiantes.length ? '' : 'No se encontraron estudiantes inscritos en la gestión vigente.';
            }, 250);
        });

        selector.addEventListener('change', () => {
            idInscrito.value = selector.value;
            formulario.submit();
        });
        formulario.addEventListener('submit', (evento) => {
            if (!idInscrito.value) evento.preventDefault();
        });
        document.addEventListener('click', (evento) => {
            if (!formulario.contains(evento.target)) sugerencias.replaceChildren();
        });
    });
</script>
@endpush