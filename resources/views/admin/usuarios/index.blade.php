@extends('adminlte::page')
@section('title', 'Usuarios')

@section('content')
<div class="card mt-3">
    <div class="card-header">
        <form action="{{ route('admin.usuarios.index') }}" method="GET" class="mb-0">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Primer Apellido</th>
                    <th>Segundo Apellido</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $index => $usuario)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $usuario->persona->primerapellido }}</td>
                    <td>{{ $usuario->persona->segundoapellido }}</td>
                    <td>{{ $usuario->persona->nombre }}</td>
                    <td>{{ $usuario->persona->email }}</td>
                    <td>{{ $usuario->usuario }}</td>
                    <td>
                        @if($usuario->cuenta && $usuario->cuenta->rol)
                            <span class="badge badge-primary">{{ $usuario->cuenta->rol->nombre }}</span>
                        @else
                            <span class="badge badge-secondary">Sin rol</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.usuarios.edit', $usuario->id_persona) }}" class="btn btn-sm btn-info"><i class="fa fa-edit"></i></a>
                        <button type="button"
                                class="btn btn-sm btn-warning"
                                title="Restablecer contraseña"
                                aria-label="Restablecer contraseña"
                                data-toggle="modal"
                                data-target="#resetPasswordModal"
                                data-action="{{ route('admin.usuarios.regenerar-credenciales', $usuario->id_persona) }}"
                                data-user="{{ trim(($usuario->persona->nombre ?? '') . ' ' . ($usuario->persona->primerapellido ?? '')) }}">
                            <i class="fa fa-key"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No hay usuarios registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="resetPasswordModalLabel">
                    <i class="fas fa-key mr-2"></i> Restablecer contraseña
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="resetPasswordForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-0">
                        ¿Deseas generar una nueva contraseña para
                        <strong id="resetPasswordUser"></strong>?
                        Se actualizarán sus credenciales y se abrirá el comprobante PDF.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check mr-1"></i> Sí, restablecer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    $('#resetPasswordModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#resetPasswordForm').attr('action', button.data('action'));
        $('#resetPasswordUser').text(button.data('user'));
    });
</script>
@stop