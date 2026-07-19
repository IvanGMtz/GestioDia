import './bootstrap';
import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}

Alpine.data('pwaInstallBanner', () => ({
    visible: false,
    canPromptInstall: false,
    message: '',
    deferredPrompt: null,

    init() {
        if (localStorage.getItem('gestiodia_pwa_banner_dismissed')) {
            return;
        }

        const alreadyInstalled = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        if (alreadyInstalled) {
            return;
        }

        const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        if (isIos) {
            this.message = 'Para instalar GestioDia: pulsa Compartir y luego "Añadir a pantalla de inicio".';
            this.visible = true;
            return;
        }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferredPrompt = event;
            this.canPromptInstall = true;
            this.message = 'Instala GestioDia en tu pantalla de inicio para acceder más rápido.';
            this.visible = true;
        });
    },

    async install() {
        if (! this.deferredPrompt) {
            return;
        }

        this.deferredPrompt.prompt();
        await this.deferredPrompt.userChoice;
        this.deferredPrompt = null;
        this.visible = false;
    },

    dismiss() {
        this.visible = false;
        localStorage.setItem('gestiodia_pwa_banner_dismissed', '1');
    },
}));

Alpine.start();
