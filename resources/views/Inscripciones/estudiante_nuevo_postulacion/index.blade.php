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

            @include('partials.sweetalert-messages')

            <form action="{{ route('estudiante_nuevo_postulacion.store') }}" method="POST" id="formPostulanteNuevo">
                @csrf
                <input type="hidden" name="accion_credenciales" id="accion_credenciales" value="">

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
                            <div class="col-md-5 mb-3">
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
                                        <button type="button" id="btnSearchPostulante" class="btn btn-primary" title="Buscar postulante">
                                            <i class="fas fa-search mr-1"></i> Buscar
                                        </button>
                                        <button type="button" id="btnClearSearch" class="btn btn-outline-secondary" title="Limpiar búsqueda"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Cada búsqueda consulta nuevamente los datos vigentes.</small>
                                <div id="customDropdownResults" class="list-group mt-2 shadow-sm" aria-live="polite" style="max-height: 250px; overflow-y: auto; display: none;">
                                </div>
                                <div id="avisoEstudianteExistente" class="alert alert-info mt-2 mb-0 d-none" role="status"></div>
                            </div>

                            <div class="col-md-7 mb-3">
                                <label class="font-weight-bold">Postulaciones pendientes de esta persona:</label>
                                <small class="form-text text-muted mb-2">Marca Sí solo en las carreras que se inscribirán ahora. Las que queden en No seguirán pendientes.</small>
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
                                <span class="small font-weight-bold text-dark d-block mb-1">Fotografía</span>
                                <div id="preview_foto" class="border rounded p-2 bg-light shadow-sm" style="min-height: 110px; display: flex; align-items: center; justify-content: center;">
                                    <span class="text-muted small font-italic">Sin archivo</span>
                                </div>
                            </div>
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
        let postulantesData = [];
        const searchInput = document.getElementById('postulante_search');
        const dropdownResults = document.getElementById('customDropdownResults');
        const postulacionesContainer = document.getElementById('postulaciones_container');
        const btnSearchPostulante = document.getElementById('btnSearchPostulante');
        const btnClearSearch = document.getElementById('btnClearSearch');
        const formPostulante = document.getElementById('formPostulanteNuevo');
        const accionCredenciales = document.getElementById('accion_credenciales');
        const avisoEstudianteExistente = document.getElementById('avisoEstudianteExistente');
        let currentPostulacionesCI = [];
        let usuarioExistenteSeleccionado = false;
        let usuarioSeleccionado = '';
        let reenvioConfirmado = false;
        let solicitudBusquedaId = 0;
        let solicitudBusquedaController = null;
        const textoBotonBusqueda = '<i class="fas fa-search mr-1"></i> Buscar';

        // Prevenir que presionar ENTER envíe el formulario por accidente
        formPostulante?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement === searchInput) {
                e.preventDefault();
                btnSearchPostulante.click();
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
                const resoluciones = (postulacion.carreraResoluciones || []).filter(Boolean);
                title.textContent = resoluciones.length
                    ? `${postulacion.carreraNombre} · R.M. ${resoluciones.join(' / ')}`
                    : postulacion.carreraNombre;

                const turnoNombre = {
                    1: 'Mañana',
                    2: 'Tarde',
                    3: 'Noche'
                }[Number(postulacion.turno)] || 'No registrado';
                const turnoParalelo = document.createElement('small');
                turnoParalelo.className = 'd-block text-muted mb-2';
                turnoParalelo.textContent = `Turno: ${turnoNombre} · Paralelo: ${postulacion.paralelo || 'No registrado'}`;

                const hiddenId = document.createElement('input');
                hiddenId.type = 'hidden';
                hiddenId.name = `postulaciones[${index}][id_postulante]`;
                hiddenId.value = postulacion.id;

                const decisionLabel = document.createElement('label');
                decisionLabel.textContent = '¿Inscribir en esta carrera?';
                decisionLabel.className = 'font-weight-bold d-block mb-1';

                const decisiones = document.createElement('div');
                decisiones.className = 'mb-2';

                ['si', 'no'].forEach((decision) => {
                    const idDecision = `postulacion_${index}_${decision}`;
                    const control = document.createElement('div');
                    control.className = 'custom-control custom-radio custom-control-inline';

                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.id = idDecision;
                    radio.name = `postulaciones[${index}][decision]`;
                    radio.value = decision;
                    radio.className = 'custom-control-input';
                    radio.required = true;
                    radio.checked = decision === 'no';
                    radio.addEventListener('change', () => {
                        const inscribir = radio.checked && decision === 'si';
                        voucher.disabled = !inscribir;
                        voucher.required = inscribir;
                    });

                    const label = document.createElement('label');
                    label.htmlFor = idDecision;
                    label.className = 'custom-control-label';
                    label.textContent = decision === 'si' ? 'Sí, inscribir ahora' : 'No, dejar pendiente';

                    control.append(radio, label);
                    decisiones.appendChild(control);
                });

                const voucherLabel = document.createElement('label');
                voucherLabel.textContent = 'Voucher (N° depósito), requerido si selecciona Sí';
                voucherLabel.className = 'font-weight-bold';

                const voucher = document.createElement('input');
                voucher.type = 'text';
                voucher.name = `postulaciones[${index}][deposito]`;
                voucher.className = 'form-control border-success font-weight-bold';
                voucher.value = postulacion.deposito || '';
                voucher.required = false;
                voucher.disabled = true;
                voucher.maxLength = 255;

                row.append(title, turnoParalelo, hiddenId, decisionLabel, decisiones, voucherLabel, voucher);
                postulacionesContainer.appendChild(row);
            });
        }

        function mostrarResultadoBusqueda(mensaje, clase = 'text-muted') {
            dropdownResults.replaceChildren();
            const resultado = document.createElement('div');
            resultado.className = `list-group-item ${clase}`;
            resultado.textContent = mensaje;
            dropdownResults.appendChild(resultado);
            dropdownResults.style.display = 'block';
        }

        function limpiarSeleccionPostulante() {
            currentPostulacionesCI = [];
            usuarioExistenteSeleccionado = false;
            usuarioSeleccionado = '';
            accionCredenciales.value = '';
            reenvioConfirmado = false;
            avisoEstudianteExistente.textContent = '';
            avisoEstudianteExistente.classList.add('d-none');
            postulacionesContainer.innerHTML = '<span class="text-muted">Busque una persona para revisar sus carreras y comprobantes.</span>';
            ['preview_foto', 'preview_ci', 'preview_ci_reverso', 'preview_nac', 'preview_dip'].forEach(id => {
                document.getElementById(id).innerHTML = '<span class="text-muted small font-italic">Sin archivo</span>';
            });
        }

        async function buscarPostulanteActualizado() {
            const query = searchInput.value.trim();
            if (!query) {
                mostrarResultadoBusqueda('Escriba un C.I. o nombre para buscar.');
                searchInput.focus();
                return;
            }

            solicitudBusquedaController?.abort();
            const controller = new AbortController();
            solicitudBusquedaController = controller;
            const solicitudId = ++solicitudBusquedaId;
            btnSearchPostulante.disabled = true;
            btnSearchPostulante.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Buscando...';
            mostrarResultadoBusqueda('Consultando postulaciones actualizadas...');

            try {
                const url = new URL("{{ route('estudiante_nuevo_postulacion.buscar') }}", window.location.origin);
                url.searchParams.set('q', query);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error('No se pudo completar la búsqueda. Intente nuevamente.');
                }

                const resultado = await response.json();
                if (solicitudId !== solicitudBusquedaId || query !== searchInput.value.trim()) return;

                postulantesData = Array.isArray(resultado.postulantes) ? resultado.postulantes : [];
                const mapCI = new Map();
                postulantesData.forEach(postulante => {
                    if (!mapCI.has(postulante.ci)) {
                        mapCI.set(postulante.ci, postulante);
                    }
                });
                const coincidencias = Array.from(mapCI.values());

                if (coincidencias.length === 0) {
                    mostrarResultadoBusqueda('No se encontraron postulaciones pendientes en la gestión vigente.');
                    return;
                }

                dropdownResults.replaceChildren();
                coincidencias.forEach(postulante => {
                    const nombreCompleto = `${postulante.nombre} ${postulante.primerapellido} ${postulante.segundoapellido || ''}`.trim();
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action py-2 select-postulante';
                    button.dataset.ci = postulante.ci;

                    const name = document.createElement('div');
                    name.className = 'font-weight-bold text-primary';
                    name.textContent = nombreCompleto;

                    const ci = document.createElement('small');
                    ci.className = 'text-muted';
                    ci.textContent = `C.I.: ${postulante.ci}`;

                    button.append(name, ci);
                    dropdownResults.appendChild(button);
                });
                dropdownResults.style.display = 'block';
            } catch (error) {
                if (error.name !== 'AbortError' && solicitudId === solicitudBusquedaId) {
                    mostrarResultadoBusqueda(error.message || 'Ocurrió un error al buscar. Intente nuevamente.', 'text-danger');
                }
            } finally {
                if (solicitudId === solicitudBusquedaId) {
                    solicitudBusquedaController = null;
                    btnSearchPostulante.disabled = false;
                    btnSearchPostulante.innerHTML = textoBotonBusqueda;
                }
            }
        }

        btnSearchPostulante?.addEventListener('click', buscarPostulanteActualizado);

        searchInput?.addEventListener('input', function() {
            solicitudBusquedaId++;
            solicitudBusquedaController?.abort();
            solicitudBusquedaController = null;
            btnSearchPostulante.disabled = false;
            btnSearchPostulante.innerHTML = textoBotonBusqueda;
            postulantesData = [];
            limpiarSeleccionPostulante();
            dropdownResults.style.display = 'none';
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
            currentPostulacionesCI = postulantesData.filter(item => item.ci === ciSeleccionado);
            if (currentPostulacionesCI.length === 0) return;

            // Tomamos el primer registro para rellenar los datos personales comunes
            const p = currentPostulacionesCI[0];
            usuarioExistenteSeleccionado = Boolean(p.usuarioExistente);
            usuarioSeleccionado = p.usuario || '';
            accionCredenciales.value = '';
            reenvioConfirmado = false;
            if (p.estudianteExistente) {
                avisoEstudianteExistente.textContent = 'Esta persona ya tiene una ficha de estudiante en el sistema. Se reutilizará esa ficha y solo se agregará la carrera que se inscriba.';
                avisoEstudianteExistente.classList.remove('d-none');
            } else {
                avisoEstudianteExistente.textContent = '';
                avisoEstudianteExistente.classList.add('d-none');
            }

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
            renderizarArchivoDirecto('preview_foto', p.foto);
            renderizarArchivoDirecto('preview_ci', p.pathci);
            renderizarArchivoDirecto('preview_ci_reverso', p.pathciReverso);
            renderizarArchivoDirecto('preview_nac', p.pathnac);
            renderizarArchivoDirecto('preview_dip', p.pathdip);
        });

        // Botón limpiar búsqueda
        btnClearSearch?.addEventListener('click', function() {
            searchInput.value = '';
            solicitudBusquedaId++;
            solicitudBusquedaController?.abort();
            solicitudBusquedaController = null;
            btnSearchPostulante.disabled = false;
            btnSearchPostulante.innerHTML = textoBotonBusqueda;
            postulantesData = [];
            limpiarSeleccionPostulante();
            dropdownResults.style.display = 'none';
        });

        formPostulante?.addEventListener('submit', async function(event) {
            if (reenvioConfirmado) {
                reenvioConfirmado = false;
            } else {
                const seleccionadas = this.querySelectorAll('input[type="radio"][name$="[decision]"][value="si"]:checked');
                if (seleccionadas.length === 0) {
                    event.preventDefault();
                    if (window.Swal?.fire) {
                        await window.Swal.fire({
                            icon: 'warning',
                            title: 'No se seleccionó ninguna carrera',
                            text: 'Marca Sí en al menos una carrera para continuar.',
                            confirmButtonText: 'Entendido',
                        });
                    } else {
                        window.alert('Marca Sí en al menos una carrera para continuar.');
                    }
                    return;
                }

                if (usuarioExistenteSeleccionado && !accionCredenciales.value) {
                    event.preventDefault();
                    let decision;
                    if (window.Swal?.fire) {
                        const respuesta = await window.Swal.fire({
                            icon: 'warning',
                            title: 'La persona ya tiene una cuenta',
                            text: `El usuario ${usuarioSeleccionado} ya existe. ¿Desea restablecer su contraseña? Se conservará el mismo usuario y no se creará otra cuenta.`,
                            showDenyButton: true,
                            showCancelButton: true,
                            confirmButtonText: 'Sí, restablecer acceso',
                            denyButtonText: 'No, conservar acceso',
                            cancelButtonText: 'Cancelar inscripción',
                        });
                        if (respuesta.isConfirmed) {
                            decision = 'regenerar';
                        } else if (respuesta.isDenied) {
                            decision = 'mantener';
                        } else {
                            return;
                        }
                    } else {
                        decision = window.confirm(
                            `El usuario ${usuarioSeleccionado} ya existe. ¿Desea restablecer su contraseña? Se conservará el mismo usuario.`
                        ) ? 'regenerar' : 'mantener';
                    }

                    accionCredenciales.value = decision;
                    reenvioConfirmado = true;
                    this.requestSubmit();
                    return;
                }
            }

            const btn = this.querySelector('button[type="submit"]');
            if (typeof window.itsaToast === 'function') {
                window.itsaToast('info', 'Procesando inscripción', usuarioExistenteSeleccionado && accionCredenciales.value === 'regenerar'
                    ? 'Se restablecerá la contraseña y se abrirá el comprobante de las nuevas credenciales.'
                    : 'Al completar el registro se abrirá el PDF con las credenciales, si corresponde.');
            }
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
            }
        });
    </script>
@endpush