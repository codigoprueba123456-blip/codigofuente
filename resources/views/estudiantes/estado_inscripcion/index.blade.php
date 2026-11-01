@extends('adminlte::page')

@section('title', 'Estado de Inscripción')

@section('content_header')
    <h1>Estado de Inscripción</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        @if (!$persona || !$persona->estudiante)
            <div class="alert alert-warning mb-0">
                Esta cuenta todavía no está vinculada a una ficha de estudiante.
            </div>
        @elseif ($inscripciones->isEmpty())
            <div class="alert alert-info mb-0">
                Aún no tienes inscripciones registradas.
            </div>
        @else
            <div class="mb-4">
                <h4>Historial de carreras</h4>
                <p class="text-muted">Aquí aparecen las carreras y niveles registrados en cada gestión. El historial no indica por sí solo si una carrera fue concluida o interrumpida.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Carrera</th>
                                <th>Gestión</th>
                                <th>Nivel registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inscripciones as $registro)
                                <tr>
                                    <td>{{ $registro->carrera }}</td>
                                    <td>{{ $registro->gestion }} - Periodo {{ $registro->periodo }}</td>
                                    <td>{{ $registro->nivelinicio ? $registro->nivelinicio.'°' : 'No registrado' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <form method="GET" action="{{ route('estudiantes.estado_inscripcion') }}" class="row align-items-end">
                <div class="col-md-6 form-group">
                    <label for="id_carrera">Carrera:</label>
                    <select name="id_carrera" id="id_carrera" class="form-control" onchange="this.form.submit()">
                        @foreach ($carreras as $carrera)
                            @php
                                $resoluciones = $carrera->resolucionesMinisteriales();
                            @endphp
                            <option value="{{ $carrera->id_carrera }}" @selected((int) request('id_carrera', $carreras->first()->id_carrera) === (int) $carrera->id_carrera)>
                                {{ $carrera->nombre . ($resoluciones ? ' · R.M. ' . implode(' / ', $resoluciones) : '') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="id_gestionacademica">Gestión académica:</label>
                    <select name="id_gestionacademica" id="id_gestionacademica" class="form-control" onchange="this.form.submit()">
                        @foreach ($gestiones as $gestion)
                            <option value="{{ $gestion->id_gestionacademica }}" @selected((int) request('id_gestionacademica', $gestiones->first()->id_gestionacademica) === (int) $gestion->id_gestionacademica)>
                                {{ $gestion->gestion }} - Periodo {{ $gestion->periodo }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($inscripcionSeleccionada)
                <p class="text-muted">
                    @php
                        $niveles = [1 => 'PRIMER AÑO', 2 => 'SEGUNDO AÑO', 3 => 'TERCER AÑO', 4 => 'CUARTO AÑO', 5 => 'QUINTO AÑO'];
                        $nombreNivel = $niveles[(int) $nivel] ?? $nivel . '° AÑO';
                    @endphp
                    {{ $inscripciones->firstWhere('id_carrera', $inscripcionSeleccionada->id_carrera)->carrera }}
                    · Gestión {{ $inscripcionSeleccionada->gestion }} · {{ $nombreNivel }}
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nivel</th>
                                <th>N.º</th>
                                <th>Código</th>
                                <th>Materia</th>
                                <th>Grupo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materias as $materia)
                                <tr>
                                    <td>{{ $nombreNivel }}</td>
                                    <td>{{ $materia->numero ?? '—' }}</td>
                                    <td>{{ $materia->codigo }}</td>
                                    <td>{{ $materia->nombre }}</td>
                                    <td>
                                        @if ($materia->paralelo)
                                            {{ $materia->paralelo }}
                                            [{{ $materia->turno ?: 'turno pendiente' }}@if ($materia->docente_nombre), {{ implode(' ', array_filter([$materia->docente_primerapellido, $materia->docente_segundoapellido, $materia->docente_nombre])) }}@endif]
                                        @else
                                            Paralelo pendiente
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No tienes materias asignadas en esta gestión.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>
@stop
