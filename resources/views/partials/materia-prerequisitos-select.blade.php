<div class="form-group">
    <label for="prerequisitos" class="font-weight-bold">Prerrequisitos</label>
    <select
        name="prerequisitos[]"
        id="prerequisitos"
        class="form-control select2 @error('prerequisitos') is-invalid @enderror"
        data-prerequisitos-select
        data-placeholder="Seleccione una o más materias"
        multiple
        style="width: 100%;"
    >
        @foreach($materiasDisponibles as $materiaDisponible)
            <option
                value="{{ $materiaDisponible->id_materia }}"
                data-carrera-id="{{ $materiaDisponible->id_carrera }}"
                {{ in_array((string) $materiaDisponible->id_materia, array_map('strval', is_array($prerequisitosSeleccionados) ? $prerequisitosSeleccionados : []), true) ? 'selected' : '' }}
            >
                {{ $materiaDisponible->nombre }}{{ $materiaDisponible->codigo ? ' (' . $materiaDisponible->codigo . ')' : '' }}
            </option>
        @endforeach
    </select>
    @error('prerequisitos')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('prerequisitos.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <small class="form-text text-muted">Las opciones disponibles corresponden a la carrera seleccionada.</small>
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const carrera = document.getElementById('id_carrera');
        const prerequisitos = document.querySelector('[data-prerequisitos-select]');
        if (!carrera || !prerequisitos) {
            return;
        }

        const opciones = Array.from(prerequisitos.options);

        function filtrarPrerequisitos(conservarSeleccion) {
            const seleccionados = conservarSeleccion
                ? Array.from(prerequisitos.selectedOptions).map(function (option) {
                    return option.value;
                })
                : [];
            const carreraId = carrera.value;

            opciones.forEach(function (option) {
                option.disabled = !carreraId || option.dataset.carreraId !== carreraId;
                option.hidden = option.disabled;
                if (option.disabled) {
                    option.selected = false;
                } else if (seleccionados.includes(option.value)) {
                    option.selected = true;
                }
            });

            if (window.jQuery && jQuery.fn.select2 && jQuery(prerequisitos).hasClass('select2-hidden-accessible')) {
                jQuery(prerequisitos).trigger('change.select2');
            }
        }

        if (window.jQuery && jQuery.fn.select2) {
            jQuery(prerequisitos).select2({
                width: '100%',
                placeholder: prerequisitos.dataset.placeholder,
                closeOnSelect: false,
            });
        }

        carrera.addEventListener('change', function () {
            filtrarPrerequisitos(false);
        });
        filtrarPrerequisitos(true);
    });
</script>
@endpush
