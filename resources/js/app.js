import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Swal from 'sweetalert2';
import '@fortawesome/fontawesome-free/css/all.min.css';

Alpine.plugin(collapse);
window.Alpine = Alpine;
window.Swal = Swal;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        page: false,
        consultation: false,
        printReferral: false,
        outward: false,
        diagnosis: false,
        prescription: false,
    });
});

Alpine.start();
