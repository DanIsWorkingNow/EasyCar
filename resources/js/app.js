import axios from 'axios';
import './ui';
import { mountCarViewers } from './car-viewer/loader';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ApexCharts is ~150 kB gzipped and only the consoles need it, so it is
// fetched on demand: `window.loadApexCharts().then(ApexCharts => ...)`.
window.loadApexCharts = () => import('apexcharts').then((m) => m.default);

const boot = () => mountCarViewers(document);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

// Re-scan after Livewire morphs the DOM (e.g. wire:navigate).
document.addEventListener('livewire:navigated', boot);
