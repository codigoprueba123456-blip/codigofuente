@php($src = $src ?? null)
@php($initialFilename = $filename ?? ($src ? basename(parse_url($src, PHP_URL_PATH)) : null))
<div id="{{ $id }}" class="mt-2">
    <img
        src="{{ $src ?? '' }}"
        alt="{{ $alt }}"
        class="img-thumbnail"
        data-initial-src="{{ $src ?? '' }}"
        style="max-height: 180px; {{ empty($src) ? 'display: none;' : '' }}"
    >
    <div class="text-muted small mt-1" data-image-placeholder {{ !empty($src) ? 'hidden' : '' }}>
        {{ $emptyLabel ?? 'Sin imagen guardada' }}
    </div>
    <div class="small mt-1">
        <strong>Archivo:</strong>
        <span data-image-filename data-initial-filename="{{ $initialFilename ?? '' }}">
            {{ $initialFilename ?? 'Ningún archivo seleccionado' }}
        </span>
    </div>
</div>

@once
    @push('js')
        <script>
            document.addEventListener('change', function (event) {
                const input = event.target;
                if (!(input instanceof HTMLInputElement) || !input.matches('[data-image-preview]')) {
                    return;
                }

                const preview = document.getElementById(input.dataset.imagePreview);
                const image = preview?.querySelector('img');
                const filename = preview?.querySelector('[data-image-filename]');
                const placeholder = preview?.querySelector('[data-image-placeholder]');
                if (!image) {
                    return;
                }

                if (image.dataset.objectUrl) {
                    URL.revokeObjectURL(image.dataset.objectUrl);
                    delete image.dataset.objectUrl;
                }

                const file = input.files?.[0];
                if (!file || !file.type.startsWith('image/')) {
                    image.src = image.dataset.initialSrc;
                    image.style.display = image.dataset.initialSrc ? 'block' : 'none';
                    if (filename) {
                        filename.textContent = filename.dataset.initialFilename || 'Ningún archivo seleccionado';
                    }
                    if (placeholder) {
                        placeholder.hidden = Boolean(image.dataset.initialSrc);
                    }
                    return;
                }

                const objectUrl = URL.createObjectURL(file);
                image.dataset.objectUrl = objectUrl;
                image.src = objectUrl;
                image.style.display = 'block';
                if (filename) {
                    filename.textContent = file.name;
                }
                if (placeholder) {
                    placeholder.hidden = true;
                }
            });
        </script>
    @endpush
@endonce
