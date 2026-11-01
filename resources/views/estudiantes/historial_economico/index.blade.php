@extends('adminlte::page')

@section('title', 'Historial Económico')

@section('content_header')
    <h1 style="margin: 0; font-weight: 400;">Historial Economico</h1>
@stop

@section('content')
<div style="background: #f3f4f6; min-height: 100%; padding: 0; border-top: 1px solid #dfe3e8; overflow-x: hidden;">
    <div style="padding: 0; margin: 0;">
        @if (! $persona || ! $persona->estudiante)
            <div class="alert alert-warning mb-0" style="margin: 0; border-radius: 0;">
                Esta cuenta todavía no está vinculada a una ficha de estudiante.
            </div>
        @else
            <div style="padding: 0 0.75rem;">
                <div style="margin: 1.2rem 0 0.8rem 0;">
                    <label for="id_carrera" style="display: block; margin-bottom: 0.45rem; font-size: 1.05rem; color: #2b2b2b; font-weight: 600;">Carreras: *</label>
                    <form method="GET" action="{{ route('estudiantes.historial_economico') }}" style="margin: 0;">
                        <select name="id_carrera" id="id_carrera" class="form-control" onchange="this.form.submit()" style="width: 100%; max-width: 430px; height: 42px; font-size: 1.05rem; border: 1px solid #cfd5da; border-radius: 4px; background-color: #fff; color: #1f1f1f; padding: 0 12px; box-shadow: none; cursor: pointer;">
                            @forelse ($carreras as $carrera)
                                @php
                                    $resoluciones = $carrera->resolucionesMinisteriales();
                                @endphp
                                <option value="{{ $carrera->id_carrera }}" @selected((int) $carreraSeleccionada === (int) $carrera->id_carrera)>
                                    {{ $carrera->nombre . ($resoluciones ? ' · R.M. ' . implode(' / ', $resoluciones) : '') }}
                                </option>
                            @empty
                                <option value="" selected disabled>No tienes carreras inscritas</option>
                            @endforelse
                        </select>
                    </form>
                </div>

                <div style="border: 1px solid #dfe3e8; background: white; width: 100%; max-width: 920px; overflow-x: hidden; margin-top: 0.4rem;">
                    <table class="table table-bordered" style="margin: 0; border-collapse: collapse; width: 100%; table-layout: fixed;">
                        <thead>
                            <tr style="background: #f1f3f5;">
                                <th style="width: 14%; border: 1px solid #dfe3e8; padding: 0.8rem 0.9rem; font-size: 0.95rem; font-weight: 700; color: #2f2f2f; text-align: left;">Gestión</th>
                                <th style="width: 8%; border: 1px solid #dfe3e8; padding: 0.8rem 0.9rem; font-size: 0.95rem; font-weight: 700; color: #2f2f2f; text-align: left;">N°</th>
                                <th style="width: 12%; border: 1px solid #dfe3e8; padding: 0.8rem 0.9rem; font-size: 0.95rem; font-weight: 700; color: #2f2f2f; text-align: left;">Código</th>
                                <th style="width: 22%; border: 1px solid #dfe3e8; padding: 0.8rem 0.9rem; font-size: 0.95rem; font-weight: 700; color: #2f2f2f; text-align: left;">Concepto</th>
                                <th style="width: 14%; border: 1px solid #dfe3e8; padding: 0.8rem 0.9rem; font-size: 0.95rem; font-weight: 700; color: #2f2f2f; text-align: left;">Pagado</th>
                                <th style="width: 18%; border: 1px solid #dfe3e8; padding: 0.8rem 0.9rem; font-size: 0.95rem; font-weight: 700; color: #2f2f2f; text-align: left;">Comprobante</th>
                                <th style="width: 12%; border: 1px solid #dfe3e8; padding: 0.8rem 0.9rem; font-size: 0.95rem; font-weight: 700; color: #2f2f2f; text-align: left;">Monto (Bs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pagos as $index => $pago)
                                <tr>
                                    <td style="border: 1px solid #dfe3e8; padding: 0.7rem 0.9rem; font-size: 0.97rem;">{{ $pago->gestion ?? '2023' }}</td>
                                    <td style="border: 1px solid #dfe3e8; padding: 0.7rem 0.9rem; font-size: 0.97rem;">{{ $index + 1 }}</td>
                                    <td style="border: 1px solid #dfe3e8; padding: 0.7rem 0.9rem; font-size: 0.97rem;">{{ $pago->codigo ?? 'MA' }}</td>
                                    <td style="border: 1px solid #dfe3e8; padding: 0.7rem 0.9rem; font-size: 0.97rem;">{{ $pago->concepto ?? 'MATRICULA' }}</td>
                                    <td style="border: 1px solid #dfe3e8; padding: 0.7rem 0.9rem; font-size: 0.97rem;">{{ $pago->pagado ?? 'Sí' }}</td>
                                    <td style="border: 1px solid #dfe3e8; padding: 0.7rem 0.9rem; font-size: 0.97rem;">
                                        {{ $pago->comprobante ?: '—' }}@if ($pago->comprobante && $pago->fecha) [{{ \Carbon\Carbon::parse($pago->fecha)->format('d/m/Y') }}]@endif
                                    </td>
                                    <td style="border: 1px solid #dfe3e8; padding: 0.7rem 0.9rem; font-size: 0.97rem;">{{ $pago->monto !== null ? number_format((float) $pago->monto, 2) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="padding: 1.2rem; text-align: center; color: #666;">No hay registros de pagos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@stop