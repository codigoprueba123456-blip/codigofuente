<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nómina de Inscriptos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #1F4E79; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #1F4E79; font-size: 16px; }
        .header h3 { margin: 5px 0; font-size: 13px; color: #555; }
        .info-reporte { margin-bottom: 12px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background-color: #1F4E79; color: #fff; font-size: 9px; text-align: center; }
        td { font-size: 9px; }
        .text-center { text-align: center; }
        .footer { margin-top: 40px; text-align: center; }
        .firma-box { display: inline-block; width: 230px; border-top: 1px solid #333; padding-top: 5px; margin-top: 35px; }
        @media print {
            .no-print { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 18px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 14px; background: #1F4E79; color: #fff; border: 0; cursor: pointer; border-radius: 4px;">Imprimir / Guardar como PDF</button>
        <button onclick="window.close()" style="padding: 8px 14px; background: #6c757d; color: #fff; border: 0; cursor: pointer; border-radius: 4px;">Cerrar</button>
    </div>

    <div class="header">
        <h2>INSTITUTO TECNOLÓGICO SACABA</h2>
        <h3>SISTEMA DE ADMISIONES E INSCRIPCIONES</h3>
        <p style="margin: 2px 0; font-weight: bold;">NÓMINA OFICIAL DE INSCRIPTOS</p>
    </div>

    <div class="info-reporte">
        <p><strong>Carrera:</strong> {{ $carreraSeleccionada->nombre ?? 'TODAS LAS CARRERAS' }}</p>
        <p><strong>Gestión Académica:</strong> {{ $gestionSeleccionada ? 'Gestión '.$gestionSeleccionada->gestion.' - Periodo '.$gestionSeleccionada->periodo : 'TODAS LAS GESTIONES' }}</p>
        <p><strong>Año:</strong> {{ request('nivel') ? (['Primer año', 'Segundo año', 'Tercer año'][(int) request('nivel') - 1] ?? request('nivel').'° año') : 'Todos' }} |
            <strong>Turno:</strong> {{ request('turno') ?: 'Todos' }} |
            <strong>Paralelo:</strong> {{ request('paralelo') ?: 'Todos' }}</p>
        @if (request('buscar_rapido'))
            <p><strong>Búsqueda:</strong> {{ request('buscar_rapido') }}</p>
        @endif
        <p><strong>Fecha de emisión:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%">Nº</th>
                <th width="11%">CÓDIGO</th>
                <th width="9%">FECHA</th>
                <th width="14%">PRIMER APELLIDO</th>
                <th width="14%">SEGUNDO APELLIDO</th>
                <th width="14%">NOMBRE</th>
                <th width="11%">CI</th>
                <th width="10%">CELULAR</th>
                <th width="13%">CARRERA</th>
                <th width="8%">ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inscritosData as $index => $inscrito)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $inscrito->codigo ?? 'S/C' }}</td>
                    <td class="text-center">{{ $inscrito->fecha ? date('d/m/Y', strtotime($inscrito->fecha)) : '' }}</td>
                    <td>{{ $inscrito->primerapellido }}</td>
                    <td>{{ $inscrito->segundoapellido ?? '' }}</td>
                    <td>{{ $inscrito->nombre }}</td>
                    <td class="text-center">{{ $inscrito->ci ?? '—' }}</td>
                    <td class="text-center">{{ $inscrito->celular ?? '—' }}</td>
                    <td>
                        {{ $inscrito->carrera ?? 'Sin carrera' }}
                        @if ((int) $inscrito->cantidad_carreras_gestion > 1)
                            (Multi-carrera)
                        @endif
                    </td>
                    <td>{{ $inscrito->id_inscrito_reemplazo ? 'Reemplazada' : ($inscrito->activo ? 'Activa' : 'Deshabilitada') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 18px; color: #777;">No se encontraron inscripciones para este reporte.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 12px; font-size: 10px;">
        <strong>Total de registros en el reporte:</strong> {{ $inscritosData->count() }} inscripciones.
    </div>

    <div class="footer">
        <div class="firma-box">Firma / Sello Dirección Académica</div>
    </div>
</body>
</html>
