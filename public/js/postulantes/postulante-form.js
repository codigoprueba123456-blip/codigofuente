document.addEventListener('DOMContentLoaded', function () {
    const MAX_SOURCE_FILE_BYTES = 15 * 1024 * 1024;
    const MAX_TOTAL_FILE_BYTES = 1550 * 1024;
    const fileBudgets = {
        foto: 150 * 1024,
        path_ci_frontal: 350 * 1024,
        path_ci_reverso: 350 * 1024,
        path_nacimiento: 350 * 1024,
        path_diploma: 350 * 1024
    };
    const filePreviews = {
        path_ci_frontal: 'preview_ci_frontal',
        path_ci_reverso: 'preview_ci_reverso',
        path_nacimiento: 'preview_nacimiento',
        path_diploma: 'preview_diploma'
    };
    const previewUrls = new Map();
    const pendingInputs = new Set();
    let activeCompressions = 0;

    // ==========================================
    // 1. COMPRESIÓN, VISTA PREVIA Y VALIDACIÓN DE IMÁGENES
    // ==========================================
    const photoInput = document.getElementById('foto');
    const btnRemovePhoto = document.getElementById('btnRemovePhoto');
    const preview = document.getElementById('photoPreview');
    const form = document.getElementById('formRegistrarPostulante')
        || document.getElementById('formEditarPostulante')
        || document.querySelector('form');
    const submitButtons = form ? form.querySelectorAll('button[type="submit"]') : [];

    function feedbackFor(input) {
        let feedback = input.validationFeedback;
        if (feedback) return feedback;

        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block';
        feedback.id = `validation-error-${input.id || input.name}`;
        feedback.setAttribute('role', 'alert');

        let anchor = input;
        if (input.id === 'foto') anchor = input.parentElement;
        else if (input.classList.contains('custom-file-input')) anchor = input.closest('.custom-file');
        anchor.insertAdjacentElement('afterend', feedback);
        input.validationFeedback = feedback;

        if (input.id) input.setAttribute('aria-describedby', feedback.id);
        input.setAttribute('aria-invalid', 'true');
        input.classList.add('is-invalid');

        if (input.id === 'foto') {
            document.getElementById('fotografiaGuia')?.classList.add('border', 'border-danger', 'text-danger');
            preview?.classList.add('border', 'border-danger');
        } else if (input.classList.contains('custom-file-input')) {
            input.nextElementSibling?.classList.add('is-invalid', 'border-danger', 'text-danger');
        }

        return feedback;
    }

    function showFieldError(input, message) {
        feedbackFor(input).textContent = message;
    }

    function clearFieldError(input) {
        const feedbackId = input.validationFeedback?.id;
        input.validationFeedback?.remove();
        input.validationFeedback = null;
        if (feedbackId && input.getAttribute('aria-describedby') === feedbackId) {
            input.removeAttribute('aria-describedby');
        }
        input.removeAttribute('aria-invalid');
        input.classList.remove('is-invalid');

        if (input.id === 'foto') {
            document.getElementById('fotografiaGuia')?.classList.remove('border', 'border-danger', 'text-danger');
            preview?.classList.remove('border', 'border-danger');
        } else if (input.classList.contains('custom-file-input')) {
            input.nextElementSibling?.classList.remove('is-invalid', 'border-danger', 'text-danger');
        }
    }

    function validationMessage(input) {
        if (input.validity.valueMissing) {
            return input.type === 'file'
                ? 'Selecciona una imagen para este documento.'
                : 'Completa este campo obligatorio.';
        }
        if (input.validity.typeMismatch && input.type === 'email') {
            return 'Ingresa un correo electrónico válido.';
        }
        if (input.validity.rangeUnderflow || input.validity.rangeOverflow) {
            return 'El valor está fuera del rango permitido.';
        }
        return input.validationMessage || 'Revisa este campo.';
    }

    function focusField(input) {
        const pane = input.closest('.step-pane');
        if (pane?.classList.contains('d-none')) {
            const paneNumber = Number(pane.id.replace('paneStep', ''));
            if (paneNumber && window.cambiarPaso) window.cambiarPaso(paneNumber);
        }

        const target = input.id === 'foto'
            ? document.getElementById('fotografiaGuia')
            : input.classList.contains('custom-file-input')
                ? input.nextElementSibling
                : input;

        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (input.type !== 'file') input.focus({ preventScroll: true });
    }

    function setCompressionState(isCompressing) {
        submitButtons.forEach(button => {
            button.disabled = isCompressing;
            button.dataset.originalText ??= button.innerHTML;
            button.innerHTML = isCompressing
                ? '<i class="fas fa-compress-arrows-alt mr-1"></i> Comprimiendo imágenes...'
                : button.dataset.originalText;
        });
    }

    function canvasBlob(canvas, type, quality) {
        return new Promise((resolve, reject) => {
            canvas.toBlob(blob => {
                if (blob) resolve(blob);
                else reject(new Error('El navegador no pudo procesar esta imagen.'));
            }, type, quality);
        });
    }

    async function compressImage(file, maxBytes, isProfilePhoto) {
        if (file.size <= maxBytes) return file;

        let imageSource;
        let cleanup = () => {};

        if ('createImageBitmap' in window) {
            imageSource = await createImageBitmap(file);
            cleanup = () => imageSource.close();
        } else {
            const objectUrl = URL.createObjectURL(file);
            imageSource = new Image();
            imageSource.src = objectUrl;
            try {
                await imageSource.decode();
            } catch (error) {
                URL.revokeObjectURL(objectUrl);
                throw new Error('No se pudo leer la imagen. Selecciona un archivo JPG o PNG válido.');
            }
            cleanup = () => URL.revokeObjectURL(objectUrl);
        }

        try {
            let scale = Math.min(
                1,
                (isProfilePhoto ? 1200 : 1800) / imageSource.width,
                (isProfilePhoto ? 1600 : 2400) / imageSource.height
            );

            while (scale >= 0.55) {
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(imageSource.width * scale));
                canvas.height = Math.max(1, Math.round(imageSource.height * scale));
                const context = canvas.getContext('2d', { alpha: false });
                if (!context) throw new Error('El navegador no pudo preparar la imagen para comprimirla.');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(imageSource, 0, 0, canvas.width, canvas.height);

                for (const quality of [0.86, 0.8, 0.74, 0.68, 0.62]) {
                    const blob = await canvasBlob(canvas, 'image/jpeg', quality);

                    if (blob.size <= maxBytes) {
                        const baseName = file.name.replace(/\.[^.]+$/, '');
                        return new File([blob], `${baseName}.jpg`, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                    }
                }

                scale *= 0.85;
            }
        } finally {
            cleanup();
        }

        throw new Error('No se pudo reducir esta imagen manteniendo una calidad legible. Toma otra foto más cercana y enfocada.');
    }

    function setInputFile(input, file) {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
    }

    function showImagePreview(input, file) {
        const previewId = filePreviews[input.name];
        const container = previewId ? document.getElementById(previewId) : null;
        const image = input.id === 'foto'
            ? preview
            : container?.querySelector('img');

        if (!image) return;

        const oldUrl = previewUrls.get(input.id);
        if (oldUrl) URL.revokeObjectURL(oldUrl);

        const objectUrl = URL.createObjectURL(file);
        previewUrls.set(input.id, objectUrl);

        if (input.id === 'foto') {
            preview.innerHTML = `<img src="${objectUrl}" alt="Vista previa de la fotografía">`;
            btnRemovePhoto?.classList.remove('d-none');
        } else {
            const imageElement = container.querySelector('img');
            imageElement.src = objectUrl;
            imageElement.classList.remove('d-none');
            container.querySelector('.file-name').textContent = file.name;
            container.classList.remove('d-none');
        }
    }

    async function processImageInput(input) {
        const sourceFile = input.files?.[0];
        if (!sourceFile) return;

        clearFieldError(input);

        if (!['image/jpeg', 'image/png'].includes(sourceFile.type)) {
            if (input.id === 'foto') resetPhotoPreview();
            else clearDocumentInput(input.id);
            showFieldError(input, 'Solo se aceptan imágenes JPG o PNG. Selecciona un archivo de imagen válido.');
            return;
        }

        if (sourceFile.size > MAX_SOURCE_FILE_BYTES) {
            if (input.id === 'foto') resetPhotoPreview();
            else clearDocumentInput(input.id);
            showFieldError(input, 'La imagen original supera 15 MB. Selecciona una imagen más pequeña.');
            return;
        }

        activeCompressions++;
        pendingInputs.add(input);
        setCompressionState(true);

        try {
            const compressedFile = await compressImage(
                sourceFile,
                fileBudgets[input.id],
                input.id === 'foto'
            );

            if (input.files?.[0] !== sourceFile) return;

            setInputFile(input, compressedFile);
            showImagePreview(input, compressedFile);
            const fileLabel = input.id === 'foto'
                ? document.getElementById('fotografiaGuia')
                : input.nextElementSibling;
            if (fileLabel) {
                fileLabel.textContent = `${compressedFile.name} (${Math.ceil(compressedFile.size / 1024)} KB)`;
            }

            const totalSize = Object.keys(fileBudgets).reduce((sum, id) => {
                const field = document.getElementById(id);
                return sum + (field?.files?.[0]?.size ?? 0);
            }, 0);

            if (totalSize > MAX_TOTAL_FILE_BYTES) {
                if (input.id === 'foto') resetPhotoPreview();
                else clearDocumentInput(input.id);
                showFieldError(input, 'El total de las imágenes supera 1.550 KB. Reduce una imagen y vuelve a seleccionarla.');
            }
        } catch (error) {
            if (input.id === 'foto') resetPhotoPreview();
            else clearDocumentInput(input.id);
            showFieldError(input, error.message || 'No se pudo comprimir la imagen.');
        } finally {
            activeCompressions--;
            pendingInputs.delete(input);
            setCompressionState(activeCompressions > 0);
        }
    }

    function resetPhotoPreview() {
        if (photoInput) photoInput.value = '';
        if (preview) preview.innerHTML = '<i class="fas fa-camera"></i><span>Vista previa</span>';
        if (btnRemovePhoto) btnRemovePhoto.classList.add('d-none');
        const photoLabel = document.getElementById('fotografiaGuia');
        if (photoLabel?.dataset.originalText) photoLabel.textContent = photoLabel.dataset.originalText;
        if (photoInput) clearFieldError(photoInput);
        const oldUrl = previewUrls.get('foto');
        if (oldUrl) URL.revokeObjectURL(oldUrl);
        previewUrls.delete('foto');
    }

    const photoLabel = document.getElementById('fotografiaGuia');
    if (photoLabel) photoLabel.dataset.originalText = photoLabel.textContent.trim();

    photoInput?.addEventListener('change', () => processImageInput(photoInput));

    btnRemovePhoto?.addEventListener('click', function () {
        resetPhotoPreview();
    });

    // ==========================================
    // 2. ETIQUETA, VALIDACIÓN, VISTA PREVIA Y ELIMINACIÓN DE DOCUMENTOS
    // ==========================================
    function clearDocumentInput(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        input.value = '';
        clearFieldError(input);
        
        const nextSibling = input.nextElementSibling;
        if (nextSibling && nextSibling.classList.contains('custom-file-label')) {
            nextSibling.innerText = 'Seleccionar imagen...';
        }

        const oldUrl = previewUrls.get(inputId);
        if (oldUrl) URL.revokeObjectURL(oldUrl);
        previewUrls.delete(inputId);

        const containerId = filePreviews[input.name];
        if (containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                container.classList.add('d-none');
                const imgPreview = container.querySelector('img');
                if (imgPreview) imgPreview.src = '';
            }
        }
    }

    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', () => processImageInput(input));
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-doc');
        if (btn) {
            const targetId = btn.getAttribute('data-target');
            if (targetId) {
                clearDocumentInput(targetId);
            }
        }
    });

    form?.addEventListener('reset', function () {
        Object.values(filePreviews).forEach(previewId => {
            const input = document.getElementById(previewId.replace('preview_', 'path_'));
            if (input) clearDocumentInput(input.id);
        });
        resetPhotoPreview();
    });

    // ==========================================
    // 3. VALIDACIÓN COMPLETA DEL FORMULARIO ANTES DEL ENVÍO
    // ==========================================
    form?.addEventListener('submit', function (e) {
        if (activeCompressions > 0) {
            e.preventDefault();
            const input = pendingInputs.values().next().value;
            if (input) showFieldError(input, 'Espera a que termine la compresión antes de guardar.');
            return;
        }

        const totalSize = Object.keys(fileBudgets).reduce((sum, id) => {
            const field = document.getElementById(id);
            return sum + (field?.files?.[0]?.size ?? 0);
        }, 0);

        if (totalSize > MAX_TOTAL_FILE_BYTES) {
            e.preventDefault();
            const input = form.querySelector('input[type="file"]');
            if (input) showFieldError(input, 'El total de las imágenes supera 1.550 KB. Reduce una imagen y vuelve a intentarlo.');
            return;
        }

        let primerElementoInvalido = null;
        form.querySelectorAll('input, select, textarea').forEach(element => {
            if (element.checkValidity()) {
                clearFieldError(element);
                return;
            }

            showFieldError(element, validationMessage(element));
            if (!primerElementoInvalido) primerElementoInvalido = element;
        });

        if (primerElementoInvalido) {
            e.preventDefault();
            focusField(primerElementoInvalido);
        }
    });

    form?.querySelectorAll('input, select, textarea').forEach(input => {
        const clearIfValid = () => {
            if (input.checkValidity()) clearFieldError(input);
        };
        input.addEventListener(input.type === 'file' || input.tagName === 'SELECT' ? 'change' : 'input', clearIfValid);
        input.addEventListener('blur', clearIfValid);
    });

    if (form) {
        let serverErrors = window.postulanteValidationErrors;
        if (!serverErrors) {
            try {
                serverErrors = JSON.parse(form.dataset.validationErrors || '{}');
            } catch (error) {
                console.error('No se pudieron leer los errores de validación del formulario.', error);
                serverErrors = {};
            }
        }

        let firstServerError = null;
        Object.entries(serverErrors || {}).forEach(([name, messages]) => {
            const input = Array.from(form.elements).find(element => element.name === name)
                || (name === 'documentos' ? form.querySelector('input[type="file"]') : null);
            if (!input) return;

            showFieldError(input, messages[0]);
            if (!firstServerError) firstServerError = input;
        });
        if (firstServerError) focusField(firstServerError);
    }

    // ==========================================
    // 4. TUTORIAL INTERACTIVO Y GUÍA
    // ==========================================
    const pasos = [
        { elemento: 'fotografiaGuia', titulo: 'Fotografía de perfil (Obligatorio)', texto: 'Selecciona una foto formal tipo carnet. Se comprimirá a un máximo de 150 KB.' },
        { elemento: 'nombre', titulo: 'Nombre', texto: 'Escribe tu nombre completo y real.' },
        { elemento: 'primerapellido', titulo: 'Primer apellido', texto: 'Este campo es obligatorio. Escribe tu primer apellido.' },
        { elemento: 'segundoapellido', titulo: 'Segundo apellido', texto: 'Este campo es opcional. Déjalo vacío si no corresponde.' },
        { elemento: 'ci', titulo: 'CI', texto: 'Registra tu número de cédula de identidad.' },
        { elemento: 'lugarexpedicion', titulo: 'Lugar de expedición', texto: 'Selecciona el departamento donde fue expedido tu CI.' },
        { elemento: 'fechanacimiento', titulo: 'Fecha de nacimiento', texto: 'Selecciona tu fecha de nacimiento.' },
        { elemento: 'lugarnacimiento', titulo: 'Lugar de nacimiento', texto: 'Escribe la ciudad o localidad donde naciste.' },
        { elemento: 'nacionalidad', titulo: 'Nacionalidad', texto: 'Indica tu nacionalidad. El formulario propone Boliviana como valor inicial.' },
        { elemento: 'sexo', titulo: 'Sexo', texto: 'Selecciona una opción de sexo.' },
        { elemento: 'direccion', titulo: 'Dirección', texto: 'Escribe tu dirección actual y completa.' },
        { elemento: 'telefono', titulo: 'Teléfono', texto: 'Puedes registrar un teléfono fijo. Este campo es opcional.' },
        { elemento: 'celular', titulo: 'Celular', texto: 'Registra tu número de celular para contactarte.' },
        { elemento: 'email', titulo: 'Email', texto: 'Escribe un correo electrónico válido para recibir comunicaciones.' },
        { elemento: 'nombrecontacto', titulo: 'Nombre de contacto', texto: 'Indica el nombre de la persona de contacto en caso de emergencia.' },
        { elemento: 'celularcontacto', titulo: 'Celular de contacto', texto: 'Registra el celular de la persona de contacto.' },
        { elemento: 'parentescocontacto', titulo: 'Parentesco', texto: 'Especifica el parentesco con tu persona de contacto.' },
        { elemento: 'nombrecolegio', titulo: 'Nombre del colegio', texto: 'Escribe el nombre completo de tu colegio de egreso.' },
        { elemento: 'caractercolegio', titulo: 'Carácter del colegio', texto: 'Selecciona si tu colegio es Fiscal, Particular o de Convenio.' },
        { elemento: 'egresocolegio', titulo: 'Egreso del colegio', texto: 'Registra el año en que terminaste el colegio.' },
        { elemento: 'id_gestionacademica', titulo: 'Gestión académica', texto: 'Selecciona la gestión académica correspondiente a tu postulación.' },
        { elemento: 'id_carrera', titulo: 'Carrera', texto: 'Selecciona la carrera a la que deseas postular.' },
        { elemento: 'diplomabachiller', titulo: 'Diploma de bachiller', texto: 'Indica si cuentas con tu diploma de bachiller.' },
        { elemento: 'deposito', titulo: 'Nº de Depósito', texto: 'Registra el número del comprobante o papeleta de depósito bancario.' },
        { elemento: 'path_ci_frontal', titulo: 'Carnet de identidad, anverso', texto: 'Sube una foto nítida del lado frontal del carnet. Máximo 350 KB comprimida.' },
        { elemento: 'path_ci_reverso', titulo: 'Carnet de identidad, reverso', texto: 'Sube una foto nítida del lado posterior del carnet. Máximo 350 KB comprimida.' },
        { elemento: 'path_nacimiento', titulo: 'Certificado de Nacimiento (Obligatorio)', texto: 'Sube una imagen clara del certificado. Máximo 350 KB comprimida.' },
        { elemento: 'path_diploma', titulo: 'Título de Bachiller (Obligatorio)', texto: 'Sube una imagen clara del título. Máximo 350 KB comprimida.' },
        { elemento: 'guardarPostulante', titulo: 'Guardar y Registrar', texto: 'Revisa todos los datos y asegúrate de haber subido todos los documentos obligatorios antes de presionar este botón.' }
    ];

    let pasoActual = 0;
    const box = document.getElementById('tutorialBox');
    const overlay = document.getElementById('tutorial-overlay');
    const next = document.getElementById('tutorialNext');
    const prev = document.getElementById('tutorialPrev');
    const welcomeModal = document.getElementById('welcomeModal');
    const tutorialVideo = document.getElementById('tutorialVideo');

    function limpiarResaltado() {
        document.querySelectorAll('.tutorial-highlight').forEach(elemento => elemento.classList.remove('tutorial-highlight'));
    }

    function cerrarTutorial() {
        limpiarResaltado();
        if (box) box.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        localStorage.setItem('postulante_tutorial_visto', 'true');
    }

    function iniciarGuia() {
        pasoActual = 0;
        mostrarPaso();
    }

    function cerrarBienvenida() {
        if (welcomeModal) welcomeModal.style.display = 'none';
        if (tutorialVideo) tutorialVideo.pause();
        localStorage.setItem('postulante_bienvenida_vista', 'true');
        if (!localStorage.getItem('postulante_tutorial_visto')) iniciarGuia();
    }

    function abrirVideoGuia() {
        if (welcomeModal) welcomeModal.style.display = 'flex';
        if (tutorialVideo) {
            tutorialVideo.currentTime = 0;
            tutorialVideo.play().catch(() => {});
        }
    }

    function mostrarPaso() {
        limpiarResaltado();
        const paso = pasos[pasoActual];
        let target = document.getElementById(paso.elemento);
        if (!target) return;

        const pane = target.closest('.step-pane');
        if (pane?.classList.contains('d-none')) {
            const paneNumber = Number(pane.id.replace('paneStep', ''));
            if (paneNumber && window.cambiarPaso) window.cambiarPaso(paneNumber);
        }

        if (overlay) overlay.style.display = 'block';

        if (target.classList.contains('custom-file-input')) {
            target = target.closest('.custom-file') || target;
        }

        target.classList.add('tutorial-highlight');
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        const stepElem = document.getElementById('tutorialStep');
        const titleElem = document.getElementById('tutorialTitle');
        const textElem = document.getElementById('tutorialText');

        if (stepElem) stepElem.textContent = 'Paso ' + (pasoActual + 1) + ' de ' + pasos.length;
        if (titleElem) titleElem.textContent = paso.titulo;
        if (textElem) textElem.textContent = paso.texto;
        
        if (prev) prev.style.display = pasoActual === 0 ? 'none' : 'inline-block';
        if (next) next.textContent = pasoActual === pasos.length - 1 ? 'Finalizar' : 'Siguiente';
        if (box) box.style.display = 'block';
        
        const rect = target.getBoundingClientRect();
        const left = window.innerWidth > 768 ? Math.min(rect.left, window.innerWidth - 340) : 20;
        if (box) {
            box.style.top = (window.scrollY + rect.bottom + 14) + 'px';
            box.style.left = Math.max(20, left) + 'px';
        }
    }

    document.getElementById('iniciarTutorial')?.addEventListener('click', iniciarGuia);
    document.getElementById('verVideoGuia')?.addEventListener('click', abrirVideoGuia);
    document.getElementById('tutorialClose')?.addEventListener('click', cerrarTutorial);
    document.getElementById('welcomeClose')?.addEventListener('click', cerrarBienvenida);
    document.getElementById('welcomeContinue')?.addEventListener('click', cerrarBienvenida);
    
    next?.addEventListener('click', function () { 
        if (pasoActual === pasos.length - 1) cerrarTutorial(); 
        else { pasoActual++; mostrarPaso(); } 
    });
    
    prev?.addEventListener('click', function () { 
        if (pasoActual > 0) { pasoActual--; mostrarPaso(); } 
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && box && box.style.display === 'block') {
            event.preventDefault();
            next?.click();
        }
    });

    window.addEventListener('resize', function () { 
        if (box && box.style.display === 'block') mostrarPaso(); 
    });

    if (!localStorage.getItem('postulante_bienvenida_vista')) {
        if (welcomeModal) welcomeModal.style.display = 'flex';
        if (tutorialVideo) tutorialVideo.play().catch(() => {});
    } else if (!localStorage.getItem('postulante_tutorial_visto')) {
        setTimeout(iniciarGuia, 600);
    }
});