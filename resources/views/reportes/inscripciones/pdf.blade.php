<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $configuracion['titulo'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        h1 { color: #23476a; font-size: 17px; margin-bottom: 5px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background: #e8eef4; }
        .total { margin-top: 10px; text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $configuracion['titulo'] }}</h1>
    <div class="meta">Fecha de emisión: {{ now()->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                @foreach ($configuracion['columnas'] as $encabezado)
                    <th>{{ $encabezado }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach (array_keys($configuracion['columnas']) as $campo)
                        <td>{{ $fila->{$campo} ?? '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($configuracion['columnas']) }}">No se encontraron registros para los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <p class="total">Total de registros: {{ $filas->count() }}</p>
</body>
</html>
