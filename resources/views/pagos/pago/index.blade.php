@extends('adminlte::page')

@section('title', 'Pagos')

@section('content_header')
    <h1>Pagos</h1>
@stop

@section('content')
<div class="card card-default">
    <div class="card-body">
        <form method="GET" action="{{ route('pagos.index') }}">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="desde">Desde:</label>
                    <input type="date" name="desde" id="desde" value="{{ request('desde') }}" class="form-control">
                </div>

                <div class="col-md-4 form-group">
                    <label for="hasta">Hasta:</label>
                    <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}" class="form-control">
                </div>

                <div class="col-md-4 form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" name="usuario" id="usuario" value="{{ request('usuario') }}" class="form-control">
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <div class="col-md-11">
                    <button type="submit" class="btn btn-default bg-light border">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div class="col-md-1 text-right">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default bg-light border dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Exportar pagos">
                            <i class="fas fa-cog"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ route('pagos.index', array_merge(request()->except(['page', 'formato']), ['formato' => 'excel'])) }}">
                                <i class="fas fa-file-excel text-success mr-2"></i> Exportar Excel
                            </a>
                            <a class="dropdown-item" href="{{ route('pagos.index', array_merge(request()->except(['page', 'formato']), ['formato' => 'pdf'])) }}" target="_blank" rel="noopener">
                                <i class="fas fa-file-pdf text-danger mr-2"></i> Exportar PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <form method="GET" action="{{ route('pagos.index') }}" class="input-group mb-3">
            @foreach (request()->only(['desde', 'hasta', 'usuario', 'por_pagina']) as $nombre => $valor)
                @if (is_scalar($valor) && $valor !== '')
                    <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
                @endif
            @endforeach
            <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="Buscar por nombre, CI, código o voucher...">
            <div class="input-group-append">
                <button class="btn btn-default bg-light border" type="submit" aria-label="Buscar">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        @php
            $desdeRegistro = $pagos->firstItem() ?? 0;
            $hastaRegistro = $pagos->lastItem() ?? 0;
            $parametrosPaginacion = request()->except('page');
        @endphp

        @foreach (['superior', 'inferior'] as $posicion)
            <div class="d-flex justify-content-between align-items-center bg-light p-2 border {{ $posicion === 'superior' ? 'mb-0' : 'mt-0' }}">
                <span class="text-muted small">{{ $desdeRegistro }}-{{ $hastaRegistro }} de {{ $pagos->total() }} registros</span>
                <div class="d-flex align-items-center">
                    <div class="btn-group btn-group-sm mr-2" role="group">
                        <a href="{{ $pagos->url(1) }}" class="btn btn-default bg-white border {{ $pagos->onFirstPage() ? 'disabled' : '' }}" aria-label="Primera página"><i class="fas fa-angle-double-left"></i></a>
                        <a href="{{ $pagos->previousPageUrl() ?? '#' }}" class="btn btn-default bg-white border {{ $pagos->onFirstPage() ? 'disabled' : '' }}" aria-label="Página anterior"><i class="fas fa-angle-left"></i></a>
                        <a href="{{ $pagos->nextPageUrl() ?? '#' }}" class="btn btn-default bg-white border {{ $pagos->hasMorePages() ? '' : 'disabled' }}" aria-label="Página siguiente"><i class="fas fa-angle-right"></i></a>
                        <a href="{{ $pagos->url($pagos->lastPage()) }}" class="btn btn-default bg-white border {{ $pagos->hasMorePages() ? '' : 'disabled' }}" aria-label="Última página"><i class="fas fa-angle-double-right"></i></a>
                    </div>
                    <form method="GET" action="{{ route('pagos.index') }}">
                        @foreach ($parametrosPaginacion as $nombre => $valor)
                            @if (is_scalar($valor) && $valor !== '' && $nombre !== 'por_pagina')
                                <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
                            @endif
                        @endforeach
                        <select name="por_pagina" class="form-control form-control-sm" style="width: 70px;" onchange="this.form.submit()">
                            @foreach ([10, 25, 50] as $limite)
                                <option value="{{ $limite }}" {{ $pagos->perPage() === $limite ? 'selected' : '' }}>{{ $limite }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            @if ($posicion === 'superior')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped m-0">
                        <thead>
                            <tr>
                                <th style="width: 8%;">Nº</th>
                                <th style="width: 12%;">Codigo</th>
                                <th style="width: 12%;">Depósito</th>
                                <th style="width: 12%;">Fecha</th>
                                <th style="width: 26%;">Estudiante/Postulante</th>
                                <th style="width: 12%;">Usuario</th>
                                <th style="width: 10%;">Monto</th>
                                <th style="width: 8%;">Valido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pagos as $indice => $pago)
                                @php
                                    $nombre = trim(implode(' ', array_filter([
                                        $pago->persona_nombre ?? $pago->postulante_nombre,
                                        $pago->persona_primerapellido ?? $pago->postulante_primerapellido,
                                        $pago->persona_segundoapellido ?? $pago->postulante_segundoapellido,
                                    ])));
                                @endphp
                                <tr>
                                    <td>{{ $pagos->firstItem() + $indice }}</td>
                                    <td>{{ $pago->inscrito_codigo ?? $pago->postulante_codigo ?? 'PAG-'.$pago->id_pago }}</td>
                                    <td>{{ $pago->deposito ?? '—' }}</td>
                                    <td>{{ $pago->fecha ? \Illuminate\Support\Carbon::parse($pago->fecha)->format('d/m/Y') : '—' }}</td>
                                    <td>{{ $nombre !== '' ? $nombre : 'Postulante sin nombre' }}</td>
                                    <td>{{ $pago->usuario_nombre ?? $pago->usuario_email ?? '—' }}</td>
                                    <td>{{ $pago->monto !== null ? number_format((float) $pago->monto, 2) : '—' }}</td>
                                    <td>
                                        @if ($pago->valido === null)
                                            Pendiente
                                        @elseif ($pago->valido)
                                            Sí
                                        @else
                                            No
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Ningún registro encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    </div>
</div>
@stop
