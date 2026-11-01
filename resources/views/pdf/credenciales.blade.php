@php
    // Datos institucionales (con valores por defecto del ITSA si aún no se configuró el instituto)
    $inst = \App\Models\Instituto::first();
    $nombreInstituto = $inst->nombreinstituto ?? 'Instituto Tecnológico Sacaba';
    $sigla = $inst->abreviatura ?? 'ITSA';
    $ubicacion = $inst->ubicacion ?? 'Sacaba - Cochabamba, Bolivia';
    $telefono = $inst->telefono ?? null;
    $resolucion = $inst->resolucionministerial ?? null;

    // Logo incrustado en base64 (DomPDF no siempre resuelve rutas web): primero el configurado, luego el del sistema
    $logoSrc = null;
    $rutas = [];
    if (!empty($inst?->logo)) {
        $rutas[] = storage_path('app/public/' . $inst->logo);
    }
    $rutas[] = public_path('images/logo.png');
    foreach ($rutas as $ruta) {
        if (is_file($ruta)) {
            $mime = strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($ruta));
            break;
        }
    }

    // Compatibilidad: algunos controladores envían $user y otros $usuario + $persona
    if (!isset($user)) {
        $user = (object) [
            'name' => trim(($persona->nombre ?? '') . ' ' . ($persona->primerapellido ?? '') . ' ' . ($persona->segundoapellido ?? '')),
            'ci' => $persona->ci ?? '-',
            'role' => $persona->tipo ?? '-',
            'carrera' => '-',
            'email' => $persona->email ?? '-',
            'username' => $usuario->usuario ?? '-',
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Credenciales - {{ $sigla }}</title>
    <style>
        @page {
            size: letter;
            margin: 14mm 12mm 18mm 12mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1f2d24;
            font-size: 10pt;
            line-height: 1.4;
        }

        .container {
            width: 100%;
            border: 1.5px solid #15803d;
            border-radius: 6px;
            padding: 0;
        }

        /* ---------- Encabezado institucional ---------- */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #14532d;
        }

        .header-table td {
            padding: 12px 16px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 92px;
        }

        .logo-box {
            background-color: #ffffff;
            border-radius: 6px;
            padding: 5px;
            width: 82px;
            text-align: center;
        }

        .logo-box img {
            width: 80px;
        }

        .institution-title {
            font-size: 14pt;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .institution-sigla {
            font-size: 9.5pt;
            font-weight: bold;
            color: #bbf7d0;
            letter-spacing: 2px;
        }

        .system-subtitle {
            font-size: 8.5pt;
            color: #dcfce7;
        }

        .badge {
            background-color: #bbf7d0;
            color: #14532d;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 8pt;
        }

        .accent-bar {
            height: 5px;
            background-color: #22c55e;
        }

        .content {
            padding: 16px 20px 12px 20px;
        }

        /* ---------- Título ---------- */
        .title-box {
            text-align: center;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .title-box h2 {
            margin: 0;
            font-size: 12pt;
            color: #14532d;
            letter-spacing: 0.5px;
        }

        .title-box .sub {
            font-size: 8.5pt;
            color: #4b7a5c;
            margin-top: 2px;
        }

        .section-header {
            font-size: 10pt;
            font-weight: bold;
            color: #14532d;
            border-left: 4px solid #16a34a;
            padding-left: 8px;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #dcfce7;
        }

        .label {
            font-weight: bold;
            color: #3f6b4e;
            width: 35%;
        }

        .value {
            color: #14291c;
            width: 65%;
        }

        /* ---------- Recuadro destacado de credenciales ---------- */
        .credentials-card {
            background-color: #14532d;
            color: #ffffff;
            border: 2px solid #22c55e;
            border-radius: 6px;
            padding: 14px 16px;
            margin: 12px 0;
        }

        .cred-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cred-table td {
            padding: 6px 0;
        }

        .cred-label {
            color: #bbf7d0;
            font-size: 9pt;
            text-transform: uppercase;
            width: 40%;
        }

        .cred-value-user {
            font-family: 'Courier New', monospace;
            font-size: 13pt;
            font-weight: bold;
            color: #ffffff;
            background-color: #166534;
            padding: 4px 10px;
            border-radius: 4px;
        }

        .cred-value-pass {
            font-family: 'Courier New', monospace;
            font-size: 13pt;
            font-weight: bold;
            color: #fef08a;
            background-color: #166534;
            padding: 4px 10px;
            border-radius: 4px;
        }

        .instructions-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #16a34a;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 8.5pt;
            color: #14532d;
        }

        .instructions-box ul {
            margin: 4px 0 0 0;
            padding-left: 16px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 36px;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #15803d;
            margin: 0 24px;
            padding-top: 5px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #14532d;
        }

        .signature-sub {
            font-size: 7.5pt;
            color: #4b7a5c;
            font-weight: normal;
        }

        /* ---------- Pie institucional ---------- */
        .footer-band {
            background-color: #dcfce7;
            border-top: 3px solid #16a34a;
            text-align: center;
            padding: 7px 10px;
            font-size: 7.5pt;
            color: #14532d;
        }
    </style>
</head>
<body>

<div class="container">
    <table class="header-table">
        <tr>
            @if ($logoSrc)
                <td class="logo-cell">
                    <div class="logo-box"><img src="{{ $logoSrc }}" alt="Logo {{ $sigla }}"></div>
                </td>
            @endif
            <td>
                <div class="institution-title">{{ $nombreInstituto }}</div>
                <div class="institution-sigla">{{ $sigla }}</div>
                <div class="system-subtitle">Sistema Web de Gestión Curricular y Administración Académica</div>
            </td>
            <td style="text-align: right;">
                <span class="badge">OFICIAL</span>
            </td>
        </tr>
    </table>
    <div class="accent-bar"></div>

    <div class="content">
        <div class="title-box">
            <h2>{{ $titulo }}</h2>
            <div class="sub">Documento personal e intransferible · {{ $sigla }}</div>
        </div>

        <div class="section-header">Información del Usuario</div>
        <table class="data-table">
            <tr>
                <td class="label">Nombre Completo:</td>
                <td class="value"><strong>{{ $user->name }}</strong></td>
            </tr>
            <tr>
                <td class="label">Cédula de Identidad:</td>
                <td class="value">{{ $user->ci }}</td>
            </tr>
            <tr>
                <td class="label">Rol Asignado:</td>
                <td class="value">{{ $user->role }}</td>
            </tr>
            <tr>
                <td class="label">Carrera / Unidad:</td>
                <td class="value">{{ $user->carrera }}</td>
            </tr>
            <tr>
                <td class="label">Correo Electrónico:</td>
                <td class="value">{{ $user->email }}</td>
            </tr>
            <tr>
                <td class="label">Fecha de Registro:</td>
                <td class="value">{{ $fecha }}</td>
            </tr>
        </table>

        <div class="section-header">Credenciales de Acceso Asignadas</div>
        <div class="credentials-card">
            <table class="cred-table">
                <tr>
                    <td class="cred-label">Usuario de Acceso:</td>
                    <td><span class="cred-value-user">{{ $user->username }}</span></td>
                </tr>
                <tr>
                    <td class="cred-label">Contraseña Temporal:</td>
                    <td><span class="cred-value-pass">{{ $plainPassword }}</span></td>
                </tr>
            </table>
        </div>

        <div class="instructions-box">
            <strong>Instrucciones de Seguridad:</strong>
            <ul>
                <li>Esta contraseña es <strong>temporal</strong>. Al iniciar sesión por primera vez, el sistema solicitará el cambio obligatorio de la misma.</li>
                <li>No comparta sus credenciales con terceros. El uso de la cuenta es personal e intransferible.</li>
            </ul>
        </div>

        <table class="signatures-table">
            <tr>
                <td>
                    <div class="signature-line">Administración de Sistemas<br><span class="signature-sub">{{ $sigla }}</span></div>
                </td>
                <td>
                    <div class="signature-line">Firma de Conformidad<br><span class="signature-sub">{{ $user->name }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-band">
        <strong>{{ $nombreInstituto }} ({{ $sigla }})</strong> · {{ $ubicacion }}
        @if ($telefono) · Tel. {{ $telefono }} @endif
        @if ($resolucion) · R.M. {{ $resolucion }} @endif
    </div>
</div>

</body>
</html>
