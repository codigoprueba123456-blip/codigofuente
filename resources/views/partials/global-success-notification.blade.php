@if (session()->has('success'))
    <span hidden data-sweetalert data-kind="success" data-title="Operación completada" data-message="{{ session('success') }}"></span>
@endif
