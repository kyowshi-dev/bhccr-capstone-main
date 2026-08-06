import './bootstrap';
import Swal from 'sweetalert2';
import '@fortawesome/fontawesome-free/css/all.min.css';

window.Swal = Swal;

document.addEventListener('alpine:init', () => {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.store('modals', {
        page: false,
        consultation: false,
        printReferral: false,
        outward: false,
        diagnosis: false,
        prescription: false,
    });
});
