<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $configuracion['titulo'] }}</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.inscripciones.index', ['reporte' => $reporte]) }}">
            <input type="hidden" name="generar" value="1">
            <div class="row">
                @foreach ($configuracion['filtros'] as $filtro)
                    @php($valores = $opciones[$filtro['options']] ?? collect())
                    <div class="col-md-6 col-lg-3 form-group">
                        <label for="{{ $filtro['name'] }}">{{ $filtro['label'] }} <span class="text-danger">*</span></label>
                        <select
                            id="{{ $filtro['name'] }}"
                            name="{{ $filtro['name'] }}"
                            class="form-control @error($filtro['name']) is-invalid @enderror"
                            required
                        >
                            <option value="">Seleccione {{ strtolower($filtro['label']) }}</option>
                            @foreach ($valores as $opcion)
                                <option value="{{ $opcion->valor }}" @selected((string) request($filtro['name']) === (string) $opcion->valor)>
                                    {{ $opcion->etiqueta }}
                                </option>
                            @endforeach
                        </select>
                        @error($filtro['name'])
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search mr-1"></i> Generar reporte
            </button>
        </form>

        @if ($generado)
            <div class="mt-3">
                <a
                    class="btn btn-success mr-2"
                    href="{{ route('reportes.inscripciones.index', array_merge(['reporte' => $reporte], $filtros, ['formato' => 'excel'])) }}"
                >
                    <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                </a>
                <a
                    class="btn btn-danger"
                    href="{{ route('reportes.inscripciones.index', array_merge(['reporte' => $reporte], $filtros, ['formato' => 'pdf'])) }}"
                >
                    <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                </a>
            </div>
            @include('reportes.inscripciones.resultados')
        @endif
    </div>
</div>
