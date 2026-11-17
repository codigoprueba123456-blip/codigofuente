@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const etiquetasPorRegimen = @json(\App\Models\Carrera::etiquetasNivelesPorRegimen());

        document.querySelectorAll('[data-nivel-carrera]').forEach(function (nivelSelect) {
            const carreraSelect = document.getElementById(nivelSelect.dataset.nivelCarrera);
            if (!carreraSelect) {
                return;
            }

            const placeholder = nivelSelect.dataset.placeholder || 'Seleccione un nivel';
            let valorInicial = nivelSelect.dataset.initialValue || nivelSelect.value;

            function actualizarNiveles(conservarInicial) {
                const opcionCarrera = carreraSelect.options[carreraSelect.selectedIndex];
                const regimen = opcionCarrera && opcionCarrera.dataset.regimen
                    ? opcionCarrera.dataset.regimen
                    : {{ \App\Models\Carrera::REGIMEN_ANUAL }};
                const etiquetas = etiquetasPorRegimen[regimen] || {};
                const valorActual = conservarInicial ? (nivelSelect.value || valorInicial) : '';

                nivelSelect.innerHTML = '';
                nivelSelect.add(new Option(placeholder, ''));

                Object.keys(etiquetas).forEach(function (nivel) {
                    nivelSelect.add(new Option(etiquetas[nivel], nivel));
                });

                nivelSelect.disabled = Object.keys(etiquetas).length === 0
                    || (nivelSelect.dataset.requireCarrera === 'true' && !carreraSelect.value);
                if (Object.prototype.hasOwnProperty.call(etiquetas, valorActual)) {
                    nivelSelect.value = valorActual;
                }

                valorInicial = '';
            }

            carreraSelect.addEventListener('change', function () {
                actualizarNiveles(false);
            });
            actualizarNiveles(true);
        });
    });
</script>
@endpush
