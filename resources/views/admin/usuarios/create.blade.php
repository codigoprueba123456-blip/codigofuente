<form action="{{ route('empleados.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-4 form-group">
            <label>Nombre *</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>
        <div class="col-md-4 form-group">
            <label>Primer Apellido *</label>
            <input type="text" name="primerapellido" class="form-control" required>
        </div>
        <div class="col-md-4 form-group">
            <label>Segundo Apellido</label>
            <input type="text" name="segundoapellido" class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 form-group">
            <label>DNI *</label>
            <input type="text" name="dni" class="form-control" required>
        </div>
        <div class="col-md-4 form-group">
            <label>Lugar Expedición</label>
            <select name="lugarexpedicion" class="form-control">
                <option value="CB">Cochabamba</option>
                </select>
        </div>
        <div class="col-md-4 form-group">
            <label>Fecha de Nacimiento *</label>
            <input type="date" name="fechanacimiento" class="form-control" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
</form>