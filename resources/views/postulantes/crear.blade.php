@vite(['resources/sass/app.scss', 'resources/js/app.js'])

@php($tutorialVideoUrl = $tutorialVideoUrl ?? null)

<div class="itsa-postulante-public">

    {{-- =========================================================
         HEADER INSTITUCIONAL
    ========================================================== --}}
    <header class="public-hero py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('postulantes.bienvenida') }}" aria-label="Ir al inicio de admisiones"
                        class="d-inline-flex align-items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo ITSA" class="postulantes-logo postulantes-logo-formulario">
                    </a>

                    <div class="d-flex flex-column text-white">
                        <span class="fw-bold fs-6 text-uppercase institution-name">
                            Instituto Tecnológico Sacaba
                        </span>

                        <small class="text-white-50">
                            Admisiones y Registro de Postulantes
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">

                    <button type="button" class="btn btn-sm btn-outline-light fw-bold px-3 rounded-3"
                        id="iniciarTutorial">
                        <i class="fas fa-compass me-1"></i>
                        Ver guía
                    </button>

                    <a href="{{ route('postulantes.bienvenida') }}"
                        class="btn btn-sm btn-light fw-bold text-dark px-3 rounded-3">
                        <i class="fas fa-arrow-left me-1"></i>
                        Volver al Inicio
                    </a>

                </div>
            </div>
        </div>
    </header>


    {{-- =========================================================
         CONTENEDOR PRINCIPAL
    ========================================================== --}}
    <main class="container py-4 py-lg-5">

        {{-- =====================================================
             ALERTAS
        ====================================================== --}}
        @if (session('success'))
            <div class="alert alert-success shadow-sm alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-1"></i>
                {{ session('success') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger shadow-sm alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-1"></i>
                {{ session('error') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger shadow-sm alert-dismissible fade show" role="alert">

                <strong>
                    <i class="fas fa-times-circle me-1"></i>
                    Revisa los datos ingresados:
                </strong>

                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            </div>
        @endif


        {{-- =====================================================
             AVISO SEGUNDA CARRERA
        ====================================================== --}}
        @if (isset($postulanteBase) && $postulanteBase)
            <div class="alert alert-info shadow-sm border-left-info mb-4" role="alert">

                <h5 class="fw-bold mb-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Postulación a una Segunda Carrera
                </h5>

                Hola
                <strong>
                    {{ $postulanteBase->nombre }}
                    {{ $postulanteBase->primerapellido }}
                </strong>,

                tus datos personales y documentos principales ya se encuentran
                precargados.

                <br>

                Para completar esta nueva postulación:
                <strong>selecciona la nueva carrera</strong> y registra el número de comprobante correspondiente.

            </div>
        @endif


        {{-- =====================================================
             ENCABEZADO + STEPPER
        ====================================================== --}}
        <div class="card-custom p-4 mb-4">

            <div class="row align-items-center">

                <div class="col-lg-5 mb-4 mb-lg-0">

                    <div class="d-flex align-items-center gap-2 mb-2">

                        <h2 class="h3 fw-bold text-dark mb-0">
                            Formulario de Postulación
                        </h2>

                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            {{ $gestionActiva->gestion ?? 'I/2026' }}
                            {{ $gestionActiva->periodo ?? 'Vigente' }}
                        </span>

                    </div>

                    <p class="text-muted mb-0 small">
                        Completa tu información personal y académica para continuar
                        con el proceso de admisión oficial.
                    </p>

                </div>


                {{-- STEPPER --}}
                <div class="col-lg-7">

                    <div class="stepper-wrapper">

                        <div class="stepper-line"></div>

                        <div class="d-flex justify-content-between">

                            <div class="step-item active" id="stepIndicator1" data-step="1">
                                <div class="step-circle shadow-sm">
                                    1
                                </div>

                                <div class="step-label">
                                    Datos personales
                                </div>
                            </div>


                            <div class="step-item" id="stepIndicator2" data-step="2">
                                <div class="step-circle shadow-sm">
                                    2
                                </div>

                                <div class="step-label">
                                    Contacto e ingreso
                                </div>
                            </div>


                            <div class="step-item" id="stepIndicator3" data-step="3">
                                <div class="step-circle shadow-sm">
                                    3
                                </div>

                                <div class="step-label">
                                    Admisión
                                </div>
                            </div>


                            <div class="step-item" id="stepIndicator4" data-step="4">
                                <div class="step-circle shadow-sm">
                                    4
                                </div>

                                <div class="step-label">
                                    Documentación
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             FORMULARIO REAL
        ====================================================== --}}
        <form action="{{ route('postulantes.store') }}" method="POST" enctype="multipart/form-data"
            id="formRegistrarPostulante"
            data-carreras-inscritas="{{ json_encode($carrerasInscritas ?? ($postulanteBase->carrerasInscritasIds ?? [])) }}">

            @csrf


            {{-- =================================================
                 PASO 1
                 FOTO + DATOS PERSONALES
            ================================================== --}}
            <div class="step-pane" id="paneStep1">

                <div class="row g-4">


                    {{-- FOTO --}}
                    <div class="col-lg-4">

                        <div class="card-custom h-100" id="step-fotografia">

                            <div class="card-custom-header">

                                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
                                    <i class="fas fa-camera text-success me-2"></i>
                                    Fotografía del postulante
                                </h5>

                            </div>


                            <div class="p-4 d-flex flex-column align-items-center justify-content-center text-center">

                                <div class="photo-preview-box mb-3" id="photoPreview">

                                    @if (isset($postulanteBase) && $postulanteBase->foto)
                                        <img id="imgPreviewPhoto"
                                            src="{{ asset('storage/' . $postulanteBase->foto) }}"
                                            alt="Foto del postulante" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div class="avatar-icon-circle" id="photoPlaceholder">
                                            <i class="fas fa-user"></i>
                                        </div>

                                        <span class="text-muted small fw-semibold">
                                            Fotografía 4x4 o carnet
                                        </span>

                                        <span class="text-secondary small">
                                            Vista previa
                                        </span>

                                        <img id="imgPreviewPhoto" src="" alt="Foto"
                                            class="d-none w-100 h-100 object-fit-cover">
                                    @endif

                                </div>


                                <div class="w-100">

                                    <label for="foto" id="fotografiaGuia"
                                        class="btn btn-itsa-primary w-100 mb-2 shadow-sm">
                                        <i class="fas fa-upload me-1"></i>

                                        {{ isset($postulanteBase) ? 'Cambiar fotografía (Opcional)' : 'Seleccionar fotografía *' }}
                                    </label>


                                    <input type="file" name="fotografia" id="foto" class="d-none"
                                        accept="image/jpeg,image/png" data-name="Fotografía del postulante"
                                        @if (!isset($postulanteBase)) required @endif>


                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger w-100 fw-bold d-none mb-2"
                                        id="btnRemovePhoto">
                                        <i class="fas fa-trash-alt me-1"></i>
                                        Eliminar foto
                                    </button>

                                </div>


                                <div class="w-100 bg-light rounded-3 p-3 border mt-2 text-start">

                                    <small class="text-muted d-block leading-normal">

                                        <i class="fas fa-info-circle text-success me-1"></i>

                                        Imagen original hasta

                                        <br>

                                        Se comprime y almacena a máximo
                                        <strong>150 KB</strong>.

                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- DATOS PERSONALES --}}
                    <div class="col-lg-8">

                        <div class="card-custom h-100" id="step-datos-personales">

                            <div class="card-custom-header d-flex justify-content-between align-items-center">

                                <div class="d-flex align-items-center">

                                    <span class="step-number-badge">
                                        01
                                    </span>

                                    <h5 class="fw-bold text-dark mb-0">
                                        Datos Personales
                                    </h5>

                                </div>

                                <small class="text-muted">
                                    Campos con
                                    (<span class="text-danger">*</span>)
                                    obligatorios
                                </small>

                            </div>


                            <div class="p-4">

                                <div class="row g-3">

                                    {{-- NOMBRE --}}
                                    <div class="col-md-4">

                                        <label for="nombre" class="form-label">
                                            Nombre:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" name="nombre" id="nombre" class="form-control"
                                            value="{{ old('nombre', $postulanteBase->nombre ?? '') }}" required
                                            placeholder="Nombres">

                                    </div>


                                    {{-- PRIMER APELLIDO --}}
                                    <div class="col-md-4">

                                        <label for="primerapellido" class="form-label">
                                            Primer apellido:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" name="primerapellido" id="primerapellido"
                                            class="form-control"
                                            value="{{ old('primerapellido', $postulanteBase->primerapellido ?? '') }}"
                                            required placeholder="Primer apellido">

                                    </div>


                                    {{-- SEGUNDO APELLIDO --}}
                                    <div class="col-md-4">

                                        <label for="segundoapellido" class="form-label">
                                            Segundo apellido:
                                        </label>

                                        <input type="text" name="segundoapellido" id="segundoapellido"
                                            class="form-control"
                                            value="{{ old('segundoapellido', $postulanteBase->segundoapellido ?? '') }}"
                                            placeholder="Segundo apellido">

                                    </div>


                                    {{-- CI --}}
                                    <div class="col-md-4">

                                        <label for="ci" class="form-label">
                                            C.I. / Documento:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" name="ci" id="ci" class="form-control"
                                            value="{{ old('ci', $ciBuscado ?? ($postulanteBase->ci ?? '')) }}"
                                            required placeholder="Número de carnet"
                                            @if (isset($ciBuscado) || isset($postulanteBase)) readonly @endif>

                                    </div>


                                    {{-- LUGAR EXPEDICIÓN --}}
                                    <div class="col-md-4">

                                        <label for="lugarexpedicion" class="form-label">
                                            Lugar de expedición:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <select name="lugarexpedicion" id="lugarexpedicion" class="form-select"
                                            required>

                                            <option value="">
                                                -- Seleccionar --
                                            </option>

                                            @foreach ([
        'CB' => 'Cochabamba (CB)',
        'LP' => 'La Paz (LP)',
        'SC' => 'Santa Cruz (SC)',
        'OR' => 'Oruro (OR)',
        'PT' => 'Potosí (PT)',
        'CH' => 'Chuquisaca (CH)',
        'TJ' => 'Tarija (TJ)',
        'BE' => 'Beni (BE)',
        'PD' => 'Pando (PD)',
        'OTRO' => 'Otro',
    ] as $codigo => $departamento)
                                                <option value="{{ $codigo }}" @selected(old('lugarexpedicion', $postulanteBase->lugarexpedicion ?? '') === $codigo)>
                                                    {{ $departamento }}
                                                </option>
                                            @endforeach

                                        </select>

                                    </div>


                                    {{-- FECHA NACIMIENTO --}}
                                    <div class="col-md-4">

                                        <label for="fechanacimiento" class="form-label">
                                            Fecha de nacimiento:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="date" name="fechanacimiento" id="fechanacimiento"
                                            class="form-control"
                                            value="{{ old('fechanacimiento', optional($postulanteBase?->fechanacimiento)->format('Y-m-d') ?? '') }}"
                                            required>

                                    </div>


                                    {{-- LUGAR NACIMIENTO --}}
                                    <div class="col-md-4">

                                        <label for="lugarnacimiento" class="form-label">
                                            Lugar de nacimiento:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" name="lugarnacimiento" id="lugarnacimiento"
                                            class="form-control"
                                            value="{{ old('lugarnacimiento', $postulanteBase->lugarnacimiento ?? '') }}"
                                            required placeholder="Ciudad / Localidad">

                                    </div>


                                    {{-- NACIONALIDAD --}}
                                    <div class="col-md-4">

                                        <label for="nacionalidad" class="form-label">
                                            Nacionalidad:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <input type="text" name="nacionalidad" id="nacionalidad"
                                            class="form-control"
                                            value="{{ old('nacionalidad', $postulanteBase->nacionalidad ?? 'Boliviana') }}"
                                            required>

                                    </div>


                                    {{-- SEXO --}}
                                    <div class="col-md-4">

                                        <label for="sexo" class="form-label">
                                            Sexo:
                                            <span class="text-danger">*</span>
                                        </label>

                                        <select name="sexo" id="sexo" class="form-select" required>

                                            <option value="">
                                                -- Seleccionar --
                                            </option>

                                            <option value="M" @selected(old('sexo', $postulanteBase->sexo ?? '') === 'M')>
                                                Masculino
                                            </option>

                                            <option value="F" @selected(old('sexo', $postulanteBase->sexo ?? '') === 'F')>
                                                Femenino
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>


                            <div class="bg-light p-3 px-4 border-top d-flex justify-content-end">

                                <button type="button" class="btn btn-itsa-primary shadow-sm"
                                    onclick="cambiarPaso(2)">
                                    Siguiente paso
                                    <i class="fas fa-arrow-right ms-1"></i>
                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 PASO 2
                 CONTACTO + EGRESO
            ================================================== --}}
            <div class="step-pane d-none" id="paneStep2">

                {{-- CONTACTO --}}
                <div class="card-custom mb-4" id="step-contacto">

                    <div class="card-custom-header">

                        <div class="d-flex align-items-center">

                            <span class="step-number-badge">
                                02
                            </span>

                            <h5 class="fw-bold text-dark mb-0">
                                Datos de Contacto y Emergencia
                            </h5>

                        </div>

                    </div>


                    <div class="p-4">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label for="direccion" class="form-label">
                                    Dirección de residencia:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="direccion" id="direccion" class="form-control"
                                    value="{{ old('direccion', $postulanteBase->direccion ?? '') }}" required
                                    placeholder="Zona, calle, número de domicilio">

                            </div>


                            <div class="col-md-3">

                                <label for="telefono" class="form-label">
                                    Teléfono fijo:
                                </label>

                                <input type="text" name="telefono" id="telefono" class="form-control"
                                    value="{{ old('telefono', $postulanteBase->telefono ?? '') }}"
                                    placeholder="Ej: 4412345">

                            </div>


                            <div class="col-md-3">

                                <label for="celular" class="form-label">
                                    Celular personal:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="celular" id="celular" class="form-control"
                                    value="{{ old('celular', $postulanteBase->celular ?? '') }}" required
                                    placeholder="Ej: 71234567">

                            </div>


                            <div class="col-12">

                                <label for="email" class="form-label">
                                    Correo electrónico:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="email" name="email" id="email" class="form-control"
                                    value="{{ old('email', $postulanteBase->email ?? '') }}" required
                                    placeholder="estudiante@correo.com">

                            </div>


                            <div class="col-12">
                                <hr class="my-2">
                            </div>


                            <div class="col-md-4">

                                <label for="nombrecontacto" class="form-label">
                                    Nombre de contacto:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="nombrecontacto" id="nombrecontacto" class="form-control"
                                    value="{{ old('nombrecontacto', $postulanteBase->nombrecontacto ?? '') }}"
                                    required placeholder="Tutor o familiar cercano">

                            </div>


                            <div class="col-md-4">

                                <label for="celularcontacto" class="form-label">
                                    Celular del contacto:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="celularcontacto" id="celularcontacto"
                                    class="form-control"
                                    value="{{ old('celularcontacto', $postulanteBase->celularcontacto ?? '') }}"
                                    required placeholder="Ej: 79876543">

                            </div>


                            <div class="col-md-4">

                                <label for="parentescocontacto" class="form-label">
                                    Parentesco:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="parentescocontacto" id="parentescocontacto"
                                    class="form-control"
                                    value="{{ old('parentescocontacto', $postulanteBase->parentescocontacto ?? '') }}"
                                    required placeholder="Padre, Madre, Hermano/a...">

                            </div>

                        </div>

                    </div>

                </div>


                {{-- EGRESO --}}
                <div class="card-custom mb-4" id="step-egreso">

                    <div class="card-custom-header">

                        <div class="d-flex align-items-center">

                            <span class="step-number-badge">
                                02.B
                            </span>

                            <h5 class="fw-bold text-dark mb-0">
                                Datos de Egreso Escolar
                            </h5>

                        </div>

                    </div>


                    <div class="p-4">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label for="nombrecolegio" class="form-label">
                                    Nombre de Colegio / Unidad Educativa:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="nombrecolegio" id="nombrecolegio" class="form-control"
                                    value="{{ old('nombrecolegio', $postulanteBase->nombrecolegio ?? '') }}" required
                                    placeholder="Nombre de la institución">

                            </div>


                            <div class="col-md-3">

                                <label for="caractercolegio" class="form-label">
                                    Carácter del colegio:
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="caractercolegio" id="caractercolegio" class="form-select" required>

                                    <option value="">
                                        -- Seleccionar --
                                    </option>

                                    @foreach (['Fiscal', 'Particular', 'Convenio'] as $caracter)
                                        <option value="{{ $caracter }}" @selected(old('caractercolegio', $postulanteBase->caractercolegio ?? '') === $caracter)>
                                            {{ $caracter }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            <div class="col-md-3">

                                <label for="egresocolegio" class="form-label">
                                    Año de egreso:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="number" name="egresocolegio" id="egresocolegio" class="form-control"
                                    value="{{ old('egresocolegio', $postulanteBase->egresocolegio ?? '') }}"
                                    min="1900" max="2100" required placeholder="Ej: 2024">

                            </div>

                        </div>

                    </div>


                    <div class="bg-light p-3 px-4 border-top d-flex justify-content-between">

                        <button type="button" class="btn btn-outline-secondary fw-bold" onclick="cambiarPaso(1)">
                            <i class="fas fa-arrow-left me-1"></i>
                            Anterior
                        </button>


                        <button type="button" class="btn btn-itsa-primary shadow-sm" onclick="cambiarPaso(3)">
                            Siguiente paso
                            <i class="fas fa-arrow-right ms-1"></i>
                        </button>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 PASO 3
                 ADMISIÓN
            ================================================== --}}
            <div class="step-pane d-none" id="paneStep3">

                <div class="card-custom mb-4" id="step-admision">

                    <div class="card-custom-header">

                        <div class="d-flex align-items-center">

                            <span class="step-number-badge">
                                03
                            </span>

                            <h5 class="fw-bold text-dark mb-0">
                                Información de Carrera y Admisión
                            </h5>

                        </div>

                    </div>


                    <div class="p-4">

                        <div class="row g-3">


                            {{-- GESTIÓN --}}
                            <div class="col-md-4">

                                <label for="gestion_display" class="form-label">
                                    Gestión académica:
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" id="gestion_display" class="form-control bg-light fw-bold"
                                    value="{{ $gestionActiva->gestion ?? 'I/2026' }} - {{ $gestionActiva->periodo ?? 'Convocatoria Vigente' }}"
                                    readonly>

                                <input type="hidden" name="id_gestionacademica"
                                    value="{{ $gestionActiva->id_gestionacademica ?? 1 }}">

                            </div>


                            {{-- CARRERA --}}
                            <div class="col-md-4">

                                <label for="id_carrera" class="form-label">
                                    Carrera a postular:
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="id_carrera" id="id_carrera" class="form-select fw-semibold"
                                    data-name="Carrera a postular" required>

                                    <option value="">
                                        -- Seleccionar carrera técnica --
                                    </option>

                                    @foreach ($carreras ?? [] as $carrera)
                                        <option value="{{ $carrera->id_carrera }}" @selected(old('id_carrera') == $carrera->id_carrera)>
                                            {{ $carrera->nombre }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- DIPLOMA --}}
                            <div class="col-md-4">

                                <label for="diplomabachiller" class="form-label">
                                    ¿Cuenta con Diploma de Bachiller?:
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="diplomabachiller" id="diplomabachiller" class="form-select" required>

                                    <option value="0" @selected(old('diplomabachiller', $postulanteBase->diplomabachiller ?? '0') == '0')>
                                        No / En trámite
                                    </option>

                                    <option value="1" @selected(old('diplomabachiller', $postulanteBase->diplomabachiller ?? '') == '1')>
                                        Sí, cuento con el documento
                                    </option>

                                </select>

                            </div>


                            {{-- VOUCHER --}}
                            <div class="col-12 mt-4">

                                <div class="p-3 bg-light rounded-3 border border-warning">

                                    <label for="deposito" class="form-label fw-bold text-dark mb-1">
                                        <i class="fas fa-receipt text-warning me-1"></i>

                                        Nº de Comprobante / Voucher de Depósito Bancario:

                                        <span class="text-danger">*</span>
                                    </label>

                                    <input type="text" name="deposito" id="deposito"
                                        class="form-control fw-bold" value="{{ old('deposito') }}"
                                        placeholder="Ej: 8493021" required>

                                    <small class="text-danger fw-semibold d-block mt-2">

                                        <i class="fas fa-exclamation-triangle me-1"></i>

                                        Este comprobante debe ser único e irrepetible.
                                        No uses el mismo voucher para otra postulación.

                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="bg-light p-3 px-4 border-top d-flex justify-content-between">

                        <button type="button" class="btn btn-outline-secondary fw-bold" onclick="cambiarPaso(2)">
                            <i class="fas fa-arrow-left me-1"></i>
                            Anterior
                        </button>


                        <button type="button" class="btn btn-itsa-primary shadow-sm" onclick="cambiarPaso(4)">
                            Siguiente paso
                            <i class="fas fa-arrow-right ms-1"></i>
                        </button>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 PASO 4
                 DOCUMENTACIÓN
            ================================================== --}}
            <div class="step-pane d-none" id="paneStep4">

                <div class="card-custom mb-4" id="step-documentos">

                    <div class="card-custom-header">

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

                            <div class="d-flex align-items-center">

                                <span class="step-number-badge">
                                    04
                                </span>

                                <div>

                                    <h5 class="fw-bold text-dark mb-0">
                                        Documentación Digital Requerida
                                    </h5>

                                    <small class="text-muted">
                                        Solo imágenes JPG o PNG; máximo 350 KB por documento
                                    </small>

                                </div>

                            </div>

                            <span class="badge bg-secondary-subtle text-secondary border">
                                4 Documentos
                            </span>

                        </div>

                    </div>


                    <div class="p-4">

                        <div class="row g-4">


                            {{-- CI FRONTAL --}}
                            <div class="col-md-6">

                                <div class="doc-upload-box">

                                    <div class="d-flex justify-content-between align-items-center mb-2">

                                        <label for="path_ci_frontal" class="form-label mb-0">
                                            1. Cédula de Identidad (Anverso)

                                            @if (!isset($postulanteBase))
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        <span class="badge bg-success-subtle text-success">
                                            Obligatorio
                                        </span>

                                    </div>


                                    <div class="custom-file">

                                        <input type="file" name="path_ci_frontal" id="path_ci_frontal"
                                            class="custom-file-input" accept="image/jpeg,image/png"
                                            data-name="Carnet de identidad, anverso"
                                            @if (empty($postulanteBase?->path_ci_frontal)) required @endif>

                                        <label class="custom-file-label" for="path_ci_frontal">
                                            Seleccionar imagen...
                                        </label>

                                    </div>


                                    <small class="text-muted d-block mt-2">

                                        @if ($postulanteBase?->path_ci_frontal)
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                                Imagen registrada. Selecciona otra solo para reemplazarla.
                                            </span>
                                        @else
                                            <i class="fas fa-info-circle me-1"></i>
                                            Fotografía nítida frontal · Máx. 350 KB
                                        @endif

                                    </small>


                                    <div id="preview_ci_frontal" class="document-preview mt-3 d-none">

                                        <img src="" class="img-fluid rounded d-none"
                                            alt="Vista previa anverso CI">

                                        <div class="d-flex align-items-center justify-content-between mt-2">

                                            <span class="file-name text-truncate fw-bold"></span>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-remove-doc"
                                                data-target="path_ci_frontal">
                                                <i class="fas fa-trash-alt me-1"></i>
                                                Eliminar
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            {{-- CI REVERSO --}}
                            <div class="col-md-6">

                                <div class="doc-upload-box">

                                    <div class="d-flex justify-content-between align-items-center mb-2">

                                        <label for="path_ci_reverso" class="form-label mb-0">
                                            2. Cédula de Identidad (Reverso)

                                            @if (!isset($postulanteBase))
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        <span class="badge bg-success-subtle text-success">
                                            Obligatorio
                                        </span>

                                    </div>


                                    <div class="custom-file">

                                        <input type="file" name="path_ci_reverso" id="path_ci_reverso"
                                            class="custom-file-input" accept="image/jpeg,image/png"
                                            data-name="Carnet de identidad, reverso"
                                            @if (empty($postulanteBase?->path_ci_reverso)) required @endif>

                                        <label class="custom-file-label" for="path_ci_reverso">
                                            Seleccionar imagen...
                                        </label>

                                    </div>


                                    <small class="text-muted d-block mt-2">

                                        @if ($postulanteBase?->path_ci_reverso)
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                                Imagen registrada. Selecciona otra solo para reemplazarla.
                                            </span>
                                        @else
                                            <i class="fas fa-info-circle me-1"></i>
                                            Fotografía posterior nítida · Máx. 350 KB
                                        @endif

                                    </small>


                                    <div id="preview_ci_reverso" class="document-preview mt-3 d-none">

                                        <img src="" class="img-fluid rounded d-none"
                                            alt="Vista previa reverso CI">

                                        <div class="d-flex align-items-center justify-content-between mt-2">

                                            <span class="file-name text-truncate fw-bold"></span>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-remove-doc"
                                                data-target="path_ci_reverso">
                                                <i class="fas fa-trash-alt me-1"></i>
                                                Eliminar
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            {{-- NACIMIENTO --}}
                            <div class="col-md-6">

                                <div class="doc-upload-box">

                                    <div class="d-flex justify-content-between align-items-center mb-2">

                                        <label for="path_nacimiento" class="form-label mb-0">
                                            3. Certificado de Nacimiento

                                            @if (!isset($postulanteBase))
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        <span class="badge bg-success-subtle text-success">
                                            Obligatorio
                                        </span>

                                    </div>


                                    <div class="custom-file">

                                        <input type="file" name="path_nacimiento" id="path_nacimiento"
                                            class="custom-file-input" accept="image/jpeg,image/png"
                                            data-name="Certificado de Nacimiento"
                                            @if (empty($postulanteBase?->path_nacimiento)) required @endif>

                                        <label class="custom-file-label" for="path_nacimiento">
                                            Seleccionar imagen...
                                        </label>

                                    </div>


                                    <small class="text-muted d-block mt-2">

                                        @if ($postulanteBase?->path_nacimiento)
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                                Imagen registrada. Selecciona otra solo para reemplazarla.
                                            </span>
                                        @else
                                            <i class="fas fa-info-circle me-1"></i>
                                            Imagen legible del certificado · Máx. 350 KB
                                        @endif

                                    </small>


                                    <div id="preview_nacimiento" class="document-preview mt-3 d-none">

                                        <img src="" class="img-fluid rounded d-none"
                                            alt="Vista previa Nacimiento">

                                        <div class="d-flex align-items-center justify-content-between mt-2">

                                            <span class="file-name text-truncate fw-bold"></span>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-remove-doc"
                                                data-target="path_nacimiento">
                                                <i class="fas fa-trash-alt me-1"></i>
                                                Eliminar
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            {{-- DIPLOMA --}}
                            <div class="col-md-6">

                                <div class="doc-upload-box">

                                    <div class="d-flex justify-content-between align-items-center mb-2">

                                        <label for="path_diploma" class="form-label mb-0">
                                            4. Diploma de Bachiller

                                            @if (!isset($postulanteBase))
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        <span class="badge bg-success-subtle text-success">
                                            Obligatorio
                                        </span>

                                    </div>


                                    <div class="custom-file">

                                        <input type="file" name="path_diploma" id="path_diploma"
                                            class="custom-file-input" accept="image/jpeg,image/png"
                                            data-name="Título / Diploma de Bachiller"
                                            @if (empty($postulanteBase?->path_diploma)) required @endif>

                                        <label class="custom-file-label" for="path_diploma">
                                            Seleccionar imagen...
                                        </label>

                                    </div>


                                    <small class="text-muted d-block mt-2">

                                        @if ($postulanteBase?->path_diploma)
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                                Imagen registrada. Selecciona otra solo para reemplazarla.
                                            </span>
                                        @else
                                            <i class="fas fa-info-circle me-1"></i>
                                            Imagen nítida del diploma · Máx. 350 KB
                                        @endif

                                    </small>


                                    <div id="preview_diploma" class="document-preview mt-3 d-none">

                                        <img src="" class="img-fluid rounded d-none"
                                            alt="Vista previa Diploma">

                                        <div class="d-flex align-items-center justify-content-between mt-2">

                                            <span class="file-name text-truncate fw-bold"></span>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-remove-doc"
                                                data-target="path_diploma">
                                                <i class="fas fa-trash-alt me-1"></i>
                                                Eliminar
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            </div>


                        </div>


                    </div>


                    {{-- ACCIONES --}}
                    <div class="bg-light p-3 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2"
                        id="step-acciones">

                        <button type="button" class="btn btn-outline-secondary fw-bold" onclick="cambiarPaso(3)">
                            <i class="fas fa-arrow-left me-1"></i>
                            Anterior
                        </button>


                        <div class="d-flex gap-2 flex-wrap">

                            <a href="{{ route('postulantes.bienvenida') }}"
                                class="btn btn-outline-danger fw-bold px-3">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </a>


                            <button type="submit" id="guardarPostulante" class="btn btn-itsa-primary shadow px-4">
                                <i class="fas fa-check-circle me-1"></i>
                                Guardar y Registrar Postulación
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </main>

</div>


{{-- =========================================================
     OVERLAY DEL TUTORIAL
========================================================== --}}
<div id="tutorial-overlay"></div>


{{-- =========================================================
     CAJA DEL TUTORIAL
========================================================== --}}
<div id="tutorialBox" class="card shadow-lg border-primary tutorial-box">

    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">

        <span id="tutorialStep" class="badge badge-light font-weight-bold text-primary">
            Paso 1
        </span>

        <button type="button" id="tutorialClose" class="close text-white" aria-label="Cerrar">
            &times;
        </button>

    </div>


    <div class="card-body p-3">

        <h5 id="tutorialTitle" class="font-weight-bold text-primary mb-2"></h5>

        <p id="tutorialText" class="card-text text-secondary small mb-3"></p>


        <div class="d-flex justify-content-between align-items-center pt-2 border-top">

            <button type="button" id="tutorialPrev" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Anterior
            </button>


            <button type="button" id="tutorialNext" class="btn btn-sm btn-primary">
                Siguiente
                <i class="fas fa-arrow-right ms-1"></i>
            </button>

        </div>

    </div>

</div>


<link rel="stylesheet" href="{{ asset('css/postulantes/formularios.css') }}">
<link rel="stylesheet" href="{{ asset('css/postulantes/registro.css') }}">


<script src="{{ asset('js/postulantes/registro-pasos.js') }}"></script>
<script src="{{ asset('js/postulantes/postulante-form.js') }}"></script>
