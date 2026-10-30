<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Credenciales</title>
    <style>
        @page {
            size: letter;
            margin: 15mm 12mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 10pt;
            line-height: 1.4;
        }

        .container {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .institution-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        .system-subtitle {
            font-size: 9pt;
            color: #64748b;
        }

        .title-box {
            text-align: center;
            background-color: #f1f5f9;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .title-box h2 {
            margin: 0;
            font-size: 12pt;
            color: #0f172a;
        }

        .section-header {
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
            border-left: 4px solid #0284c7;
            padding-left: 8px;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #f1f5f9;
        }

        .label {
            font-weight: bold;
            color: #475569;
            width: 35%;
        }

        .value {
            color: #0f172a;
            width: 65%;
        }

        /* Recuadro Destacado de Credenciales */
        .credentials-card {
            background-color: #0f172a;
            color: #ffffff;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }

        .cred-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cred-label {
            color: #94a3b8;
            font-size: 9pt;
            text-transform: uppercase;
            width: 40%;
        }

        .cred-value-user {
            font-family: 'Courier New', monospace;
            font-size: 12pt;
            font-weight: bold;
            color: #38bdf8;
        }

        .cred-value-pass {
            font-family: 'Courier New', monospace;
            font-size: 12pt;
            font-weight: bold;
            color: #f43f5e;
        }

        .instructions-box {
            background-color: #fefce8;
            border: 1px solid #fef08a;
            border-left: 4px solid #eab308;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 8.5pt;
            color: #713f12;
        }

        .signatures-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #94a3b8;
            margin: 0 20px;
            padding-top: 5px;
            font-size: 8.5pt;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <table class="header-table">
        <tr>
            <td>
                <div class="institution-title">INSTITUTO TECNOLÓGICO ACADÉMICO</div>
                <div class="system-subtitle">Sistema Web de Gestión Académica</div>
            </td>
            <td style="text-align: right;">
                <span style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 8pt;">
                    OFICIAL
                </span>
            </td>
        </tr>
    </table>

    <div class="title-box">
        <h2>{{ $titulo }}</h2>
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
                <td class="cred-value-user">{{ $user->username }}</td>
            </tr>
            <tr>
                <td class="cred-label">Contraseña Temporal:</td>
                <td class="cred-value-pass">{{ $plainPassword }}</td>
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
                <div class="signature-line">Administración de Sistemas</div>
            </td>
            <td>
                <div class="signature-line">Firma de Conformidad</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>