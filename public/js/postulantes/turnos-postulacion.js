document.addEventListener('DOMContentLoaded', () => {
    const carrera = document.getElementById('id_carrera');
    const turno = document.getElementById('turno');
    const paralelo = document.getElementById('paralelo');
    const cupos = document.getElementById('cupos_disponibles');
    if (!carrera || !turno || !paralelo) return;

    const opcionesPorCarrera = window.opcionesTurnosCarrera || {};
    const valoresIniciales = {
        turno: String(window.turnoPostulacionInicial || ''),
        paralelo: String(window.paraleloPostulacionInicial || '')
    };
    const nombresTurno = { 1: 'Mañana', 2: 'Tarde', 3: 'Noche' };

    function actualizarParalelos(seleccionado = '') {
        const opciones = opcionesPorCarrera[carrera.value] || [];
        const coincidentes = opciones.filter(opcion => String(opcion.turno) === turno.value);
        paralelo.replaceChildren(new Option(coincidentes.length ? 'Selecciona un paralelo' : 'Sin paralelos disponibles', ''));
        coincidentes.forEach(opcion => {
            const texto = opcion.disponibles === null
                ? `Paralelo ${opcion.paralelo} · cupo no configurado`
                : `Paralelo ${opcion.paralelo} · ${opcion.disponibles} cupos`;
            paralelo.add(new Option(texto, opcion.paralelo));
        });
        paralelo.disabled = coincidentes.length === 0;
        if (seleccionado && coincidentes.some(opcion => opcion.paralelo === seleccionado)) {
            paralelo.value = seleccionado;
        }
        actualizarCupos();
    }

    function actualizarCupos() {
        if (!cupos) return;
        const opcion = (opcionesPorCarrera[carrera.value] || []).find(item =>
            String(item.turno) === turno.value && item.paralelo === paralelo.value
        );
        cupos.textContent = !opcion
            ? ''
            : opcion.disponibles === null
                ? 'Este grupo no tiene una capacidad máxima configurada.'
                : `${opcion.disponibles} cupos disponibles`;
    }

    function actualizarTurnos(seleccionadoTurno = '', seleccionadoParalelo = '') {
        const opciones = opcionesPorCarrera[carrera.value] || [];
        const turnos = [...new Set(opciones.map(opcion => String(opcion.turno)))];
        turno.replaceChildren(new Option(turnos.length ? 'Selecciona un turno' : 'Sin turnos disponibles', ''));
        turnos.forEach(valor => turno.add(new Option(nombresTurno[valor] || valor, valor)));
        turno.disabled = turnos.length === 0;
        if (seleccionadoTurno && turnos.includes(seleccionadoTurno)) {
            turno.value = seleccionadoTurno;
        }
        actualizarParalelos(seleccionadoParalelo);
    }

    carrera.addEventListener('change', () => actualizarTurnos());
    turno.addEventListener('change', () => actualizarParalelos());
    paralelo.addEventListener('change', actualizarCupos);
    actualizarTurnos(valoresIniciales.turno, valoresIniciales.paralelo);
});
