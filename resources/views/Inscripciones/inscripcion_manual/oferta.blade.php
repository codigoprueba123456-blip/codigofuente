@extends('adminlte::page')

@section('title', 'Oferta de Materias')

@section('content_header')
    <h1>Oferta de Materias</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        <p class="mb-1"><strong>Estudiante:</strong> {{ trim($inscripcion->nombre.' '.$inscripcion->primerapellido.' '.$inscripcion->segundoapellido) }}</p>
        <p class="mb-3"><strong>Carrera:</strong> {{ $inscripcion->carrera }} · Gestión {{ $gestionActual->gestion }} / Periodo {{ $gestionActual->periodo }} · Nivel {{ $inscripcion->nivelinicio }}°</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="mb-3">
            <a href="{{ route('inscripcion_manual.materias', $inscripcion->id_inscrito) }}" class="btn btn-default border">
                <i class="fas fa-asterisk"></i> Todas las Materias
            </a>
            <a href="{{ route('inscripcion_manual.index', ['id_inscrito' => $inscripcion->id_inscrito]) }}" class="btn btn-default border ml-1">
                Volver a materias asignadas
            </a>
        </div>

        <div class="btn-group mb-3" role="group" aria-label="Paralelo">
            @foreach (['A', 'B', 'C', 'D'] as $opcionParalelo)
                <a href="{{ route('inscripcion_manual.oferta', ['inscrito' => $inscripcion->id_inscrito, 'paralelo' => $opcionParalelo]) }}" class="btn {{ $paralelo === $opcionParalelo ? 'btn-primary' : 'btn-default border' }}">{{ $opcionParalelo }}</a>
            @endforeach
        </div>
        <p class="text-muted">Se muestran las materias del nivel de inscripción para la gestión actual y el paralelo seleccionado.</p>

        <form id="formOfertaMaterias" method="POST" action="{{ route('inscripcion_manual.guardar', $inscripcion->id_inscrito) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="paralelo" value="{{ $paralelo }}">

            @if ($materiasOferta->isNotEmpty())
                <div class="d-flex flex-wrap justify-content-between align-items-center border rounded bg-light p-3 mb-3">
                    <div class="mb-2 mb-md-0">
                        <h5 class="mb-1">Seleccionar materias</h5>
                        <small class="text-muted">Marca las materias que deseas asignar al estudiante en el paralelo {{ $paralelo }}.</small>
                    </div>
                    <div class="d-flex flex-wrap align-items-center">
                        <span id="contadorMateriasSeleccionadas" class="text-muted mr-3" aria-live="polite">Selecciona las materias disponibles.</span>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" id="seleccionarTodo" class="custom-control-input" aria-label="Seleccionar todas las materias disponibles">
                            <label for="seleccionarTodo" class="custom-control-label font-weight-bold">Seleccionar todo</label>
                        </div>
                    </div>
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
                        @forelse ($materiasOferta as $grupos)
                            @foreach ($grupos as $grupo)
                                @php
                                    $seleccionActual = (int) ($seleccionadosPorMateria[$grupo->id_materia] ?? 0) === (int) $grupo->id_grupo;
                                    $lleno = (int) $grupo->ocupados >= (int) $grupo->capacidad;
                                    $deshabilitado = ((!$grupo->abierto && !$seleccionActual) || ($lleno && !$seleccionActual));
                                @endphp
                                <tr>
                                    <td class="text-center align-middle"><input type="checkbox" name="grupos[{{ $grupo->id_materia }}]" value="{{ $grupo->id_grupo }}" aria-label="Elegir {{ $grupo->materia }} en paralelo {{ $grupo->paralelo }}" @checked($seleccionActual) @disabled($deshabilitado)></td>
                                    <td>{{ $grupo->nivel }}</td>
                                    <td>{{ $grupo->numero ?? '—' }}</td>
                                    <td>{{ $grupo->materia_codigo }}</td>
                                    <td>{{ $grupo->materia }}</td>
                                    <td>{{ $grupo->paralelo }}{{ $grupo->turno ? ' ['.$grupo->turno : '' }}{{ $grupo->docente_nombre ? ', '.trim($grupo->docente_nombre.' '.$grupo->docente_apellido.' '.$grupo->docente_segundo_apellido) : '' }}{{ $grupo->turno ? ']' : '' }} · {{ max(0, (int) $grupo->capacidad - (int) $grupo->ocupados) }} cupos</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No hay materias ofertadas en el paralelo {{ $paralelo }} para esta carrera y gestión.</td></tr>
                        @endforelse
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
            seleccionarTodo.checked = disponibles.length > 0 && seleccionadas === disponibles.length;
            seleccionarTodo.indeterminate = seleccionadas > 0 && seleccionadas < disponibles.length;
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
