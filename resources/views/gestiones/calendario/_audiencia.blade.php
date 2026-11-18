@php
    $audiencia = old('audiencia');
    if (!$audiencia && isset($actividad)) {
        $audiencia = $actividad->carreras->isNotEmpty() ? 'carrera' : ($actividad->usuarios->isNotEmpty() ? 'usuario' : 'general');
    }
    $audiencia = $audiencia ?: 'general';
    $carrerasSeleccionadas = old('carreras', isset($actividad) ? $actividad->carreras->pluck('id_carrera')->all() : []);
    $usuariosSeleccionados = old('usuarios', isset($actividad) ? $actividad->usuarios->pluck('id_persona')->all() : []);
@endphp

<div class="form-group">
    <label>Habilitar evento para</label>
    <div class="custom-control custom-radio">
        <input type="radio" id="audiencia_general" name="audiencia" value="general" class="custom-control-input audiencia-opcion" {{ $audiencia === 'general' ? 'checked' : '' }}>
        <label class="custom-control-label" for="audiencia_general">Todos los usuarios</label>
    </div>
    <div class="custom-control custom-radio">
        <input type="radio" id="audiencia_carrera" name="audiencia" value="carrera" class="custom-control-input audiencia-opcion" {{ $audiencia === 'carrera' ? 'checked' : '' }}>
        <label class="custom-control-label" for="audiencia_carrera">Usuarios de carreras seleccionadas</label>
    </div>
    <div class="custom-control custom-radio">
        <input type="radio" id="audiencia_usuario" name="audiencia" value="usuario" class="custom-control-input audiencia-opcion" {{ $audiencia === 'usuario' ? 'checked' : '' }}>
        <label class="custom-control-label" for="audiencia_usuario">Usuarios específicos</label>
    </div>
</div>

<div class="form-group audiencia-destino" data-audiencia="carrera">
    <label for="carreras">Carreras</label>
    <div class="border rounded p-2" style="max-height: 240px; overflow-y: auto;">
        @foreach($carreras as $carrera)
            @php
                $resoluciones = $carrera->resolucionesMinisteriales();
            @endphp
            <div class="custom-control custom-checkbox">
                <input
                    type="checkbox"
                    id="carrera_{{ $carrera->id_carrera }}"
                    name="carreras[]"
                    value="{{ $carrera->id_carrera }}"
                    class="custom-control-input"
                    @checked(in_array((string) $carrera->id_carrera, array_map('strval', is_array($carrerasSeleccionadas) ? $carrerasSeleccionadas : []), true))
                >
                <label class="custom-control-label" for="carrera_{{ $carrera->id_carrera }}">
                    {{ $carrera->nombre . ($resoluciones ? ' · R.M. ' . implode(' / ', $resoluciones) : '') }}
                </label>
            </div>
        @endforeach
    </div>
</div>

<div class="form-group audiencia-destino" data-audiencia="usuario">
    <label for="usuarios">Usuarios</label>
    <input
        type="search"
        id="buscar_usuario"
        class="form-control mb-2"
        placeholder="Buscar por nombre o username..."
        aria-label="Buscar usuarios por nombre o username"
        data-user-search-input
        autocomplete="off"
    >
    <div id="usuarios" class="border rounded p-2" style="max-height: 240px; overflow-y: auto;" data-user-search-options>
        @foreach($usuarios as $usuario)
            <div class="custom-control custom-checkbox" data-user-search-option>
                <input
                    type="checkbox"
                    id="usuario_{{ $usuario->id_persona }}"
                    name="usuarios[]"
                    value="{{ $usuario->id_persona }}"
                    class="custom-control-input"
                    @checked(in_array((string) $usuario->id_persona, array_map('strval', is_array($usuariosSeleccionados) ? $usuariosSeleccionados : []), true))
                >
                <label class="custom-control-label" for="usuario_{{ $usuario->id_persona }}">
                    {{ $usuario->name }} ({{ $usuario->usuario }})
                </label>
            </div>
        @endforeach
    </div>
</div>

@push('js')
<script>
    document.querySelectorAll('.audiencia-opcion').forEach(function (option) {
        option.addEventListener('change', function () {
            document.querySelectorAll('.audiencia-destino').forEach(function (destino) {
                var activo = destino.dataset.audiencia === option.value;
                destino.classList.toggle('d-none', !activo);
            });
        });
    });

    var buscarUsuario = document.querySelector('[data-user-search-input]');
    var opcionesUsuarios = document.querySelector('[data-user-search-options]');
    if (buscarUsuario && opcionesUsuarios) {
        function normalizarBusqueda(texto) {
            return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
        }

        function filtrarUsuarios() {
            var termino = normalizarBusqueda(buscarUsuario.value.trim());
            opcionesUsuarios.querySelectorAll('[data-user-search-option]').forEach(function (usuario) {
                var coincide = normalizarBusqueda(usuario.textContent).includes(termino);
                var seleccionado = usuario.querySelector('input').checked;
                usuario.classList.toggle('d-none', !coincide && !seleccionado);
            });
        }

        buscarUsuario.addEventListener('input', filtrarUsuarios);
        opcionesUsuarios.addEventListener('change', filtrarUsuarios);
    }

    document.querySelector('.audiencia-opcion:checked')?.dispatchEvent(new Event('change'));
</script>
@endpush
