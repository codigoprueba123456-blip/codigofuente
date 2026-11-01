<div class="table-responsive mt-4">
    <table class="table table-bordered table-striped table-hover">
        <thead class="thead-light">
            <tr>
                @foreach ($configuracion['columnas'] as $encabezado)
                    <th>{{ $encabezado }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach (array_keys($configuracion['columnas']) as $campo)
                        <td>{{ $fila->{$campo} ?? '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($configuracion['columnas']) }}" class="text-center text-muted">
                        No se encontraron registros para los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <p class="text-muted mb-0">Total de registros: {{ $filas->count() }}</p>
</div>
