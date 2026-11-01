@extends('adminlte::page')

@section('title', 'Reporte Malla Curricular')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-8">
            <h1>Reporte Malla Curricular <small class="text-muted">generar malla curricular</small></h1>
        </div>
    </div>
@stop

@section('content')
<div class="card mt-3">
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.academicos.malla-curricular') }}">
            <input type="hidden" name="generar" value="1">
            <div class="row">
                <div class="col-md-5 form-group">
                    <label for="id_carrera">Carrera: <span class="text-danger">*</span></label>
                    <select id="id_carrera" name="id_carrera" required
                            class="form-control @error('id_carrera') is-invalid @enderror">
                        <option value="">Seleccione una carrera</option>
                        @foreach ($carreras as $c)
                            @php
                                $resoluciones = $c->resolucionesMinisteriales();
                            @endphp
                            <option value="{{ $c->id_carrera }}" @selected((string) request('id_carrera') === (string) $c->id_carrera)>
                                {{ $c->nombre . ($resoluciones ? ' · R.M. ' . implode(' / ', $resoluciones) : '') }}
                                @unless ($c->activo) - inactiva @endunless
                            </option>
                        @endforeach
                    </select>
                    @error('id_carrera')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-cog mr-1"></i> Generar
            </button>
        </form>
    </div>
</div>

@if ($generado && $carrera)
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title mb-2 mb-md-0">
                    {{ $carrera->nombre }} <small class="text-muted">{{ $carrera->codigo }} · Versión {{ $carrera->version ?? '-' }}</small>
                </h3>
                <div>
                    <a class="btn btn-default mr-1"
                       href="{{ route('reportes.academicos.malla-curricular', array_merge(request()->only('id_carrera'), ['formato' => 'excel'])) }}">
                        <i class="fas fa-file-excel text-success mr-1"></i> Excel
                    </a>
                    <a class="btn btn-default"
                       href="{{ route('reportes.academicos.malla-curricular', array_merge(request()->only('id_carrera'), ['formato' => 'pdf'])) }}">
                        <i class="fas fa-file-pdf text-danger mr-1"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:70px" class="text-center">N°</th>
                            <th style="width:130px">Código</th>
                            <th>Materia</th>
                            <th style="width:80px" class="text-center">Horas</th>
                            <th style="width:90px" class="text-center">Créditos</th>
                            <th style="width:200px">Prerrequisitos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($niveles as $nivel => $materias)
                            <tr class="table-success">
                                <td colspan="6"><strong>{{ $nivel ? 'Nivel '.$nivel : 'Sin nivel asignado' }}</strong>
                                    <span class="text-muted">· {{ $materias->count() }} materias</span></td>
                            </tr>
                            @foreach ($materias as $materia)
                                <tr>
                                    <td class="text-center">{{ $materia->numero ?? '—' }}</td>
                                    <td>{{ $materia->codigo ?? '—' }}</td>
                                    <td>{{ $materia->nombre }}
                                        @unless ($materia->curricular) <span class="badge badge-secondary">No curricular</span> @endunless
                                    </td>
                                    <td class="text-center">{{ $materia->horas ?? '—' }}</td>
                                    <td class="text-center">{{ $materia->creditajemateria ?? '—' }}</td>
                                    <td>{{ $materia->prerequisitos->pluck('codigo')->filter()->implode(', ') ?: '—' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">La carrera seleccionada aún no tiene materias registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($totales['materias'] > 0)
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="3" class="text-right">Total ({{ $totales['materias'] }} materias)</td>
                                <td class="text-center">{{ $totales['horas'] }}</td>
                                <td class="text-center">{{ $totales['creditos'] }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endif
@stop
