@vite(['resources/sass/app.scss', 'resources/js/app.js'])

<div class="itsa-postulante-public">
    <header class="public-hero">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 text-white">
                <a href="{{ route('postulantes.bienvenida') }}" aria-label="Ir al inicio de admisiones"
                    class="d-inline-flex align-items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo ITSA" class="postulantes-logo postulantes-logo-formulario">
                </a>
                    <div class="d-flex flex-column">
                        <span class="fw-bold institution-name">Instituto Tecnológico Sacaba</span>
                        <span class="text-white-50 small">Admisiones y Registro de Postulantes</span>
                    </div>
                </div>
                <a href="{{ route('postulantes.bienvenida', ['ci_consulta' => $postulante->ci]) }}"
                    class="btn btn-light btn-back font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </header>

    <main class="container py-4 py-lg-5">
        <div class="row">
            <div class="col-12">
        {{-- ALERTAS Y NOTIFICACIONES --}}
        @include('partials.sweetalert-messages')
        {{-- BANNER DE INFORMACIÓN PARA EDICIÓN --}}
        <div class="alert alert-warning shadow-sm border-left-warning mb-4" role="alert">
            <h5 class="font-weight-bold"><i class="fas id-card mr-1 text-warning"></i> Panel de Actualización de Expediente</h5>
            Estás editando la información correspondiente al postulante <strong>{{ $postulante->nombre }} {{ $postulante->primerapellido }}</strong> (C.I.: {{ $postulante->ci }}). 
            <br>Puedes modificar cualquier campo de texto o reemplazar los documentos digitales según sea necesario. Los archivos actuales se mantendrán si no seleccionas archivos nuevos.
        </div>

        <form action="{{ route('postulantes.update', $postulante->id_postulante) }}" method="POST" enctype="multipart/form-data" id="formEditarPostulante"
            novalidate
            data-validation-errors="{{ json_encode($errors->messages(), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">
            @csrf
            @method('PUT')

            {{-- CABECERA / FOTOGRAFÍA --}}
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-primary"><i class="fas fa-camera mr-1"></i> Cabecera / Fotografía</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-3">
                            <div class="photo-preview-box mb-2" id="photoPreview">
                                @if($postulante->foto)
                                    <img src="{{ asset('storage/'.$postulante->foto) }}" alt="Fotografía actual">
                                @else
                                    <i class="fas fa-camera"></i>
                                    <span>Sin fotografía</span>
                                @endif
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <label for="foto" class="btn btn-select-photo btn-block mb-1 font-weight-bold shadow-sm">
                                    <i class="fas fa-upload mr-1"></i> Cambiar fotografía (Opcional)
                                </label>
                            </div>
                            <input type="file" name="fotografia" id="foto" class="d-none" accept="image/jpeg,image/png">
                            <small class="text-muted d-block text-center mt-2">JPG o PNG. Se comprime hasta <strong>150 KB</strong>.</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <div class="intro-copy">
                                <span class="text-uppercase font-weight-bold text-info small d-block mb-1">Módulo de Edición</span>
                                <h2 class="font-weight-bold text-primary mb-2">Modificar Ficha de Admisión</h2>
                                <p class="mb-0 text-secondary">
                                    Actualiza los datos necesarios del postulante y presiona el botón inferior para guardar los cambios en el sistema institucional.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- I. DATOS PERSONALES --}}
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header"><h3 class="card-title font-weight-bold text-primary"><i class="fas fa-address-card mr-1"></i> I. Datos Personales</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="nombre" class="font-weight-bold">Nombre: <span class="text-danger">*</span></label><input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre', $postulante->nombre) }}" placeholder="Ej.: María Fernanda" required></div>
                        <div class="col-md-4 mb-3"><label for="primerapellido" class="font-weight-bold">Primer apellido: <span class="text-danger">*</span></label><input type="text" name="primerapellido" id="primerapellido" class="form-control" value="{{ old('primerapellido', $postulante->primerapellido) }}" placeholder="Ej.: Rodríguez" required></div>
                        <div class="col-md-4 mb-3"><label for="segundoapellido" class="font-weight-bold">Segundo apellido:</label><input type="text" name="segundoapellido" id="segundoapellido" class="form-control" value="{{ old('segundoapellido', $postulante->segundoapellido) }}" placeholder="Ej.: Vargas"></div>
                        <div class="col-md-4 mb-3"><label for="ci" class="font-weight-bold">CI / Documento de Identidad: <span class="text-danger">*</span></label><input type="text" name="ci" id="ci" class="form-control" value="{{ old('ci', $postulante->ci) }}" placeholder="Ej.: 12345678" required></div>
                        <div class="col-md-4 mb-3">
                            <label for="lugarexpedicion" class="font-weight-bold">Lugar expedición: <span class="text-danger">*</span></label>
                            <select name="lugarexpedicion" id="lugarexpedicion" class="form-control custom-select" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach(['CB','LP','SC','OR','PT','CH','TJ','BE','PD','OTRO'] as $departamento)
                                    <option value="{{ $departamento }}" @selected(old('lugarexpedicion', $postulante->lugarexpedicion) === $departamento)>{{ $departamento }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3"><label for="fechanacimiento" class="font-weight-bold">Fecha de nacimiento: <span class="text-danger">*</span></label><input type="date" name="fechanacimiento" id="fechanacimiento" class="form-control" value="{{ old('fechanacimiento', optional($postulante->fechanacimiento)->format('Y-m-d')) }}" required></div>
                        <div class="col-md-4 mb-3"><label for="lugarnacimiento" class="font-weight-bold">Lugar de nacimiento: <span class="text-danger">*</span></label><input type="text" name="lugarnacimiento" id="lugarnacimiento" class="form-control" value="{{ old('lugarnacimiento', $postulante->lugarnacimiento) }}" placeholder="Ej.: Sacaba, Cochabamba" required></div>
                        <div class="col-md-4 mb-3"><label for="nacionalidad" class="font-weight-bold">Nacionalidad: <span class="text-danger">*</span></label><input type="text" name="nacionalidad" id="nacionalidad" class="form-control" value="{{ old('nacionalidad', $postulante->nacionalidad) }}" placeholder="Ej.: Boliviana" required></div>
                        <div class="col-md-4 mb-3">
                            <label for="sexo" class="font-weight-bold">Sexo: <span class="text-danger">*</span></label>
                            <select name="sexo" id="sexo" class="form-control custom-select" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="M" @selected(old('sexo', $postulante->sexo) === 'M')>Masculino</option>
                                <option value="F" @selected(old('sexo', $postulante->sexo) === 'F')>Femenino</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- II. DATOS DE CONTACTO --}}
            <div class="card card-outline card-info shadow-sm mb-4">
                <div class="card-header"><h3 class="card-title font-weight-bold text-info"><i class="fas fa-phone mr-1"></i> II. Datos de Contacto</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="direccion" class="font-weight-bold">Dirección: <span class="text-danger">*</span></label><input type="text" name="direccion" id="direccion" class="form-control" value="{{ old('direccion', $postulante->direccion) }}" placeholder="Calle, número y zona" required></div>
                        <div class="col-md-3 mb-3"><label for="telefono" class="font-weight-bold">Teléfono:</label><input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono', $postulante->telefono) }}" placeholder="Ej.: 4251234"></div>
                        <div class="col-md-3 mb-3"><label for="celular" class="font-weight-bold">Celular: <span class="text-danger">*</span></label><input type="text" name="celular" id="celular" class="form-control" value="{{ old('celular', $postulante->celular) }}" placeholder="Ej.: 70012345" required></div>
                        <div class="col-md-12 mb-3"><label for="email" class="font-weight-bold">Email: <span class="text-danger">*</span></label><input type="email" name="email" id="email" class="form-control" value="{{ old('email', $postulante->email) }}" placeholder="nombre@correo.com" required></div>
                        <div class="col-md-4 mb-3"><label for="nombrecontacto" class="font-weight-bold">Nombre de contacto: <span class="text-danger">*</span></label><input type="text" name="nombrecontacto" id="nombrecontacto" class="form-control" value="{{ old('nombrecontacto', $postulante->nombrecontacto) }}" placeholder="Nombre completo" required></div>
                        <div class="col-md-4 mb-3"><label for="celularcontacto" class="font-weight-bold">Celular de contacto: <span class="text-danger">*</span></label><input type="text" name="celularcontacto" id="celularcontacto" class="form-control" value="{{ old('celularcontacto', $postulante->celularcontacto) }}" placeholder="Ej.: 70012345" required></div>
                        <div class="col-md-4 mb-3"><label for="parentescocontacto" class="font-weight-bold">Parentesco contacto: <span class="text-danger">*</span></label><input type="text" name="parentescocontacto" id="parentescocontacto" class="form-control" value="{{ old('parentescocontacto', $postulante->parentescocontacto) }}" placeholder="Ej.: Madre" required></div>
                    </div>
                </div>
            </div>

            {{-- III. DATOS DE EGRESO --}}
            <div class="card card-outline card-info shadow-sm mb-4">
                <div class="card-header"><h3 class="card-title font-weight-bold text-info"><i class="fas fa-school mr-1"></i> III. Datos de Egreso</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="nombrecolegio" class="font-weight-bold">Nombre colegio: <span class="text-danger">*</span></label><input type="text" name="nombrecolegio" id="nombrecolegio" class="form-control" value="{{ old('nombrecolegio', $postulante->nombrecolegio) }}" placeholder="Nombre oficial de la unidad educativa" required></div>
                        <div class="col-md-3 mb-3">
                            <label for="caractercolegio" class="font-weight-bold">Carácter colegio: <span class="text-danger">*</span></label>
                            <select name="caractercolegio" id="caractercolegio" class="form-control custom-select" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach(['Fiscal','Particular','Convenio'] as $caracter)
                                    <option value="{{ $caracter }}" @selected(old('caractercolegio', $postulante->caractercolegio) === $caracter)>{{ $caracter }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3"><label for="egresocolegio" class="font-weight-bold">Egreso colegio: <span class="text-danger">*</span></label><input type="number" name="egresocolegio" id="egresocolegio" class="form-control" value="{{ old('egresocolegio', $postulante->egresocolegio) }}" min="1900" max="2100" placeholder="Ej.: 2024" required></div>
                    </div>
                </div>
            </div>

            {{-- IV. DATOS DE ADMISIÓN --}}
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header"><h3 class="card-title font-weight-bold text-success"><i class="fas fa-file-alt mr-1"></i> IV. Datos de Admisión</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="gestion_display" class="font-weight-bold">Gestión académica activa:</label>
                            <input type="text" id="gestion_display" class="form-control bg-light" value="{{ $gestionActiva->gestion }} - {{ $gestionActiva->periodo }}" readonly>
                            <input type="hidden" name="id_gestionacademica" value="{{ $gestionActiva->id_gestionacademica }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="id_carrera" class="font-weight-bold">Carrera a postular: <span class="text-danger">*</span></label>
                            <select name="id_carrera" id="id_carrera" class="form-control custom-select" required>
                                <option value="">-- Seleccionar carrera --</option>
                                @foreach($carreras ?? [] as $carrera)
                                    @php
                                        $resoluciones = $carrera->resolucionesMinisteriales();
                                    @endphp
                                    <option value="{{ $carrera->id_carrera }}" @selected(old('id_carrera', $postulante->id_carrera) == $carrera->id_carrera)>
                                        {{ $carrera->nombre }}
                                        @if($resoluciones)
                                            · R.M. {{ implode(' / ', $resoluciones) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="diplomabachiller" class="font-weight-bold">¿Cuenta con Diploma de Bachiller?: <span class="text-danger">*</span></label>
                            <select name="diplomabachiller" id="diplomabachiller" class="form-control custom-select" required>
                                <option value="0" @selected(old('diplomabachiller', $postulante->diplomabachiller) == 0)>No</option>
                                <option value="1" @selected(old('diplomabachiller', $postulante->diplomabachiller) == 1)>Sí</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="deposito" class="font-weight-bold">Nº de Comprobante / Voucher de Depósito Bancario: <span class="text-danger">*</span></label>
                            <input type="text" name="deposito" id="deposito" class="form-control border-warning" value="{{ old('deposito', $postulante->deposito) }}" placeholder="Ej: 8493021" required>
                            <small class="text-danger font-weight-bold mt-1 d-block">
                                <i class="fas fa-exclamation-circle mr-1"></i> Si reemplazas el comprobante, debe ser único en la gestión activa y no estar usado en otra postulación.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- V. DOCUMENTACIÓN DIGITAL REQUERIDA --}}
            <div class="card card-outline card-warning shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-warning">
                        <i class="fas fa-folder-open mr-1"></i> V. Documentación Digital Requerida (Opcional actualizar)
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Solo se aceptan imágenes JPG o PNG. Cada reemplazo se comprime antes de enviarse; la fotografía admite 150 KB y cada documento 350 KB.
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="path_ci_frontal" class="font-weight-bold">Carnet de identidad, anverso:</label>
                            <div class="custom-file">
                                <input type="file" name="path_ci_frontal" id="path_ci_frontal" class="custom-file-input" accept="image/jpeg,image/png">
                                <label class="custom-file-label" for="path_ci_frontal" data-browse="Elegir">Seleccionar nueva imagen...</label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                @if($postulante->path_ci_frontal) <span class="text-success"><i class="fas fa-check-circle"></i> Imagen cargada. Selecciona otra solo para reemplazarla.</span> @else Sin imagen previa @endif · JPG o PNG, máximo 350 KB comprimida
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="path_ci_reverso" class="font-weight-bold">Carnet de identidad, reverso:</label>
                            <div class="custom-file">
                                <input type="file" name="path_ci_reverso" id="path_ci_reverso" class="custom-file-input" accept="image/jpeg,image/png">
                                <label class="custom-file-label" for="path_ci_reverso" data-browse="Elegir">Seleccionar nueva imagen...</label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                @if($postulante->path_ci_reverso) <span class="text-success"><i class="fas fa-check-circle"></i> Imagen cargada. Selecciona otra solo para reemplazarla.</span> @else Sin imagen previa @endif · JPG o PNG, máximo 350 KB comprimida
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="path_nacimiento" class="font-weight-bold">Certificado de Nacimiento:</label>
                            <div class="custom-file">
                                <input type="file" name="path_nacimiento" id="path_nacimiento" class="custom-file-input" accept="image/jpeg,image/png">
                                <label class="custom-file-label" for="path_nacimiento" data-browse="Elegir">Seleccionar nueva imagen...</label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                @if($postulante->path_nacimiento) <span class="text-success"><i class="fas fa-check-circle"></i> Imagen cargada. Selecciona otra solo para reemplazarla.</span> @else Sin imagen previa @endif · JPG o PNG, máximo 350 KB comprimida
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="path_diploma" class="font-weight-bold">Título / Diploma de Bachiller:</label>
                            <div class="custom-file">
                                <input type="file" name="path_diploma" id="path_diploma" class="custom-file-input" accept="image/jpeg,image/png">
                                <label class="custom-file-label" for="path_diploma" data-browse="Elegir">Seleccionar nueva imagen...</label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                @if($postulante->path_diploma) <span class="text-success"><i class="fas fa-check-circle"></i> Imagen cargada. Selecciona otra solo para reemplazarla.</span> @else Sin imagen previa @endif · JPG o PNG, máximo 350 KB comprimida
                            </small>
                        </div>

                    </div>
                </div>
                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold mr-2 shadow-sm">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar Formulario
                    </button>
                    <a href="{{ route('postulantes.bienvenida', ['ci_consulta' => $postulante->ci]) }}" class="btn btn-secondary font-weight-bold shadow-sm">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                </div>
            </div>

        </form>

    </main>
</div>
<link rel="stylesheet" href="{{ asset('css/postulantes/formularios.css') }}">
<link rel="stylesheet" href="{{ asset('css/postulantes/edicion.css') }}">

<script>
    window.postulanteValidationErrors = @json($errors->messages());
</script>
@include('partials.sweetalert-assets')
<script src="{{ asset('js/postulantes/postulante-form.js') }}"></script>