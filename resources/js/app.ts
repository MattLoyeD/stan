import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import router from './router';
import AppLayout from './layouts/AppLayout.vue';

import 'primeicons/primeicons.css';

const app = createApp(AppLayout);

app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.dark-mode',
            cssLayer: false,
        },
    },
});

app.use(ToastService);
app.use(ConfirmationService);
app.use(router);

app.mount('#app');
