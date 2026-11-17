document.addEventListener('DOMContentLoaded', function () {
    const carrera = document.getElementById('id_carrera');
    const gestion = document.getElementById('id_gestionacademica');
    const nivel = document.getElementById('nivelinicio');
    const turno = document.getElementById('turno');
    const paralelo = document.getElementById('paralelo');
    const cupos = document.getElementById('cupos_disponibles_edicion');
    const opcionesPorSeleccion = window.opcionesTurnosInscripcionEditar || {};
    const nombresTurno = { 1: 'Mañana', 2: 'Tarde', 3: 'Noche' };

    if (!carrera || !gestion || !nivel || !turno || !paralelo || !cupos) return;

    function opcionesDelNivel() {
        return opcionesPorSeleccion[gestion.value]?.[carrera.value]?.[nivel.value] || [];
    }

    function actualizarCupos() {
        const opcion = opcionesDelNivel().find(item =>
            String(item.turno) === turno.value && item.paralelo === paralelo.value
        );
        cupos.textContent = !opcion
            ? ''
            : opcion.actual
                ? 'Selección actual; no está entre las opciones disponibles actualmente.'
                : opcion.disponibles === null
                    ? 'Este grupo no tiene una capacidad máxima configurada.'
                    : `${opcion.disponibles} cupos disponibles`;
    }

    function actualizarParalelos(seleccionado = '') {
        const coincidentes = opcionesDelNivel()
            .filter(opcion => String(opcion.turno) === turno.value);
        paralelo.replaceChildren(new Option(
            coincidentes.length ? 'Selecciona un paralelo' : 'Sin paralelos disponibles',
            ''
        ));
        coincidentes.forEach(opcion => {
            const texto = opcion.actual
                ? `Paralelo ${opcion.paralelo} · selección actual`
                : opcion.disponibles === null
                    ? `Paralelo ${opcion.paralelo} · cupo no configurado`
                    : `Paralelo ${opcion.paralelo} · ${opcion.disponibles} cupos`;
            paralelo.add(new Option(texto, opcion.paralelo));
        });
        paralelo.disabled = coincidentes.length === 0;
        const valorSeleccionado = String(seleccionado).toUpperCase();
        if (valorSeleccionado && coincidentes.some(opcion => opcion.paralelo === valorSeleccionado)) {
            paralelo.value = valorSeleccionado;
        }
        actualizarCupos();
    }

    function actualizarTurnos(seleccionadoTurno = '', seleccionadoParalelo = '') {
        const opciones = opcionesDelNivel();
        const turnos = [...new Set(opciones.map(opcion => String(opcion.turno)))]
            .sort((a, b) => Number(a) - Number(b));
        turno.replaceChildren(new Option(
            turnos.length ? 'Selecciona un turno' : 'Sin turnos disponibles',
            ''
        ));
        turnos.forEach(valor => turno.add(new Option(nombresTurno[valor] || valor, valor)));
        turno.disabled = turnos.length === 0;
        if (seleccionadoTurno && turnos.includes(String(seleccionadoTurno))) {
            turno.value = String(seleccionadoTurno);
        }
        actualizarParalelos(seleccionadoParalelo);
    }

    carrera.addEventListener('change', () => actualizarTurnos());
    gestion.addEventListener('change', () => actualizarTurnos());
    nivel.addEventListener('change', () => actualizarTurnos());
    turno.addEventListener('change', () => actualizarParalelos());
    paralelo.addEventListener('change', actualizarCupos);
    actualizarTurnos(window.turnoInscripcionInicial, window.paraleloInscripcionInicial);
});
