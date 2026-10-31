@extends('adminlte::page')

@section('title', 'Materias del Estudiante')

@section('content_header')
    <h1>Materias del Estudiante</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        <p class="mb-1"><strong>Estudiante:</strong> {{ trim($inscripcion->nombre.' '.$inscripcion->primerapellido.' '.$inscripcion->segundoapellido) }} · CI {{ $inscripcion->ci }}</p>
        <p class="mb-3"><strong>Carrera:</strong> {{ $inscripcion->carrera }} · Gestión {{ $gestionActual->gestion }} / Periodo {{ $gestionActual->periodo }}</p>

        <div class="mb-3">
            <a href="{{ route('inscripcion_manual.oferta', ['inscrito' => $inscripcion->id_inscrito]) }}" class="btn btn-default border">
                <i class="far fa-list-alt"></i> Oferta de Materias
            </a>
            <a href="{{ route('inscripcion_manual.index', ['id_inscrito' => $inscripcion->id_inscrito]) }}" class="btn btn-default border ml-1">
                Volver a materias asignadas
            </a>
        </div>

        <div class="table-responsive">
            @forelse ($materiasPorNivel as $nivel => $materias)
                <h4>{{ $nivel }}° Año</h4>
                <table class="table table-bordered table-striped mb-4">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Código</th>
                            <th>Materia</th>
                            <th>Grupo</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($materias as $materia)
                        <tr>
                            <td>{{ $materia->numero ?? '—' }}</td>
                            <td>{{ $materia->codigo }}</td>
                            <td>{{ $materia->nombre }}</td>
                            <td>
                                @forelse ($materia->grupos as $grupo)
                                    <div>{{ $grupo->paralelo }} [{{ $grupo->turno ?: 'turno pendiente' }}{{ $grupo->docente_nombre ? ', '.trim($grupo->docente_nombre.' '.$grupo->docente_apellido.' '.$grupo->docente_segundo_apellido) : ', docente pendiente' }}]</div>
                                @empty
                                    <span class="text-muted">Sin grupo ofertado en esta gestión</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @empty
                <p class="text-center text-muted">No hay materias registradas para esta carrera.</p>
            @endforelse
        </div>
    </div>
</div>
@stop
