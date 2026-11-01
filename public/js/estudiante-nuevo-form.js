document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formEstudianteNuevo');
    if (!form) return;

    const documentIds = [
        'path_ci_frontal',
        'path_ci_reverso',
        'path_nacimiento',
        'path_diploma',
        'path_bth'
    ];
    const maxSourceFileBytes = 15 * 1024 * 1024;
    const maxDocumentBytes = 350 * 1024;
    const maxTotalFileBytes = 1750 * 1024;
    const previewUrls = new Map();
    const submitButton = form.querySelector('button[type="submit"]');
    const ciInput = document.getElementById('ci');
    const existingStudentMessage = document.getElementById('estudianteExistenteMensaje');
    const personalFieldIds = [
        'nombre', 'primerapellido', 'segundoapellido', 'lugarexpedicion',
        'fechanacimiento', 'lugarnacimiento', 'nacionalidad', 'sexo',
        'direccion', 'telefono', 'celular', 'email', 'nombrecontacto',
        'celularcontacto', 'parentescocontacto', 'nombrecolegio',
        'caractercolegio', 'egresocolegio', 'diplomabachiller'
    ];
    let ciFichaEncontrada = '';
    let consultaCiPendiente = false;
    let activeCompressions = 0;

    function mostrarFallback(title, message) {
        let feedback = form.querySelector('[data-form-feedback]');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'alert alert-warning';
            feedback.setAttribute('data-form-feedback', '');
            feedback.setAttribute('role', 'alert');
            form.prepend(feedback);
        }

        feedback.textContent = `${title}: ${message}`;
    }

    function notificar(icon, title, message) {
        if (typeof window.itsaToast === 'function') {
            window.itsaToast(icon, title, message);
        } else {
            mostrarFallback(title, message);
        }
    }

    function aplicarFichaExistente(estudiante) {
        personalFieldIds.forEach(id => {
            const field = document.getElementById(id);
            if (!field) return;
            field.value = estudiante[id] ?? '';
            field.required = false;
            if (field instanceof HTMLSelectElement) field.disabled = true;
            else field.readOnly = true;
        });

        ['path_ci_frontal', 'path_ci_reverso', 'path_nacimiento', 'path_diploma'].forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.required = false;
                input.disabled = true;
                input.closest('.col-md-6')?.classList.add('d-none');
            }
        });

        ciFichaEncontrada = estudiante.ci;
        existingStudentMessage.textContent = 'Estudiante existente: se reutilizará su ficha y documentos. Puede adjuntar o reemplazar el certificado BTH opcional.';
        existingStudentMessage.classList.remove('d-none');
    }

    function restaurarFormularioNuevo() {
        personalFieldIds.forEach(id => {
            const field = document.getElementById(id);
            if (!field) return;
            field.required = ['nombre', 'primerapellido', 'fechanacimiento', 'lugarnacimiento', 'nacionalidad', 'sexo', 'direccion', 'email', 'nombrecontacto', 'celularcontacto', 'parentescocontacto', 'nombrecolegio', 'caractercolegio', 'egresocolegio', 'diplomabachiller'].includes(id);
            field.disabled = false;
            field.readOnly = false;
            if (field instanceof HTMLSelectElement) field.value = '';
            else field.value = '';
        });

        ['path_ci_frontal', 'path_ci_reverso', 'path_nacimiento', 'path_diploma'].forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.required = true;
                input.disabled = false;
                input.closest('.col-md-6')?.classList.remove('d-none');
            }
        });
        ciFichaEncontrada = '';
        existingStudentMessage.textContent = '';
        existingStudentMessage.classList.add('d-none');
    }

    async function buscarFichaExistente() {
        const ci = ciInput.value.trim().replace(/[^A-Za-z0-9-]/g, '');
        if (!ci || ci === ciFichaEncontrada) return;

        consultaCiPendiente = true;
        try {
            const url = new URL(form.dataset.studentLookupUrl, window.location.origin);
            url.searchParams.set('ci', ci);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se pudo comprobar el CI en el sistema.');
            const result = await response.json();
            if (ciInput.value.trim().replace(/[^A-Za-z0-9-]/g, '') !== ci) return;
            if (result.estudiante) aplicarFichaExistente(result.estudiante);
        } catch (error) {
            existingStudentMessage.textContent = error.message || 'No se pudo comprobar el CI en el sistema.';
            existingStudentMessage.classList.remove('d-none');
            notificar('error', 'No se pudo comprobar el C.I.', error.message || 'Intenta nuevamente.');
        } finally {
            consultaCiPendiente = false;
        }
    }

    ciInput.addEventListener('input', () => {
        const ci = ciInput.value.trim().replace(/[^A-Za-z0-9-]/g, '');
        if (ciFichaEncontrada && ci !== ciFichaEncontrada) restaurarFormularioNuevo();
    });
    ciInput.addEventListener('blur', buscarFichaExistente);
    if (ciInput.value.trim()) buscarFichaExistente();

    function canvasBlob(canvas, quality) {
        return new Promise((resolve, reject) => {
            canvas.toBlob(blob => {
                if (blob) resolve(blob);
                else reject(new Error('El navegador no pudo procesar esta imagen.'));
            }, 'image/webp', quality);
        });
    }

    async function compressImage(file) {
        if (file.size <= maxDocumentBytes && file.type === 'image/webp') return file;

        let imageSource;
        let cleanup = function () {};

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
                throw new Error('No se pudo leer la imagen. Selecciona un archivo JPG, PNG o WebP.');
            }
            cleanup = () => URL.revokeObjectURL(objectUrl);
        }

        try {
            let scale = Math.min(1, 1800 / imageSource.width, 2400 / imageSource.height);
            while (scale >= 0.55) {
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(imageSource.width * scale));
                canvas.height = Math.max(1, Math.round(imageSource.height * scale));
                const context = canvas.getContext('2d', { alpha: false });
                if (!context) throw new Error('El navegador no pudo procesar esta imagen.');

                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(imageSource, 0, 0, canvas.width, canvas.height);

                for (const quality of [0.86, 0.8, 0.74, 0.68, 0.62]) {
                    let blob = await canvasBlob(canvas, quality);
                    if (blob.type !== 'image/webp') {
                        blob = await new Promise((resolve, reject) => {
                            canvas.toBlob(result => result ? resolve(result) : reject(new Error('No se pudo comprimir la imagen.')), 'image/jpeg', quality);
                        });
                    }

                    if (blob.size <= maxDocumentBytes) {
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

        throw new Error('No se pudo reducir la imagen a 350 KB. Toma otra foto más cercana y enfocada.');
    }

    function setInputFile(input, file) {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
    }

    function totalSelectedBytes() {
        return documentIds.reduce((total, id) => {
            return total + (document.getElementById(id)?.files?.[0]?.size ?? 0);
        }, 0);
    }

    function clearDocument(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.value = '';
        if (input.nextElementSibling) input.nextElementSibling.textContent = 'Seleccionar imagen...';

        const preview = document.getElementById(`preview_${inputId.replace('path_', '')}`);
        if (preview) {
            preview.classList.add('d-none');
            const image = preview.querySelector('img');
            if (image) image.src = '';
        }

        const oldUrl = previewUrls.get(inputId);
        if (oldUrl) URL.revokeObjectURL(oldUrl);
        previewUrls.delete(inputId);
    }

    async function processImage(input) {
        const sourceFile = input.files?.[0];
        if (!sourceFile) return;

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(sourceFile.type)) {
            notificar('error', 'Imagen no válida', 'El documento debe ser una imagen JPG, PNG o WebP.');
            clearDocument(input.id);
            return;
        }

        if (sourceFile.size > maxSourceFileBytes) {
            notificar('warning', 'Imagen demasiado grande', 'La imagen original supera 15 MB. Selecciona una imagen más pequeña.');
            clearDocument(input.id);
            return;
        }

        activeCompressions++;
        if (submitButton) submitButton.disabled = true;

        try {
            const compressedFile = await compressImage(sourceFile);
            if (input.files?.[0] !== sourceFile) return;

            setInputFile(input, compressedFile);
            const preview = document.getElementById(`preview_${input.id.replace('path_', '')}`);
            const image = preview?.querySelector('img');
            if (preview && image) {
                const oldUrl = previewUrls.get(input.id);
                if (oldUrl) URL.revokeObjectURL(oldUrl);

                const objectUrl = URL.createObjectURL(compressedFile);
                previewUrls.set(input.id, objectUrl);
                image.src = objectUrl;
                image.classList.remove('d-none');
                preview.querySelector('.file-name').textContent = `${compressedFile.name} (${Math.ceil(compressedFile.size / 1024)} KB)`;
                preview.classList.remove('d-none');
            }
            input.nextElementSibling.textContent = `${compressedFile.name} (${Math.ceil(compressedFile.size / 1024)} KB)`;

            if (totalSelectedBytes() > maxTotalFileBytes) {
                clearDocument(input.id);
                notificar('warning', 'Límite de archivos excedido', 'Los documentos superan el máximo total de 1.750 KB. Selecciona imágenes más pequeñas.');
            }
        } catch (error) {
            clearDocument(input.id);
            notificar('error', 'No se pudo procesar la imagen', error.message || 'Intenta seleccionar otra imagen.');
        } finally {
            activeCompressions--;
            if (submitButton) submitButton.disabled = activeCompressions > 0;
        }
    }

    documentIds.forEach(id => {
        document.getElementById(id)?.addEventListener('change', event => processImage(event.currentTarget));
    });

    form.querySelectorAll('.btn-remove-doc').forEach(button => {
        button.addEventListener('click', () => clearDocument(button.dataset.target));
    });

    form.addEventListener('submit', function (event) {
        if (consultaCiPendiente) {
            event.preventDefault();
            notificar('info', 'Búsqueda en curso', 'Espera a que termine la búsqueda del CI antes de guardar.');
            return;
        }

        if (activeCompressions > 0) {
            event.preventDefault();
            notificar('info', 'Procesando imágenes', 'Espera a que termine la compresión de las imágenes antes de guardar.');
            return;
        }

        if (totalSelectedBytes() > maxTotalFileBytes) {
            event.preventDefault();
            notificar('warning', 'Límite de archivos excedido', 'Los documentos superan el máximo total de 1.750 KB. Selecciona imágenes más pequeñas.');
            return;
        }

        const missingFields = [...form.querySelectorAll('[required]')].filter(element => {
            return element.type === 'file'
                ? !element.files?.length
                : !element.value || element.value.trim() === '';
        });

        if (missingFields.length) {
            event.preventDefault();
            const messages = missingFields.map(element => {
                const label = element.dataset.name
                    || form.querySelector(`label[for="${element.id}"]`)?.innerText.replace(/\*/g, '').replace(/:\s*$/, '').trim()
                    || element.name;
                return element.type === 'file'
                    ? `Te faltó adjuntar ${label}.`
                    : `Te faltó ingresar ${label.toLowerCase()}.`;
            });
            if (typeof window.itsaAlert === 'function') {
                window.itsaAlert('warning', 'Completa los campos requeridos', messages.join('\n'));
            } else {
                mostrarFallback('Completa los campos requeridos', messages.join(' '));
            }
            const firstMissing = missingFields[0];
            (firstMissing.type === 'file' ? firstMissing.closest('.card') : firstMissing)
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (firstMissing.type !== 'file') firstMissing.focus();
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
        }
    });
});