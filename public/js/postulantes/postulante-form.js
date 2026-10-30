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
        if (file.size <= maxBytes && file.type === 'image/webp') return file;

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
                throw new Error('No se pudo leer la imagen. Selecciona una foto JPG, PNG o WebP.');
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
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(imageSource, 0, 0, canvas.width, canvas.height);

                for (const quality of [0.86, 0.8, 0.74, 0.68, 0.62]) {
                    let blob = await canvasBlob(canvas, 'image/webp', quality);
                    if (blob.type !== 'image/webp') {
                        blob = await canvasBlob(canvas, 'image/jpeg', quality);
                    }

                    if (blob.size <= maxBytes) {
                        const extension = blob.type === 'image/webp' ? 'webp' : 'jpg';
                        const baseName = file.name.replace(/\.[^.]+$/, '');
                        return new File([blob], `${baseName}.${extension}`, {
                            type: blob.type,
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

        if (!sourceFile.type.startsWith('image/')) {
            alert('Solo se aceptan imágenes. No se aceptan archivos PDF.');
            input.value = '';
            return;
        }

        if (sourceFile.size > MAX_SOURCE_FILE_BYTES) {
            alert('La imagen original supera 15 MB. Selecciona una imagen más pequeña.');
            input.value = '';
            return;
        }

        activeCompressions++;
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
            input.nextElementSibling.textContent = `${compressedFile.name} (${Math.ceil(compressedFile.size / 1024)} KB)`;

            const totalSize = Object.keys(fileBudgets).reduce((sum, id) => {
                const field = document.getElementById(id);
                return sum + (field?.files?.[0]?.size ?? 0);
            }, 0);

            if (totalSize > MAX_TOTAL_FILE_BYTES) {
                input.value = '';
                alert('Las imágenes seleccionadas superan el máximo total de 1.550 KB. Reduce alguna imagen y vuelve a seleccionarla.');
                if (input.id === 'foto') resetPhotoPreview();
                else clearDocumentInput(input.id);
            }
        } catch (error) {
            if (input.id === 'foto') resetPhotoPreview();
            else clearDocumentInput(input.id);
            alert(error.message || 'No se pudo comprimir la imagen.');
        } finally {
            activeCompressions--;
            setCompressionState(activeCompressions > 0);
        }
    }

    function resetPhotoPreview() {
        if (photoInput) photoInput.value = '';
        if (preview) preview.innerHTML = '<i class="fas fa-camera"></i><span>Vista previa</span>';
        if (btnRemovePhoto) btnRemovePhoto.classList.add('d-none');
        const oldUrl = previewUrls.get('foto');
        if (oldUrl) URL.revokeObjectURL(oldUrl);
        previewUrls.delete('foto');
    }

    photoInput?.addEventListener('change', () => processImageInput(photoInput));

    btnRemovePhoto?.addEventListener('click', function () {
        resetPhotoPreview();
    });

    // ==========================================
    // 2. ETIQUETA, VALIDACIÓN, VISTA PREVIA Y ELIMINACIÓN DE DOCUMENTOS (PDF/IMAGEN)
    // ==========================================
    function clearDocumentInput(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        input.value = '';
        
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
            alert('Espera a que termine la compresión de las imágenes antes de guardar.');
            return;
        }

        const totalSize = Object.keys(fileBudgets).reduce((sum, id) => {
            const field = document.getElementById(id);
            return sum + (field?.files?.[0]?.size ?? 0);
        }, 0);

        if (totalSize > MAX_TOTAL_FILE_BYTES) {
            e.preventDefault();
            alert('Las imágenes superan el máximo total de 1.550 KB. Selecciona imágenes más pequeñas.');
            return;
        }

        const requiredElements = form.querySelectorAll('[required]');
        let faltantes = [];
        let primerElementoFaltante = null;

        requiredElements.forEach(element => {
            let estaVacio = false;

            if (element.type === 'file') {
                if (!element.files || element.files.length === 0) {
                    estaVacio = true;
                }
            } else {
                if (!element.value || element.value.trim() === '') {
                    estaVacio = true;
                }
            }

            if (estaVacio) {
                let labelText = element.getAttribute('data-name');

                if (!labelText && element.id) {
                    const label = form.querySelector('label[for="' + element.id + '"]');
                    if (label) {
                        labelText = label.innerText.replace('*', '').trim();
                    }
                }

                if (!labelText) {
                    const parent = element.closest('.mb-3');
                    if (parent) {
                        const lbl = parent.querySelector('label');
                        if (lbl) labelText = lbl.innerText.replace('*', '').trim();
                    }
                }

                if (!labelText) {
                    labelText = element.name || 'Campo obligatorio';
                }

                faltantes.push(labelText);
                if (!primerElementoFaltante) primerElementoFaltante = element;
            }
        });

        if (faltantes.length > 0) {
            e.preventDefault();
            
            let mensaje = 'Por favor completa los siguientes campos obligatorios antes de continuar:\n\n';
            faltantes.forEach((item, index) => {
                mensaje += `${index + 1}. ${item}\n`;
            });

            alert(mensaje);

            if (primerElementoFaltante) {
                let scrollTarget = primerElementoFaltante;
                if (primerElementoFaltante.classList.contains('custom-file-input') || primerElementoFaltante.id === 'foto') {
                    scrollTarget = primerElementoFaltante.closest('.card') || primerElementoFaltante;
                }
                scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (typeof primerElementoFaltante.focus === 'function' && primerElementoFaltante.type !== 'file') {
                    primerElementoFaltante.focus();
                }
            }
        }
    });

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