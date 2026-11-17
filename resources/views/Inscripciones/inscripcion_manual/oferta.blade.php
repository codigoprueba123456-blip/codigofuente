@extends('adminlte::page')

@section('title', 'Oferta de Materias')

@section('content_header')
    <h1>Oferta de Materias</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        @php
            $turnoResumen = $turnoBloqueado ?? $inscripcion->turno;
            $paraleloResumen = $paraleloBloqueado ?? $inscripcion->paralelo;
        @endphp
        <p class="mb-1"><strong>Estudiante:</strong> {{ trim($inscripcion->nombre.' '.$inscripcion->primerapellido.' '.$inscripcion->segundoapellido) }}</p>
        <p class="mb-3">
            <strong>Carrera:</strong> {{ $inscripcion->carrera }}
            · Gestión {{ $gestionActual->gestion }} / Periodo {{ $gestionActual->periodo }}
            · {{ \App\Models\Carrera::etiquetaNivel($inscripcion->regimen, $inscripcion->nivelinicio) }}
            · Turno {{ $turnoResumen ? ($turnos[$turnoResumen] ?? 'pendiente') : 'pendiente' }}
            · Paralelo {{ $paraleloResumen ?: 'pendiente' }}
        </p>

        @include('partials.sweetalert-messages')

        @php
            $turnosVisibles = $opcionesTurnoParalelo->pluck('turno')->unique()->values();
            if ($turnoBloqueado !== null && !$turnosVisibles->contains($turnoBloqueado)) {
                $turnosVisibles->push($turnoBloqueado);
            }
            $paralelosVisibles = $turno === null
                ? collect()
                : $opcionesTurnoParalelo->where('turno', $turno)->pluck('paralelo')->unique()->values();
            if ($turnoBloqueado !== null && !$combinacionInconsistente) {
                $paralelosVisibles = collect([$paraleloBloqueado]);
            }
        @endphp

        <div class="mb-3">
            <a href="{{ route('inscripcion_manual.materias', $inscripcion->id_inscrito) }}" class="btn btn-default border">
                <i class="fas fa-asterisk"></i> Todas las Materias
            </a>
            <a href="{{ route('inscripcion_manual.index', ['id_inscrito' => $inscripcion->id_inscrito]) }}" class="btn btn-default border ml-1">
                Volver a materias asignadas
            </a>
        </div>

        <form method="GET" action="{{ route('inscripcion_manual.oferta', $inscripcion->id_inscrito) }}" class="border rounded bg-light p-2 mb-3">
            <input type="hidden" name="paralelo" value="{{ $paralelo }}">
            <fieldset class="mb-0">
                <legend class="h6 font-weight-bold mb-2">Turno y paralelo de la inscripción</legend>
                @foreach ($turnos as $valorTurno => $etiquetaTurno)
                    @if ($turnosVisibles->contains($valorTurno))
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="turno_{{ $valorTurno }}" name="turno" value="{{ $valorTurno }}" class="custom-control-input" required @checked((int) $turno === $valorTurno) @disabled($combinacionInconsistente || $turnoBloqueado !== null)>
                            <label class="custom-control-label" for="turno_{{ $valorTurno }}">{{ $etiquetaTurno }}</label>
                        </div>
                    @endif
                @endforeach
                <button type="submit" class="btn btn-sm btn-primary ml-2" @disabled($combinacionInconsistente || $turnoBloqueado !== null || $opcionesTurnoParalelo->isEmpty())>Ver materias</button>
            </fieldset>
        </form>

        <div class="btn-group mb-2" role="group" aria-label="Paralelo">
            @foreach ($paralelosVisibles as $opcionParalelo)
                @if ($combinacionInconsistente || $paraleloBloqueado !== null)
                    <button type="button" class="btn btn-primary" disabled>{{ $opcionParalelo }}</button>
                @else
                    <a href="{{ route('inscripcion_manual.oferta', ['inscrito' => $inscripcion->id_inscrito, 'paralelo' => $opcionParalelo, 'turno' => $turno]) }}" class="btn {{ $paralelo === $opcionParalelo ? 'btn-primary' : 'btn-default border' }}">{{ $opcionParalelo }}</a>
                @endif
            @endforeach
        </div>
        @if ($combinacionInconsistente)
            <div class="alert alert-warning py-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                La inscripción tiene una combinación de turno/paralelo inconsistente con sus materias asignadas. Use el cambio de turno para corregirla antes de agregar materias.
            </div>
        @elseif ($seIntentoCambiarCombinacion)
            <div class="alert alert-warning py-2">
                <i class="fas fa-lock mr-1"></i>
                Esta inscripción está registrada en turno {{ $turnos[$turno] }} y paralelo {{ $paralelo }}. Para cambiarlo use la acción de cambio de turno/paralelo.
            </div>
        @elseif ($turnoBloqueado !== null)
            <div class="alert alert-info py-2">
                <i class="fas fa-lock mr-1"></i>
                La combinación registrada es {{ $turnos[$turno] }} · paralelo {{ $paralelo }}. Las materias disponibles se cargan para esa combinación.
                @if ($tieneMateriasAsignadas)
                    Agregue o reasigne materias una por una.
                @endif
            </div>
        @elseif ($tieneMateriasAsignadas)
            <div class="alert alert-info py-2">
                <i class="fas fa-lock mr-1"></i>
                El estudiante ya tiene materias asignadas sin grupo. Elija turno y paralelo; agregue las materias una por una.
            </div>
        @elseif ($opcionesTurnoParalelo->isEmpty())
            <div class="alert alert-warning py-2">
                No hay grupos abiertos con cupos para el nivel, carrera y gestión de esta inscripción.
            </div>
        @endif
        <p class="text-muted">
            @if ($combinacionInconsistente)
                No se pueden ofrecer materias hasta corregir la combinación actual.
            @elseif ($turno)
                Se muestran las materias del nivel de inscripción para el paralelo {{ $paralelo }} en turno {{ $turnos[$turno] }}.
            @else
                Elija un turno para consultar las materias del paralelo {{ $paralelo }}.
            @endif
        </p>

        @if ($puedeCambiarTurno)
            <div class="border rounded p-3 mb-3">
                <button
                    type="button"
                    class="btn btn-outline-warning"
                    id="iniciarCambioTurno"
                    data-confirmation-text="{{ $tieneMateriasAsignadas
                        ? 'Las materias de esta inscripción se conservarán, pero sus grupos de la gestión vigente quedarán sin asignar para que pueda elegir nuevos grupos. Las gestiones anteriores no se modificarán.'
                        : 'Se actualizará el turno y paralelo de esta inscripción. Las gestiones anteriores no se modificarán.' }}"
                >
                    <i class="fas fa-exchange-alt mr-1"></i> Cambiar turno/paralelo
                </button>
                <div id="errorCambioTurno" class="alert alert-danger mt-2 mb-0" role="alert" hidden>
                    No se pudo abrir la confirmación. Recarga la página e inténtalo nuevamente.
                </div>
                <div id="selectorCambioTurno" class="mt-3" hidden>
                    <form method="POST" action="{{ route('inscripcion_manual.cambiar-turno', $inscripcion->id_inscrito) }}" id="formCambioTurno">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="turno" id="turnoCambioTurno">
                        <input type="hidden" name="paralelo" id="paraleloCambioTurno">
                        <div class="form-group">
                            <label for="combinacionCambioTurno">Nuevo turno y paralelo</label>
                            <select id="combinacionCambioTurno" class="form-control" required>
                                @foreach ($opcionesCambioTurno as $opcion)
                                    <option value="{{ $opcion['turno'] }}|{{ $opcion['paralelo'] }}">
                                        {{ $turnos[$opcion['turno']] }} · Paralelo {{ $opcion['paralelo'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-check mr-1"></i> Confirmar y cambiar
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <form id="formOfertaMaterias" method="POST" action="{{ route('inscripcion_manual.guardar', $inscripcion->id_inscrito) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="paralelo" value="{{ $paralelo }}">
            @if ($turno)
                <input type="hidden" name="turno" value="{{ $turno }}">
            @endif

            @if ($materiasOferta->isNotEmpty())
                <div class="d-flex flex-wrap align-items-center border rounded bg-light p-2 mb-3">
                    <div class="custom-control custom-checkbox mr-3">
                        <input type="checkbox" id="seleccionarTodo" class="custom-control-input" aria-label="Seleccionar todas las materias disponibles" @disabled($tieneMateriasAsignadas)>
                        <label for="seleccionarTodo" class="custom-control-label small font-weight-bold">
                            @if ($tieneMateriasAsignadas)
                                <i class="fas fa-lock mr-1"></i>
                            @endif
                            Seleccionar todo
                        </label>
                    </div>
                    <div>
                        <h6 class="mb-0">Materias disponibles</h6>
                        <small class="text-muted">
                            @if ($tieneMateriasAsignadas)
                                Selección bloqueada porque el estudiante ya tiene materias asignadas; agregue materias una por una.
                            @else
                                Solo se seleccionarán materias del paralelo {{ $paralelo }} en turno {{ $turnos[$turno] }}.
                            @endif
                        </small>
                    </div>
                    <span id="contadorMateriasSeleccionadas" class="small text-muted ml-auto pl-2" aria-live="polite">Selecciona las materias disponibles.</span>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center align-middle">Elegir</th>
                            <th>Nivel</th>
                            <th>N.º</th>
                            <th>Código</th>
                            <th>Materia</th>
                            <th>Grupo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($combinacionInconsistente)
                            <tr><td colspan="6" class="text-center text-muted">Revise las materias ya asignadas antes de continuar.</td></tr>
                        @elseif (!$turno)
                            <tr><td colspan="6" class="text-center text-muted">Seleccione un turno antes de ver o asignar materias.</td></tr>
                        @else
                            @forelse ($materiasOferta as $grupos)
                                @foreach ($grupos as $grupo)
                                    @php
                                        $seleccionActual = (int) ($seleccionadosPorMateria[$grupo->id_materia] ?? 0) === (int) $grupo->id_grupo;
                                        $lleno = $grupo->capacidad !== null && (int) $grupo->ocupados >= (int) $grupo->capacidad;
                                        $deshabilitado = ((!$grupo->abierto && !$seleccionActual) || ($lleno && !$seleccionActual));
                                    @endphp
                                    <tr>
                                        <td class="text-center align-middle"><input type="checkbox" name="grupos[{{ $grupo->id_materia }}]" value="{{ $grupo->id_grupo }}" aria-label="Elegir {{ $grupo->materia }} en paralelo {{ $grupo->paralelo }} turno {{ $turnos[$grupo->turno] }}" @checked($seleccionActual) @disabled($deshabilitado)></td>
                                        <td>{{ $grupo->nivel }}</td>
                                        <td>{{ $grupo->numero ?? '—' }}</td>
                                        <td>{{ $grupo->materia_codigo }}</td>
                                        <td>{{ $grupo->materia }}</td>
                                        <td>
                                            {{ $grupo->paralelo }} [{{ $turnos[$grupo->turno] }}]{{ $grupo->docente_nombre ? ', '.trim($grupo->docente_nombre.' '.$grupo->docente_apellido.' '.$grupo->docente_segundo_apellido) : '' }}
                                            ·
                                            @if ($grupo->capacidad !== null)
                                                {{ max(0, (int) $grupo->capacidad - (int) $grupo->ocupados) }} cupos
                                            @else
                                                Sin límite de cupos
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No hay materias ofertadas en el paralelo {{ $paralelo }} para el turno {{ $turnos[$turno] }} de esta carrera y gestión.</td></tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap align-items-center mt-3">
                @if ($materiasOferta->isNotEmpty())
                    <button type="submit" class="btn btn-primary mr-2 mb-2"><i class="fas fa-check mr-1"></i> Asignar materias</button>
                @endif
                <a href="{{ route('inscripcion_manual.index') }}" class="btn btn-default border mb-2">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@stop

@push('js')
@include('partials.sweetalert-assets')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const iniciarCambioTurno = document.getElementById('iniciarCambioTurno');
        const selectorCambioTurno = document.getElementById('selectorCambioTurno');
        const errorCambioTurno = document.getElementById('errorCambioTurno');
        const formularioCambioTurno = document.getElementById('formCambioTurno');
        const combinacionCambioTurno = document.getElementById('combinacionCambioTurno');
        const turnoCambioTurno = document.getElementById('turnoCambioTurno');
        const paraleloCambioTurno = document.getElementById('paraleloCambioTurno');

        if (iniciarCambioTurno) {
            iniciarCambioTurno.addEventListener('click', async () => {
                if (!window.Swal?.fire) {
                    errorCambioTurno.hidden = false;
                    return;
                }

                const confirmacion = await window.Swal.fire({
                    icon: 'warning',
                    title: '¿Cambiar turno y paralelo?',
                    text: iniciarCambioTurno.dataset.confirmationText,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    customClass: {
                        popup: 'itsa-swal-modal',
                    },
                });

                if (confirmacion.isConfirmed) {
                    iniciarCambioTurno.hidden = true;
                    selectorCambioTurno.hidden = false;
                    combinacionCambioTurno.focus();
                }
            });

            formularioCambioTurno.addEventListener('submit', (evento) => {
                const [turno, paralelo] = combinacionCambioTurno.value.split('|');
                if (!turno || !paralelo) {
                    evento.preventDefault();
                    combinacionCambioTurno.focus();
                    return;
                }
                turnoCambioTurno.value = turno;
                paraleloCambioTurno.value = paralelo;
            });
        }

        const seleccionarTodo = document.getElementById('seleccionarTodo');
        const contadorSeleccion = document.getElementById('contadorMateriasSeleccionadas');
        const casillasMateria = Array.from(document.querySelectorAll(
            '#formOfertaMaterias input[type="checkbox"][name^="grupos["]'
        ));

        if (!seleccionarTodo || !contadorSeleccion || casillasMateria.length === 0) {
            return;
        }

        const actualizarSeleccionTotal = () => {
            const disponibles = casillasMateria.filter((casilla) => !casilla.disabled);
            const seleccionadas = disponibles.filter((casilla) => casilla.checked).length;

            contadorSeleccion.textContent = `${seleccionadas} de ${disponibles.length} materias disponibles seleccionadas`;
            if (!seleccionarTodo.disabled) {
                seleccionarTodo.checked = disponibles.length > 0 && seleccionadas === disponibles.length;
                seleccionarTodo.indeterminate = seleccionadas > 0 && seleccionadas < disponibles.length;
            }
        };

        seleccionarTodo.addEventListener('change', () => {
            casillasMateria.forEach((casilla) => {
                if (!casilla.disabled) {
                    casilla.checked = seleccionarTodo.checked;
                }
            });
            actualizarSeleccionTotal();
        });

        casillasMateria.forEach((casilla) => {
            casilla.addEventListener('change', actualizarSeleccionTotal);
        });

        actualizarSeleccionTotal();
    });
</script>
@endpush
