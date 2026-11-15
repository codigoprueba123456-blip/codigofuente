@extends('adminlte::page')

@section('title', 'Programar grupos')

@section('content')
<div class="container-fluid px-3 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1 text-dark fw-semibold">Programar Grupos</h3>
            <small class="text-muted">Programación académica</small>
        </div>
        <a href="{{ route('admin.grupos.index') }}" class="btn btn-sm btn-light border shadow-sm px-3">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <form action="{{ route('admin.grupos.store') }}" method="POST" class="m-0">
            @csrf
            <div class="card-body px-4 py-4 px-md-5 py-md-5">
                <div class="row g-4 align-items-end">
                    <div class="col-md-6">
                        <div class="mb-0">
                            <label for="id_carrera" class="form-label fw-semibold text-dark mb-2">
                                Carrera <span class="text-danger">*</span>
                            </label>
                            <select name="id_carrera" id="id_carrera" class="form-select form-select-lg border border-secondary-subtle bg-light rounded-3 shadow-none" required>
                                <option value="">Seleccionar</option>
                                @foreach($carreras as $carrera)
                                    <option value="{{ $carrera->id_carrera }}" data-regimen="{{ $carrera->regimen }}" @selected(old('id_carrera') == $carrera->id_carrera)>
                                        {{ $carrera->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-0">
                            <label for="nivel" class="form-label fw-semibold text-dark mb-2">
                                Nivel <span class="text-danger">*</span>
                            </label>
                            <select name="nivel" id="nivel" class="form-select form-select-lg border border-secondary-subtle bg-light rounded-3 shadow-none" data-nivel-carrera="id_carrera" data-initial-value="{{ old('nivel') }}" data-placeholder="Seleccionar" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-1 align-items-end">
                    <div class="col-md-4">
                        <div class="mb-0">
                            <label for="turno" class="form-label fw-semibold text-dark mb-2">Turno</label>
                            <select name="turno" id="turno" class="form-select border border-secondary-subtle bg-light rounded-3 shadow-none">
                                <option value="">Seleccionar</option>
                                <option value="1" @selected(old('turno') == 1)>Mañana</option>
                                <option value="2" @selected(old('turno') == 2)>Tarde</option>
                                <option value="3" @selected(old('turno') == 3)>Noche</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-0">
                            <label for="capacidad" class="form-label fw-semibold text-dark mb-2">Capacidad</label>
                            <input type="number" min="1" name="capacidad" id="capacidad" class="form-control border border-secondary-subtle bg-light rounded-3 shadow-none" value="{{ old('capacidad') }}" placeholder="40">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded-3 bg-light px-3 py-3 mt-2 border-secondary-subtle">
                            <div class="form-check d-flex align-items-center mb-0">
                                <input type="hidden" name="abierto" value="0">
                                <input type="checkbox" name="abierto" id="abierto" value="1" class="form-check-input" @checked(old('abierto', true))>
                                <label for="abierto" class="form-check-label ms-2 fw-semibold text-secondary">Grupo abierto</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer border-0 bg-white px-4 px-md-5 py-3 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.grupos.index') }}" class="btn btn-light border shadow-sm px-4">Cancelar</a>
                <button type="submit" class="btn btn-success px-4 shadow-sm" style="background: linear-gradient(135deg, #015001, #0d6e35); border-color: #015001;">
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@stop

@include('partials.niveles-por-regimen')
