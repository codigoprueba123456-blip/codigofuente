@foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $type)
    @if (session()->has($key))
        <span hidden data-sweetalert data-kind="{{ $type }}" data-title="{{ ['success' => 'Operación completada', 'error' => 'No se pudo completar la operación', 'warning' => 'Atención', 'info' => 'Información'][$key] }}" data-message="{{ session($key) }}"></span>
    @endif
@endforeach

@if (isset($errors) && $errors->any())
    <span hidden data-sweetalert data-kind="validation" data-title="Revisa el formulario" data-message="{{ implode(' · ', $errors->all()) }}"></span>
@endif
