@extends('adminlte::page')

@section('title', 'Oferta de Materias')

@section('content_header')
    <h1>Oferta de Materias</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        <p class="mb-1"><strong>Estudiante:</strong> {{ trim($inscripcion->nombre.' '.$inscripcion->primerapellido.' '.$inscripcion->segundoapellido) }}</p>
        <p class="mb-3"><strong>Carrera:</strong> {{ $inscripcion->carrera }} · Gestión {{ $gestionActual->gestion }} / Periodo {{ $gestionActual->periodo }} · {{ \App\Models\Carrera::etiquetaNivel($inscripcion->regimen, $inscripcion->nivelinicio) }}</p>

        @include('partials.sweetalert-messages')

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
                <legend class="h6 font-weight-bold mb-2">Seleccione el turno obligatorio</legend>
                @foreach ($turnos as $valorTurno => $etiquetaTurno)
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="turno_{{ $valorTurno }}" name="turno" value="{{ $valorTurno }}" class="custom-control-input" required @checked((int) $turno === $valorTurno) @disabled($combinacionInconsistente || ($turnoBloqueado !== null && $turnoBloqueado !== $valorTurno))>
                        <label class="custom-control-label" for="turno_{{ $valorTurno }}">{{ $etiquetaTurno }}</label>
                    </div>
                @endforeach
                <button type="submit" class="btn btn-sm btn-primary ml-2" @disabled($combinacionInconsistente)>Ver materias</button>
            </fieldset>
        </form>

        <div class="btn-group mb-2" role="group" aria-label="Paralelo">
            @foreach (['A', 'B', 'C', 'D'] as $opcionParalelo)
                @if ($combinacionInconsistente || $paraleloBloqueado !== null)
                    <button type="button" class="btn {{ $paraleloBloqueado === $opcionParalelo ? 'btn-primary' : 'btn-default border' }}" disabled>{{ $opcionParalelo }}</button>
                @else
                    <a href="{{ route('inscripcion_manual.oferta', ['inscrito' => $inscripcion->id_inscrito, 'paralelo' => $opcionParalelo, 'turno' => $turno]) }}" class="btn {{ $paralelo === $opcionParalelo ? 'btn-primary' : 'btn-default border' }}">{{ $opcionParalelo }}</a>
                @endif
            @endforeach
        </div>
        @if ($combinacionInconsistente)
            <div class="alert alert-warning py-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Este estudiante ya tiene materias asignadas en distintos turnos o paralelos. Revise las materias asignadas antes de agregar otras.
            </div>
        @elseif ($seIntentoCambiarCombinacion)
            <div class="alert alert-warning py-2">
                <i class="fas fa-lock mr-1"></i>
                Este estudiante ya tiene materias asignadas en turno {{ $turnos[$turno] }} y paralelo {{ $paralelo }}. No se puede cambiar ni mezclar esa combinación.
            </div>
        @elseif ($turnoBloqueado !== null)
            <div class="alert alert-info py-2">
                <i class="fas fa-lock mr-1"></i>
                El estudiante ya tiene materias asignadas en turno {{ $turnos[$turno] }} y paralelo {{ $paralelo }}. Puede agregar materias una por una solo en esta combinación.
            </div>
        @elseif ($tieneMateriasAsignadas)
            <div class="alert alert-info py-2">
                <i class="fas fa-lock mr-1"></i>
                El estudiante ya tiene materias asignadas sin grupo. Elija turno y paralelo; agregue las materias una por una.
            </div>
        @endif
        <p class="text-muted">
            @if ($combinacionInconsistente)
                No se pueden ofrecer materias hasta revisar las asignaciones actuales.
            @elseif ($turno)
                Se muestran las materias del nivel de inscripción para el paralelo {{ $paralelo }} en turno {{ $turnos[$turno] }}.
            @else
                Elija un turno para consultar las materias del paralelo {{ $paralelo }}.
            @endif
        </p>

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
                                        $lleno = (int) $grupo->ocupados >= (int) $grupo->capacidad;
                                        $deshabilitado = ((!$grupo->abierto && !$seleccionActual) || ($lleno && !$seleccionActual));
                                    @endphp
                                    <tr>
                                        <td class="text-center align-middle"><input type="checkbox" name="grupos[{{ $grupo->id_materia }}]" value="{{ $grupo->id_grupo }}" aria-label="Elegir {{ $grupo->materia }} en paralelo {{ $grupo->paralelo }} turno {{ $turnos[$grupo->turno] }}" @checked($seleccionActual) @disabled($deshabilitado)></td>
                                        <td>{{ $grupo->nivel }}</td>
                                        <td>{{ $grupo->numero ?? '—' }}</td>
                                        <td>{{ $grupo->materia_codigo }}</td>
                                        <td>{{ $grupo->materia }}</td>
                                        <td>{{ $grupo->paralelo }} [{{ $turnos[$grupo->turno] }}]{{ $grupo->docente_nombre ? ', '.trim($grupo->docente_nombre.' '.$grupo->docente_apellido.' '.$grupo->docente_segundo_apellido) : '' }} · {{ max(0, (int) $grupo->capacidad - (int) $grupo->ocupados) }} cupos</td>
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
<script>
    document.addEventListener('DOMContentLoaded', () => {
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
