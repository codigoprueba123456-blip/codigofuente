<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nómina Oficial de Postulantes</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1F4E79; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #1F4E79; font-size: 16px; }
        .header h3 { margin: 5px 0; font-size: 14px; color: #555; }
        .info-reporte { margin-bottom: 15px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
        th { background-color: #1F4E79; color: #fff; font-size: 11px; text-align: center; }
        td { font-size: 11px; }
        .text-center { text-align: center; }
        .footer { margin-top: 50px; text-align: center; }
        .firma-box { display: inline-block; width: 250px; border-top: 1px solid #333; padding-top: 5px; margin-top: 40px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #1F4E79; color: #fff; border: none; cursor: pointer; border-radius: 4px;">🖨 Imprimir Documento</button>
        <button onclick="window.close()" style="padding: 8px 15px; background: #6c757d; color: #fff; border: none; cursor: pointer; border-radius: 4px;">✖ Cerrar</button>
    </div>

    <div class="header">
        <h2>INSTITUTO TECNOLÓGICO SACABA</h2>
        <h3>SISTEMA DE ADMISIONES E INSCRIPCIONES</h3>
        <p style="margin: 2px 0; font-weight: bold;">NÓMINA OFICIAL DE POSTULANTES</p>
    </div>

    <div class="info-reporte">
        <p><strong>Carrera:</strong> {{ $carreraSeleccionada->nombre ?? 'TODAS LAS CARRERAS' }}</p>
        <p><strong>Gestión Académica:</strong> {{ $gestionSeleccionada ? 'Gestión ' . $gestionSeleccionada->gestion . ' - Periodo ' . $gestionSeleccionada->periodo : 'TODAS LAS GESTIONES' }}</p>
        <p><strong>Fecha de Emisión:</strong> {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">Nº</th>
                <th width="15%">CÓDIGO</th>
                <th width="12%">FECHA</th>
                <th width="38%">APELLIDOS Y NOMBRES</th>
                <th width="15%">C.I.</th>
                <th width="15%">CELULAR</th>
            </tr>
        </thead>
        <tbody>
            @forelse($postulantesData as $index => $p)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $p->codigo ?? 'S/C' }}</td>
                    <td class="text-center">{{ $p->fecha ? date('d/m/Y', strtotime($p->fecha)) : '' }}</td>
                    <td>{{ $p->primerapellido }} {{ $p->segundoapellido ?? '' }}, {{ $p->nombre }}</td>
                    <td class="text-center">{{ $p->ci }}</td>
                    <td class="text-center">{{ $p->celular ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #777;">No se encontraron registros para este reporte.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 11px;">
        <strong>Total de registros en reporte:</strong> {{ count($postulantesData) }} postulantes.
    </div>

    <div class="footer">
        <div class="firma-box">
            Firma / Sello Dirección Académica
        </div>
    </div>

</body>
</html>