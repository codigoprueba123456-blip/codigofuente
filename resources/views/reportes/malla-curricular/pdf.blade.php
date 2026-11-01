@php
    $nombreInstituto = $instituto->nombreinstituto ?? 'Instituto Tecnológico Sacaba';
    $sigla = $instituto->abreviatura ?? 'ITSA';
    $logoSrc = null;
    $rutas = [];
    if (!empty($instituto?->logo)) { $rutas[] = storage_path('app/public/' . $instituto->logo); }
    $rutas[] = public_path('images/logo.png');
    foreach ($rutas as $ruta) {
        if (is_file($ruta)) {
            $mime = strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($ruta));
            break;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Malla Curricular - {{ $carrera->codigo }}</title>
    <style>
        @page { size: a4 portrait; margin: 12mm 10mm 14mm 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1f2d24; }
        .header { width: 100%; border-collapse: collapse; background-color: #14532d; }
        .header td { padding: 10px 14px; vertical-align: middle; }
        .logo-box { background: #ffffff; border-radius: 5px; padding: 4px; width: 66px; text-align: center; }
        .logo-box img { width: 64px; }
        .inst { font-size: 12.5pt; font-weight: bold; color: #ffffff; text-transform: uppercase; }
        .sigla { font-size: 9pt; font-weight: bold; color: #bbf7d0; letter-spacing: 2px; }
        .sub { font-size: 8pt; color: #dcfce7; }
        .bar { height: 4px; background-color: #22c55e; }
        .title { text-align: center; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 6px; margin: 10px 0 8px 0; }
        .title h2 { margin: 0; font-size: 11.5pt; color: #14532d; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta td { padding: 3px 4px; font-size: 8.5pt; }
        .meta .k { font-weight: bold; color: #3f6b4e; width: 18%; }
        table.malla { width: 100%; border-collapse: collapse; }
        table.malla th { background: #14532d; color: #ffffff; padding: 5px; font-size: 8pt; border: 1px solid #14532d; }
        table.malla td { border: 1px solid #bbf7d0; padding: 4px 5px; }
        tr.nivel td { background: #dcfce7; color: #14532d; font-weight: bold; }
        tr.total td { background: #f0fdf4; font-weight: bold; border-top: 2px solid #16a34a; }
        .c { text-align: center; }
        .footer { margin-top: 10px; text-align: center; font-size: 7.5pt; color: #14532d; border-top: 3px solid #16a34a; padding-top: 5px; background: #dcfce7; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            @if ($logoSrc)
                <td style="width:78px"><div class="logo-box"><img src="{{ $logoSrc }}" alt="Logo {{ $sigla }}"></div></td>
            @endif
            <td>
                <div class="inst">{{ $nombreInstituto }}</div>
                <div class="sigla">{{ $sigla }}</div>
                <div class="sub">Sistema Web de Gestión Curricular y Administración Académica</div>
            </td>
        </tr>
    </table>
    <div class="bar"></div>

    <div class="title"><h2>MALLA CURRICULAR - {{ mb_strtoupper($carrera->nombre) }}</h2></div>
    <table class="meta">
        <tr>
            <td class="k">Código:</td><td>{{ $carrera->codigo }}</td>
            <td class="k">Versión:</td><td>{{ $carrera->version ?? '-' }}</td>
        </tr>
        <tr>
            <td class="k">Resolución:</td><td>{{ implode(' · ', $carrera->resolucionesMinisteriales()) ?: '-' }}</td>
            <td class="k">Emitido:</td><td>{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="malla">
        <thead>
            <tr>
                <th style="width:36px">N°</th><th style="width:70px">Código</th><th>Materia</th>
                <th style="width:42px">Horas</th><th style="width:50px">Créditos</th><th style="width:100px">Prerrequisitos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($niveles as $nivel => $materias)
                <tr class="nivel"><td colspan="6">{{ $nivel ? 'Nivel '.$nivel : 'Sin nivel asignado' }}</td></tr>
                @foreach ($materias as $materia)
                    <tr>
                        <td class="c">{{ $materia->numero ?? '-' }}</td>
                        <td>{{ $materia->codigo ?? '-' }}</td>
                        <td>{{ $materia->nombre }}</td>
                        <td class="c">{{ $materia->horas ?? '-' }}</td>
                        <td class="c">{{ $materia->creditajemateria ?? '-' }}</td>
                        <td>{{ $materia->prerequisitos->pluck('codigo')->filter()->implode(', ') ?: '-' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6" class="c">La carrera no tiene materias registradas.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="3" style="text-align:right">TOTAL ({{ $totales['materias'] }} materias)</td>
                <td class="c">{{ $totales['horas'] }}</td><td class="c">{{ $totales['creditos'] }}</td><td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer"><strong>{{ $nombreInstituto }} ({{ $sigla }})</strong> · {{ $instituto->ubicacion ?? 'Sacaba - Cochabamba, Bolivia' }}</div>
</body>
</html>
