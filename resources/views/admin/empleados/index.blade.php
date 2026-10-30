@extends('adminlte::page')
@section('content')
<div class="card mt-3">
    <div class="card-header">
        <a href="{{ route('admin.empleados.create') }}" class="btn btn-default"><i class="fa fa-plus"></i> Nuevo</a>
        <a href="{{ route('admin.empleados.index') }}" class="btn btn-default"><i class="fa fa-sync"></i> Actualizar</a>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.empleados.index') }}" method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Buscar...">
                <div class="input-group-append">
                    <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Primer Apellido</th><th>Segundo Apellido</th><th>Nombre</th><th>DNI</th><th>Dirección</th>
                    <th>Teléfono</th><th>Celular</th><th>Email</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($empleados as $emp)
                <tr>
                    <td>{{ $emp->persona->primerapellido }}</td>
                    <td>{{ $emp->persona->segundoapellido }}</td>
                    <td>{{ $emp->persona->nombre }}</td>
                    <td>{{ $emp->persona->dni }}</td>
                    <td>{{ $emp->persona->direccion }}</td>
                    <td>{{ $emp->persona->telefono }}</td>
                    <td>{{ $emp->persona->celular }}</td>
                    <td>{{ $emp->persona->email }}</td>
                    <td>
                        @php
                            $imagenesEmpleado = collect();
                            if ($emp->persona->foto) {
                                $imagenesEmpleado->push([
                                    'url' => url('storage/' . $emp->persona->foto),
                                    'nombre' => basename($emp->persona->foto),
                                    'tipo' => 'Foto del empleado',
                                ]);
                            }
                            foreach ($emp->documentos as $documento) {
                                $imagenesEmpleado->push([
                                    'url' => url('storage/' . $documento->ruta),
                                    'nombre' => basename($documento->ruta),
                                    'tipo' => 'Documento ' . $documento->slot,
                                ]);
                            }
                        @endphp
                        <a href="{{ route('admin.empleados.edit', $emp->id_persona) }}" class="btn btn-sm btn-default" aria-label="Editar empleado"><i class="fa fa-edit"></i></a>
                        <button
                            type="button"
                            class="btn btn-sm btn-danger"
                            data-toggle="modal"
                            data-target="#deleteEmployeeModal"
                            data-delete-url="{{ route('admin.empleados.destroy', $emp->id_persona) }}"
                            data-employee-name="{{ trim($emp->persona->nombre . ' ' . $emp->persona->primerapellido . ' ' . ($emp->persona->segundoapellido ?? '')) }}"
                            data-employee-images="{{ json_encode($imagenesEmpleado->values(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                            aria-label="Eliminar empleado"
                        ><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="deleteEmployeeForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger">
                    <h5 class="modal-title" id="deleteEmployeeModalLabel">Eliminar empleado</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Seguro que deseas eliminar a <strong id="deleteEmployeeName"></strong>?</p>
                    <p class="mb-2">Estas imágenes también se eliminarán:</p>
                    <div id="deleteEmployeeImages" class="row"></div>
                    <p id="deleteEmployeeNoImages" class="text-muted mb-0" hidden>Este empleado no tiene imágenes guardadas.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar empleado</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    $('#deleteEmployeeModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const modal = $(this);
        const imagesContainer = modal.find('#deleteEmployeeImages').empty();
        const images = JSON.parse(button.attr('data-employee-images') || '[]');

        modal.find('#deleteEmployeeForm').attr('action', button.attr('data-delete-url'));
        modal.find('#deleteEmployeeName').text(button.attr('data-employee-name'));
        modal.find('#deleteEmployeeNoImages').prop('hidden', images.length > 0);

        images.forEach(function (image) {
            const column = $('<div class="col-sm-4 mb-3"></div>');
            const preview = $('<img class="img-thumbnail mb-1" style="max-height: 160px;">')
                .attr('src', image.url)
                .attr('alt', image.tipo);
            const kind = $('<div class="small font-weight-bold"></div>').text(image.tipo);
            const filename = $('<div class="small text-muted text-break"></div>').text('Archivo: ' + image.nombre);

            column.append(preview, kind, filename);
            imagesContainer.append(column);
        });
    });
</script>
@endpush
@stop