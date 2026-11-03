(function () {
    if (!window.Swal) {
        console.error('SweetAlert2 no se pudo cargar.');
        return;
    }

    const toast = window.Swal.mixin({
        toast: true,
        position: 'top-start',
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
        customClass: {
            popup: 'itsa-swal-toast',
        },
        didOpen: popup => {
            popup.addEventListener('mouseenter', window.Swal.stopTimer);
            popup.addEventListener('mouseleave', window.Swal.resumeTimer);
        },
    });

    window.itsaToast = function (icon, title, text) {
        return toast.fire({ icon, title, text });
    };

    window.itsaAlert = function (icon, title, text) {
        return window.Swal.fire({
            icon,
            title,
            text,
            confirmButtonText: 'Entendido',
            customClass: {
                popup: 'itsa-swal-modal',
            },
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const shownMessages = new Set();

        document.querySelectorAll('[data-sweetalert]').forEach(message => {
            const { kind, title, message: text } = message.dataset;
            const key = JSON.stringify([kind, title, text]);

            if (!shownMessages.has(key)) {
                shownMessages.add(key);

                if (kind === 'validation') {
                    window.itsaAlert('error', title, text);
                } else {
                    window.itsaToast(kind, title, text);
                }
            }

            message.remove();
        });
    });
})();
