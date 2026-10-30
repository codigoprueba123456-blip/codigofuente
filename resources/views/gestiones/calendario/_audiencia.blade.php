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
    <select name="carreras[]" id="carreras" class="form-control" multiple size="5">
        @foreach($carreras as $carrera)
            <option value="{{ $carrera->id_carrera }}" {{ in_array($carrera->id_carrera, $carrerasSeleccionadas) ? 'selected' : '' }}>{{ $carrera->nombre }}</option>
        @endforeach
    </select>
</div>

<div class="form-group audiencia-destino" data-audiencia="usuario">
    <label for="usuarios">Usuarios</label>
    <select name="usuarios[]" id="usuarios" class="form-control" multiple size="5">
        @foreach($usuarios as $usuario)
            <option value="{{ $usuario->id_persona }}" {{ in_array($usuario->id_persona, $usuariosSeleccionados) ? 'selected' : '' }}>
                {{ $usuario->name }} ({{ $usuario->usuario }})
            </option>
        @endforeach
    </select>
</div>

@push('js')
<script>
    document.querySelectorAll('.audiencia-opcion').forEach(function (option) {
        option.addEventListener('change', function () {
            document.querySelectorAll('.audiencia-destino').forEach(function (destino) {
                var activo = destino.dataset.audiencia === option.value;
                destino.classList.toggle('d-none', !activo);
                destino.querySelector('select').required = activo;
            });
        });
    });
    document.querySelector('.audiencia-opcion:checked')?.dispatchEvent(new Event('change'));
</script>
@endpush
