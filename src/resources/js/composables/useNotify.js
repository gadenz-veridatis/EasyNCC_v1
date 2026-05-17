import Swal from 'sweetalert2';

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
});

export function useNotify() {
    /**
     * Toast di successo auto-dismiss (2.5s)
     */
    function success(message) {
        Toast.fire({ icon: 'success', title: message });
    }

    /**
     * Toast di errore - rimane visibile più a lungo (4s)
     */
    function error(message) {
        Toast.fire({ icon: 'error', title: message, timer: 4000 });
    }

    /**
     * Toast di warning auto-dismiss (3s)
     */
    function warning(message) {
        Toast.fire({ icon: 'warning', title: message, timer: 3000 });
    }

    /**
     * Toast informativo auto-dismiss (2.5s)
     */
    function info(message) {
        Toast.fire({ icon: 'info', title: message });
    }

    /**
     * Modale di conferma - ritorna Promise<boolean>
     */
    async function confirm(title, message, options = {}) {
        const result = await Swal.fire({
            title: title,
            html: message,
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#d33',
            cancelButtonColor: options.cancelColor || '#6c757d',
            confirmButtonText: options.confirmText || 'Conferma',
            cancelButtonText: options.cancelText || 'Annulla',
            reverseButtons: true,
        });
        return result.isConfirmed;
    }

    /**
     * Modale informativa con conferma (per azioni con side-effect)
     */
    async function confirmInfo(title, message, options = {}) {
        return confirm(title, message, {
            icon: 'info',
            confirmColor: options.confirmColor || '#3085d6',
            confirmText: options.confirmText || 'Sì, procedi',
            cancelText: options.cancelText || 'Annulla',
            ...options,
        });
    }

    return { success, error, warning, info, confirm, confirmInfo };
}
