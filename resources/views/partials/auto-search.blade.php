@push('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-auto-search-input]').forEach((input) => {
            const form = input.closest('form');
            if (!form) {
                return;
            }

            let timeout;
            input.addEventListener('input', () => {
                window.clearTimeout(timeout);
                timeout = window.setTimeout(() => form.requestSubmit(), 400);
            });
            form.addEventListener('submit', () => window.clearTimeout(timeout));
        });
    });
</script>
@endpush
