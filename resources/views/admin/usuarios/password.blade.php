<form action="{{ route('user.update.password', $user->id) }}" method="POST">
    @csrf @method('PUT')
    
    <div class="alert alert-danger">
        La contraseña debe tener entre 8-20 caracteres. Por lo menos una mayúscula, una minúscula y un dígito.
    </div>

    <div class="form-group">
        <label>Nueva contraseña *</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Repita la nueva contraseña *</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    
    <button type="submit" class="btn btn-primary">Guardar</button>
</form>