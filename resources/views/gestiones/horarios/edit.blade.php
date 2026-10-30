@extends('adminlte::page')

@section('title', 'Editar docente asignado')
@section('content_header')<h1>Editar docente asignado</h1>@stop
@section('content')
<div class="card card-outline card-secondary">
    <form action="{{ route('admin.asignacion-docente.asignar', $grupo->id_grupo) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="alert alert-light border">
                <div><strong>Grupo:</strong> {{ $grupo->codigo }}</div>
                <div><strong>Materia:</strong> {{ $grupo->materia->nombre ?? 'Sin materia' }}</div>
            </div>
            <div class="form-group mb-0">
                <label for="id_persona">Docente</label>
                <select name="id_persona" id="id_persona" class="form-control">
                    <option value="">-- Sin asignar --</option>
                    @foreach($docentes as $docente)
                        <option value="{{ $docente->id_persona }}" @selected(old('id_persona', $grupo->id_persona) == $docente->id_persona)>
                            {{ $docente->nombre }} {{ $docente->primerapellido }} {{ $docente->segundoapellido }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('admin.asignacion-docente.index') }}" class="btn btn-light border mr-2">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar cambios</button>
        </div>
    </form>
</div>
@stop
