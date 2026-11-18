<script>
    // Repite en el navegador la regla validada por el servidor para informar antes del envío.
    (() => {
        const inicio = document.getElementById('inicio');
        const fin = document.getElementById('fin');
        const error = document.getElementById('fin-date-error');
        const mensaje = 'La fecha de fin no puede ser anterior a la fecha de inicio.';

        // El atributo min limita el calendario y la validez personalizada cubre fechas ya ingresadas.
        const validarFechas = () => {
            fin.min = inicio.value;
            const fechaInvalida = inicio.value && fin.value && fin.value < inicio.value;
            fin.setCustomValidity(fechaInvalida ? mensaje : '');
            fin.classList.toggle('is-invalid', Boolean(fechaInvalida));
            error.textContent = fechaInvalida ? mensaje : '';
            error.hidden = !fechaInvalida;
        };

        inicio.addEventListener('change', validarFechas);
        fin.addEventListener('input', validarFechas);
        fin.addEventListener('change', validarFechas);
        validarFechas();
    })();
</script>
