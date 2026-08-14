import './bootstrap';
import Swal from 'sweetalert2';
import '@fortawesome/fontawesome-free/css/all.min.css';

window.Swal = Swal;

window.modalMerge = (extra) => ({ open: false, ...(extra ?? {}) });

window.administerVaccine = () => ({
    adminOpen: false,
    vaccineId: null,
    vaccineName: '',
    doseNumber: null,
    requiresTemp: false,
    outOfWindow: false,
    init() {
        this.$watch('adminOpen', (open) => {
            document.body.classList.toggle('overflow-hidden', open);
        });
    },
    initReopen(hasErrors) {
        if (!hasErrors) return;
        this.vaccineId = Number(document.querySelector('input[name="vaccine_id"]')?.value) || null;
        this.vaccineName = '';
        this.requiresTemp = false;
        this.outOfWindow = false;
        this.adminOpen = true;
    },
    openAdminister(detail) {
        this.vaccineId = detail.vaccineId ?? null;
        this.vaccineName = detail.vaccineName ?? '';
        this.doseNumber = detail.doseNumber ?? null;
        this.requiresTemp = Boolean(detail.requiresTemp);
        this.outOfWindow = Boolean(detail.outOfWindow);
        this.adminOpen = true;
        this.$nextTick(() => document.getElementById('administer_date')?.focus());
    },
});

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
