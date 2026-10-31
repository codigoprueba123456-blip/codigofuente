@extends('adminlte::page')

@section('title', 'Inscribir postulante')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">
                    <i class="fas fa-user-plus text-primary mr-2"></i> Inscripción de Estudiante (Nueva Postulación)
                </h1>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <h5 class="alert-heading font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Corrija los siguientes campos:</h5>
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('estudiante_nuevo_postulacion.store') }}" method="POST" id="formPostulanteNuevo">
                @csrf

                {{-- BLOQUE PRINCIPAL CONECTADO: BÚSQUEDA, CARRERAS Y VOUCHER --}}
                <div class="card card-outline card-primary shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-primary">
                            <i class="fas fa-search mr-1"></i> 1. Búsqueda de Postulante, Carrera y Voucher
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- 1. Barra de Búsqueda Flexible --}}
                            <div class="col-md-5 mb-3 position-relative">
                                <label for="postulante_search" class="font-weight-bold">Buscar Postulante (C.I. o Nombre): <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                                    </div>
                                    <input type="text" id="postulante_search"
                                        class="form-control"
                                        placeholder="Escribe C.I., Nombre o Apellido..."
                                        value="{{ old('postulante_search') }}" autocomplete="off">
                                    <div class="input-group-append">
                                        <button type="button" id="btnClearSearch" class="btn btn-outline-secondary" title="Limpiar búsqueda"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                {{-- Contenedor desplegable para resultados de búsqueda flexible --}}
                                <div id="customDropdownResults" class="dropdown-menu w-100 shadow" style="max-height: 250px; overflow-y: auto; display: none; position: absolute; z-index: 1050;">
                                </div>
                            </div>

                            <div class="col-md-7 mb-3">
                                <label class="font-weight-bold">Postulaciones pendientes de esta persona:</label>
                                <div id="postulaciones_container" class="border rounded p-3 bg-light">
                                    <span class="text-muted">Busque una persona para revisar sus carreras y comprobantes.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PREVISUALIZACIÓN DIRECTA DE ARCHIVOS --}}
                <div class="card card-outline card-info shadow-sm mb-4">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold text-info">
                            <i class="fas fa-file-image mr-1"></i> 2. Archivos y Documentos Adjuntos del Estudiante
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <span class="small font-weight-bold text-dark d-block mb-1">C.I. anverso</span>
                                <div id="preview_ci" class="border rounded p-2 bg-light shadow-sm" style="min-height: 110px; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted small font-italic">Sin archivo</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <span class="small font-weight-bold text-dark d-block mb-1">C.I. reverso</span>
                                <div id="preview_ci_reverso" class="border rounded p-2 bg-light shadow-sm" style="min-height: 110px; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted small font-italic">Sin archivo</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <span class="small font-weight-bold text-dark d-block mb-1">Certificado de Nacimiento</span>
                                <div id="preview_nac" class="border rounded p-2 bg-light shadow-sm" style="min-height: 110px; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted small font-italic">Sin archivo</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <span class="small font-weight-bold text-dark d-block mb-1">Diploma de Bachiller</span>
                                <div id="preview_dip" class="border rounded p-2 bg-light shadow-sm" style="min-height: 110px; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted small font-italic">Sin archivo</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DATOS PERSONALES --}}
                <div class="card card-outline card-secondary shadow-sm mb-4">
                    <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-address-card mr-1"></i> 3. Datos Personales</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Nombre: <span class="text-danger">*</span></label><input type="text" name="nombre" id="nombre" class="form-control" value="{{ old('nombre') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Primer apellido: <span class="text-danger">*</span></label><input type="text" name="primerapellido" id="primerapellido" class="form-control" value="{{ old('primerapellido') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Segundo apellido:</label><input type="text" name="segundoapellido" id="segundoapellido" class="form-control" value="{{ old('segundoapellido') }}"></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Carnet de identidad (CI): <span class="text-danger">*</span></label><input type="text" name="ci" id="ci" class="form-control" value="{{ old('ci') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Lugar expedición:</label>
                                <select name="lugarexpedicion" id="lugarexpedicion" class="form-control custom-select">
                                    <option value="">-- Seleccionar --</option>
                                    @foreach (['CB', 'LP', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD', 'OTRO'] as $dept)
                                        <option value="{{ $dept }}" {{ old('lugarexpedicion') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Fecha de nacimiento: <span class="text-danger">*</span></label><input type="date" name="fechanacimiento" id="fechanacimiento" class="form-control" value="{{ old('fechanacimiento') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Lugar de nacimiento: <span class="text-danger">*</span></label><input type="text" name="lugarnacimiento" id="lugarnacimiento" class="form-control" value="{{ old('lugarnacimiento') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Nacionalidad: <span class="text-danger">*</span></label><input type="text" name="nacionalidad" id="nacionalidad" class="form-control" value="{{ old('nacionalidad', 'Boliviana') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Sexo: <span class="text-danger">*</span></label>
                                <select name="sexo" id="sexo" class="form-control custom-select" required>
                                    <option value="">-- Seleccionar --</option>
                                    <option value="M" {{ old('sexo') == 'M' ? 'selected' : '' }}>Masculino</option>
                                    <option value="F" {{ old('sexo') == 'F' ? 'selected' : '' }}>Femenino</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3"><label class="font-weight-bold">Dirección: <span class="text-danger">*</span></label><input type="text" name="direccion" id="direccion" class="form-control" value="{{ old('direccion') }}" required></div>
                            <div class="col-md-3 mb-3"><label class="font-weight-bold">Teléfono:</label><input type="text" name="telefono" id="telefono" class="form-control" value="{{ old('telefono') }}"></div>
                            <div class="col-md-3 mb-3"><label class="font-weight-bold">Celular:</label><input type="text" name="celular" id="celular" class="form-control" value="{{ old('celular') }}"></div>
                            <div class="col-md-12 mb-3"><label class="font-weight-bold">Correo Electrónico (Email): <span class="text-danger">*</span></label><input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required></div>
                        </div>
                    </div>
                </div>

                {{-- CONTACTO Y COLEGIO --}}
                <div class="card card-outline card-secondary shadow-sm mb-4">
                    <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-phone mr-1"></i> 4. Contacto y Procedencia</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Nombre de contacto: <span class="text-danger">*</span></label><input type="text" name="nombrecontacto" id="nombrecontacto" class="form-control" value="{{ old('nombrecontacto') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Celular de contacto: <span class="text-danger">*</span></label><input type="text" name="celularcontacto" id="celularcontacto" class="form-control" value="{{ old('celularcontacto') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Parentesco contacto: <span class="text-danger">*</span></label><input type="text" name="parentescocontacto" id="parentescocontacto" class="form-control" value="{{ old('parentescocontacto') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Nombre colegio: <span class="text-danger">*</span></label><input type="text" name="nombrecolegio" id="nombrecolegio" class="form-control" value="{{ old('nombrecolegio') }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Carácter colegio: <span class="text-danger">*</span></label>
                                <select name="caractercolegio" id="caractercolegio" class="form-control custom-select" required>
                                    <option value="">-- Seleccionar --</option>
                                    <option value="Fiscal" {{ old('caractercolegio') == 'Fiscal' ? 'selected' : '' }}>Fiscal</option>
                                    <option value="Particular" {{ old('caractercolegio') == 'Particular' ? 'selected' : '' }}>Particular</option>
                                    <option value="Convenio" {{ old('caractercolegio') == 'Convenio' ? 'selected' : '' }}>Convenio</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Año de egreso colegio: <span class="text-danger">*</span></label><input type="number" name="egresocolegio" id="egresocolegio" class="form-control" value="{{ old('egresocolegio') }}" required></div>
                        </div>
                    </div>
                </div>

                {{-- DATOS ACADÉMICOS --}}
                <div class="card card-outline card-success shadow-sm mb-4">
                    <div class="card-header"><h3 class="card-title font-weight-bold text-success"><i class="fas fa-graduation-cap mr-1"></i> 5. Datos Académicos</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Fecha de inscripción: <span class="text-danger">*</span></label><input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', date('Y-m-d')) }}" required></div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">Diploma de bachiller: <span class="text-danger">*</span></label>
                                <select name="diplomabachiller" id="diplomabachiller" class="form-control custom-select" required>
                                    <option value="0" {{ old('diplomabachiller') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('diplomabachiller') == '1' ? 'selected' : '' }}>Sí</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3"><label class="font-weight-bold">¿Es becado?:</label>
                                <select name="becado" id="becado" class="form-control custom-select">
                                    <option value="0" {{ old('becado') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('becado') == '1' ? 'selected' : '' }}>Sí</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3"><label for="gestion_actual" class="font-weight-bold">Gestión académica:</label>
                                <input type="hidden" name="id_gestionacademica" id="id_gestionacademica" value="{{ old('id_gestionacademica', $gestionSeleccionadaId) }}">
                                <input type="text" id="gestion_actual" class="form-control" value="{{ $gestionActual ? $gestionActual->gestion.' - '.$gestionActual->periodo : 'Sin gestión disponible' }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3"><label class="font-weight-bold">Traspaso / Convalidación:</label>
                                <select name="traspaso_convalidacion" id="traspaso_convalidacion" class="form-control custom-select">
                                    <option value="0" {{ old('traspaso_convalidacion') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('traspaso_convalidacion') == '1' ? 'selected' : '' }}>Sí</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-primary font-weight-bold mr-2 px-4"><i class="fas fa-save mr-1"></i> Guardar Inscripción</button>
                        <a href="{{ url()->previous() }}" class="btn btn-secondary font-weight-bold"><i class="fas fa-undo mr-1"></i> Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@push('js')
    <script>
        // Catálogo completo de postulaciones (agrupadas o individuales por carrera)
        const postulantesData = @js($postulantesData);

        const searchInput = document.getElementById('postulante_search');
        const dropdownResults = document.getElementById('customDropdownResults');
        const postulacionesContainer = document.getElementById('postulaciones_container');
        const btnClearSearch = document.getElementById('btnClearSearch');
        const formPostulante = document.getElementById('formPostulanteNuevo');
        const gestionSelect = document.getElementById('id_gestionacademica');
        let currentPostulacionesCI = [];

        // Prevenir que presionar ENTER envíe el formulario por accidente
        formPostulante?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement === searchInput) {
                e.preventDefault();
                const firstItem = dropdownResults.querySelector('.select-postulante');
                if (firstItem) {
                    firstItem.click();
                }
            }
        });

        // Función para renderizar archivos o imágenes
        function renderizarArchivoDirecto(containerId, ruta) {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (!ruta || ruta.trim() === '') {
                container.innerHTML = `<span class="text-muted small font-italic">Sin archivo</span>`;
                return;
            }

            const url = `{{ asset('storage') }}/${ruta.split('/').map(encodeURIComponent).join('/')}`;
            const ext = ruta.split('.').pop().toLowerCase();
            const imageExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

            if (imageExtensions.includes(ext)) {
                container.innerHTML = `
                    <a href="${url}" target="_blank" title="Ampliar imagen">
                        <img src="${url}" class="img-thumbnail shadow-sm" style="max-height: 95px; width: 100%; object-fit: cover;">
                    </a>
                `;
            } else {
                container.innerHTML = `
                    <a href="${url}" target="_blank" class="btn btn-sm btn-info font-weight-bold">
                        <i class="fas fa-file-pdf mr-1"></i> Abrir Archivo (${ext.toUpperCase()})
                    </a>
                `;
            }
        }

        function renderizarPostulaciones(postulaciones) {
            postulacionesContainer.replaceChildren();

            postulaciones.forEach((postulacion, index) => {
                const row = document.createElement('div');
                row.className = 'border-bottom pb-3 mb-3';

                const title = document.createElement('strong');
                title.className = 'd-block mb-2 text-primary';
                title.textContent = postulacion.carreraNombre;

                const hiddenId = document.createElement('input');
                hiddenId.type = 'hidden';
                hiddenId.name = `postulaciones[${index}][id_postulante]`;
                hiddenId.value = postulacion.id;

                const voucherLabel = document.createElement('label');
                voucherLabel.textContent = 'Voucher (N° depósito)';
                voucherLabel.className = 'font-weight-bold';

                const voucher = document.createElement('input');
                voucher.type = 'text';
                voucher.name = `postulaciones[${index}][deposito]`;
                voucher.className = 'form-control border-success font-weight-bold';
                voucher.value = postulacion.deposito || '';
                voucher.required = true;
                voucher.maxLength = 255;

                row.append(title, hiddenId, voucherLabel, voucher);
                postulacionesContainer.appendChild(row);
            });
        }

        // Búsqueda flexible agrupada por C.I. único
        searchInput?.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            if (query.length === 0) {
                dropdownResults.style.display = 'none';
                return;
            }

            const palabras = query.split(/\s+/);
            
            // Agrupar registros únicos por C.I.
            const mapCI = new Map();
            postulantesData
                .filter(p => p.idgestion === String(gestionSelect.value))
                .forEach(p => {
                const textoCompleto = `${p.nombre} ${p.primerapellido} ${p.segundoapellido} ${p.ci}`.toLowerCase();
                if (palabras.every(palabra => textoCompleto.includes(palabra))) {
                    if (!mapCI.has(p.ci)) {
                        mapCI.set(p.ci, p);
                    }
                }
            });

            const matches = Array.from(mapCI.values());

            if (matches.length === 0) {
                dropdownResults.innerHTML = `<span class="dropdown-item text-muted">No se encontraron postulantes</span>`;
                dropdownResults.style.display = 'block';
                return;
            }

            dropdownResults.replaceChildren();
            matches.forEach(p => {
                const nombreCompleto = `${p.nombre} ${p.primerapellido} ${p.segundoapellido || ''}`.trim();
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'dropdown-item border-bottom py-2 select-postulante';
                button.dataset.ci = p.ci;

                const name = document.createElement('div');
                name.className = 'font-weight-bold text-primary';
                name.textContent = nombreCompleto;

                const ci = document.createElement('small');
                ci.className = 'text-muted';
                ci.textContent = `C.I.: ${p.ci}`;

                button.append(name, ci);
                dropdownResults.appendChild(button);
            });

            dropdownResults.style.display = 'block';
        });

        // Al seleccionar un postulante por su C.I.
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.select-postulante');
            if (!target) {
                if (!searchInput.contains(e.target) && !dropdownResults.contains(e.target)) {
                    dropdownResults.style.display = 'none';
                }
                return;
            }

            const ciSeleccionado = target.dataset.ci;
            
            // Obtener todas las inscripciones/carreras asociadas a este C.I.
            currentPostulacionesCI = postulantesData.filter(item =>
                item.ci === ciSeleccionado && item.idgestion === String(gestionSelect.value)
            );
            if (currentPostulacionesCI.length === 0) return;

            // Tomamos el primer registro para rellenar los datos personales comunes
            const p = currentPostulacionesCI[0];

            const nombreCompleto = `${p.nombre} ${p.primerapellido} ${p.segundoapellido || ''}`.trim();
            searchInput.value = `${nombreCompleto} - CI: ${p.ci}`;
            dropdownResults.style.display = 'none';
            renderizarPostulaciones(currentPostulacionesCI);

            // Rellenar demás campos del formulario
            const campos = [
                'nombre', 'primerapellido', 'segundoapellido', 'ci', 'lugarexpedicion',
                'fechanacimiento', 'lugarnacimiento', 'nacionalidad', 'sexo', 'direccion',
                'telefono', 'celular', 'email', 'nombrecontacto', 'celularcontacto',
                'parentescocontacto', 'nombrecolegio', 'caractercolegio', 'egresocolegio',
                'diplomabachiller'
            ];

            campos.forEach(campo => {
                const el = document.getElementById(campo);
                if (el) el.value = p[campo] || '';
            });

            if (p.idgestion) {
                document.getElementById('id_gestionacademica').value = p.idgestion;
            }

            // Renderizar documentos
            renderizarArchivoDirecto('preview_ci', p.pathci);
            renderizarArchivoDirecto('preview_ci_reverso', p.pathciReverso);
            renderizarArchivoDirecto('preview_nac', p.pathnac);
            renderizarArchivoDirecto('preview_dip', p.pathdip);
        });

        // Botón limpiar búsqueda
        btnClearSearch?.addEventListener('click', function() {
            searchInput.value = '';
            currentPostulacionesCI = [];
            postulacionesContainer.innerHTML = '<span class="text-muted">Busque una persona para revisar sus carreras y comprobantes.</span>';
            dropdownResults.style.display = 'none';
            
            ['preview_ci', 'preview_ci_reverso', 'preview_nac', 'preview_dip'].forEach(id => {
                document.getElementById(id).innerHTML = `<span class="text-muted small font-italic">Sin archivo</span>`;
            });
        });

        formPostulante?.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
            }
        });
    </script>
@endpush