<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de pagos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
        h2 { color: #1F4E79; text-align: center; margin-bottom: 4px; }
        p { text-align: center; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        th { background: #1F4E79; color: #fff; }
        .no-print { text-align: right; margin-bottom: 16px; }
        @media print { .no-print { display: none; } body { margin: 10mm; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print">
        <button onclick="window.print()">Imprimir / Guardar como PDF</button>
        <button onclick="window.close()">Cerrar</button>
    </div>
    <h2>REPORTE DE PAGOS</h2>
    <p>Emitido: {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>Nº</th>
                <th>Código</th>
                <th>Depósito</th>
                <th>Fecha</th>
                <th>Estudiante / Postulante</th>
                <th>Usuario</th>
                <th>Monto (Bs.)</th>
                <th>Válido</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pagosData as $indice => $pago)
                @php
                    $nombre = trim(implode(' ', array_filter([
                        $pago->persona_nombre ?? $pago->postulante_nombre,
                        $pago->persona_primerapellido ?? $pago->postulante_primerapellido,
                        $pago->persona_segundoapellido ?? $pago->postulante_segundoapellido,
                    ])));
                @endphp
                <tr>
                    <td>{{ $indice + 1 }}</td>
                    <td>{{ $pago->inscrito_codigo ?? $pago->postulante_codigo ?? 'PAG-'.$pago->id_pago }}</td>
                    <td>{{ $pago->deposito ?? '—' }}</td>
                    <td>{{ $pago->fecha ? \Illuminate\Support\Carbon::parse($pago->fecha)->format('d/m/Y') : '—' }}</td>
                    <td>{{ $nombre !== '' ? $nombre : 'Postulante sin nombre' }}</td>
                    <td>{{ trim(implode(' ', array_filter([$pago->usuario_nombre, $pago->usuario_email]))) ?: '—' }}</td>
                    <td>{{ $pago->monto !== null ? number_format((float) $pago->monto, 2) : '—' }}</td>
                    <td>{{ $pago->valido === null ? 'Pendiente' : ($pago->valido ? 'Sí' : 'No') }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No se encontraron pagos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
