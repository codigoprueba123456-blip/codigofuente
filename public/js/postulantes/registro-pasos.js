document.addEventListener('DOMContentLoaded', function () {
    window.cambiarPaso = function (numeroPaso) {
        for (let paso = 1; paso <= 4; paso++) {
            const panel = document.getElementById(`paneStep${paso}`);
            const indicador = document.getElementById(`stepIndicator${paso}`);

            if (panel) {
                panel.classList.toggle('d-none', paso !== numeroPaso);
            }

            if (indicador) {
                indicador.classList.toggle('active', paso === numeroPaso);
                indicador.classList.toggle('completed', paso < numeroPaso);
            }
        }

        window.scrollTo({
            top: 120,
            behavior: 'smooth'
        });
    };

    document.querySelectorAll('.step-item').forEach(function (item) {
        item.addEventListener('click', function () {
            const paso = Number.parseInt(this.dataset.step, 10);

            if (paso) {
                window.cambiarPaso(paso);
            }
        });
    });

    window.cambiarPaso(1);
});
