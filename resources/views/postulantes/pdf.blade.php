<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha de postulación - {{ $postulante->codigo ?? $postulante->ci }}</title>
    <link rel="stylesheet" href="{{ asset('css/postulantes/ficha.css') }}">
</head>
<body onload="window.print()">
    <div class="toolbar">
        <a href="{{ route('postulantes.bienvenida', ['ci_consulta' => $postulante->ci]) }}">Volver a postulaciones</a>
        <button type="button" onclick="window.print()">Imprimir o guardar como PDF</button>
    </div>

    <main class="sheet">
        <header class="header">
            <img class="logo" src="{{ asset('images/logo.png') }}" alt="Logo ITSA">
            <div class="institution">
                <h1>Instituto Tecnológico Sacaba</h1>
                <p>Sistema de admisiones e inscripciones</p>
            </div>
        </header>

        <section class="title">
            <h2>Ficha de postulación</h2>
            <p>Comprobante individual de registro por carrera</p>
        </section>

        <section class="identity">
            @if ($postulante->foto)
                <img class="photo" src="{{ asset('storage/'.$postulante->foto) }}" alt="Fotografía del postulante">
            @endif
            <div class="person">
                <h3>{{ $postulante->nombre }} {{ $postulante->primerapellido }} {{ $postulante->segundoapellido }}</h3>
                <p><strong>C.I.:</strong> {{ $postulante->ci }} · <strong>Código:</strong> {{ $postulante->codigo ?? 'Pendiente' }}</p>
                <p><strong>Carrera:</strong> {{ $postulante->carrera?->nombre ?? 'No registrada' }}</p>
                <p><strong>Gestión:</strong> {{ $postulante->gestionAcademica?->gestion ?? 'No registrada' }} · Periodo {{ $postulante->gestionAcademica?->periodo ?? '-' }}</p>
            </div>
        </section>

        <section class="section">
            <h3>Datos personales</h3>
            <table class="details">
                <tr>
                    <td><span class="label">Fecha de nacimiento</span><span class="value">{{ optional($postulante->fechanacimiento)->format('d/m/Y') }}</span></td>
                    <td><span class="label">Lugar de nacimiento</span><span class="value">{{ $postulante->lugarnacimiento }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Lugar de expedición</span><span class="value">{{ $postulante->lugarexpedicion }}</span></td>
                    <td><span class="label">Nacionalidad</span><span class="value">{{ $postulante->nacionalidad }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Sexo</span><span class="value">{{ $postulante->sexo }}</span></td>
                    <td><span class="label">Dirección</span><span class="value">{{ $postulante->direccion }}</span></td>
                </tr>
            </table>
        </section>

        <section class="section">
            <h3>Contacto</h3>
            <table class="details">
                <tr>
                    <td><span class="label">Celular</span><span class="value">{{ $postulante->celular }}</span></td>
                    <td><span class="label">Teléfono</span><span class="value">{{ $postulante->telefono ?: 'No registrado' }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Correo electrónico</span><span class="value">{{ $postulante->email }}</span></td>
                    <td><span class="label">Persona de contacto</span><span class="value">{{ $postulante->nombrecontacto }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Celular de contacto</span><span class="value">{{ $postulante->celularcontacto }}</span></td>
                    <td><span class="label">Parentesco</span><span class="value">{{ $postulante->parentescocontacto }}</span></td>
                </tr>
            </table>
        </section>

        <section class="section">
            <h3>Datos académicos y de postulación</h3>
            <table class="details">
                <tr>
                    <td><span class="label">Unidad educativa</span><span class="value">{{ $postulante->nombrecolegio }}</span></td>
                    <td><span class="label">Carácter del colegio</span><span class="value">{{ $postulante->caractercolegio }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Año de egreso</span><span class="value">{{ $postulante->egresocolegio }}</span></td>
                    <td><span class="label">Diploma de bachiller</span><span class="value">{{ $postulante->diplomabachiller ? 'Sí' : 'No' }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Carrera a la que postula</span><span class="value">{{ $postulante->carrera?->nombre ?? 'No registrada' }}</span></td>
                    <td><span class="label">Número de comprobante bancario</span><span class="value">{{ $postulante->deposito }}</span></td>
                </tr>
            </table>
        </section>

        <footer class="footer">
            Ficha emitida el {{ now()->format('d/m/Y H:i') }} · Conserve este documento como constancia de su postulación.
        </footer>
    </main>
</body>
</html>
