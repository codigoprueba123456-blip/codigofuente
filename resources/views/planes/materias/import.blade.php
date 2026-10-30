@extends('adminlte::page')

@section('title', 'Cargar materias')

@section('content')
<div class="container-fluid pt-3">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Cargar materias del plan de estudios</h3>
        </div>

        <form action="{{ route('planes.carreras.materias.import.store', $carrera->id_carrera) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <p class="mb-3">Carrera: <strong>{{ $carrera->nombre }}</strong></p>

                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="form-group">
                    <label for="archivo">Archivo del plan de estudios</label>
                    <input type="file" name="archivo" id="archivo" class="form-control-file @error('archivo') is-invalid @enderror" accept=".xlsx,.xls,.csv,.pdf" required>
                    <small class="form-text text-muted">Excel: la primera fila debe tener encabezados como código, nombre, créditos, horas, nivel y número. PDF: use una tabla de texto con esas columnas.</small>
                    @error('archivo')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('planes.carreras.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Cargar materias</button>
            </div>
        </form>
    </div>
</div>
@endsection
