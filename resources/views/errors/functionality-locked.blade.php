@extends('adminlte::page')

@section('title', 'Sección en solo lectura')

@section('content')
<div class="container py-5">
    <div class="card border-warning shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-header bg-warning">
            <h3 class="card-title mb-0">
                <i class="fas fa-lock mr-2"></i> Sección en solo lectura
            </h3>
        </div>
        <div class="card-body text-center py-4">
            <p class="mb-3">Puedes consultar esta sección, pero no añadir, editar ni eliminar datos hasta que se habilite desde el Calendario Académico.</p>
            <a href="{{ url('/' . $resourcePath) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver a la sección
            </a>
        </div>
    </div>
</div>
@endsection
