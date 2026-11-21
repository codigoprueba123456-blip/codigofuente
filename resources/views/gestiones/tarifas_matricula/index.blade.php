@extends('adminlte::page')

@section('title', 'Tarifas de matrícula')
@section('content_header')<h1>Tarifas de matrícula · {{ $gestion->etiqueta }}</h1>@stop

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Agregar o reemplazar tarifa</h3></div>
        <form action="{{ route('admin.gestionacademica.tarifas.store', $gestion->id_gestionacademica) }}" method="POST">
            @csrf
            <div class="card-body">
                <p class="text-muted">
                    Se aplica primero el monto del nivel específico, luego “Todos los niveles” y, si no hay tarifa,
                    el cálculo actual de matrícula.
                </p>
                <div class="row">
                    <div class="col-md-5 form-group">
                        <label for="id_carrera">Carrera</label>
                        <select name="id_carrera" id="id_carrera" class="form-control @error('id_carrera') is-invalid @enderror" required>
                            <option value="">Seleccione una carrera</option>
                            @foreach($carreras as $carrera)
                                <option
                                    value="{{ $carrera->id_carrera }}"
                                    data-levels="{{ implode(',', \App\Models\Carrera::nivelesPorRegimen($carrera->regimen)) }}"
                                    @selected(old('id_carrera') == $carrera->id_carrera)
                                >
                                    {{ $carrera->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_carrera')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="nivel">Aplicación</label>
                        <select name="nivel" id="nivel" class="form-control @error('nivel') is-invalid @enderror" required>
                            <option value="0" @selected(old('nivel', '0') === '0')>Todos los niveles</option>
                            @for($nivel = 1; $nivel <= 6; $nivel++)
                                <option value="{{ $nivel }}" @selected((string) old('nivel') === (string) $nivel)>
                                    Nivel {{ $nivel }}
                                </option>
                            @endfor
                        </select>
                        @error('nivel')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="monto">Monto (Bs.)</label>
                        <input type="number" name="monto" id="monto" class="form-control @error('monto') is-invalid @enderror" min="0" step="0.01" value="{{ old('monto') }}" required>
                        @error('monto')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-1 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block" aria-label="Guardar tarifa">
                            <i class="fas fa-save"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Tarifas configuradas</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Carrera</th>
                        <th>Nivel</th>
                        <th>Monto (Bs.)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tarifas as $tarifa)
                        <tr>
                            <td>{{ $tarifa->carrera->nombre }}</td>
                            <td>{{ $tarifa->nivel === 0 ? 'Todos los niveles' : 'Nivel ' . $tarifa->nivel }}</td>
                            <td>
                                <form id="tarifa-form-{{ $tarifa->id_tarifa_matricula }}" action="{{ route('admin.gestionacademica.tarifas.update', [$gestion->id_gestionacademica, $tarifa->id_tarifa_matricula]) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                </form>
                                <input form="tarifa-form-{{ $tarifa->id_tarifa_matricula }}" type="number" name="monto" class="form-control" min="0" step="0.01" value="{{ $tarifa->monto }}" required>
                            </td>
                            <td class="text-nowrap">
                                <button form="tarifa-form-{{ $tarifa->id_tarifa_matricula }}" type="submit" class="btn btn-sm btn-primary" aria-label="Actualizar tarifa">
                                    <i class="fas fa-save"></i>
                                </button>
                                <form action="{{ route('admin.gestionacademica.tarifas.destroy', [$gestion->id_gestionacademica, $tarifa->id_tarifa_matricula]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" aria-label="Eliminar tarifa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No hay tarifas configuradas para esta gestión.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.gestionacademica.index') }}" class="btn btn-light border">Volver a gestiones</a>
        </div>
    </div>
</div>
@stop

@section('js')
    @parent
    <script>
        // Al elegir carrera limita las tarifas de nivel a los niveles de su régimen académico.
        (() => {
            const carrera = document.getElementById('id_carrera');
            const nivel = document.getElementById('nivel');
            const nivelesEspecificos = Array.from(nivel.options).filter((option) => option.value !== '0');

            const actualizarNiveles = () => {
                const seleccion = carrera.options[carrera.selectedIndex];
                const nivelesPermitidos = seleccion && seleccion.value
                    ? (seleccion.dataset.levels || '').split(',').filter(Boolean)
                    : nivelesEspecificos.map((option) => option.value);

                nivelesEspecificos.forEach((option) => {
                    option.disabled = !nivelesPermitidos.includes(option.value);
                });

                if (nivel.selectedOptions[0].disabled) {
                    nivel.value = '0';
                }
            };

            carrera.addEventListener('change', actualizarNiveles);
            actualizarNiveles();
        })();
    </script>
@stop
