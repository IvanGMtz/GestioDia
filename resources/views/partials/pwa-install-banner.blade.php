<div x-data="pwaInstallBanner()" x-show="visible" x-cloak
     class="d-flex justify-content-between align-items-center gap-3 p-3"
     style="background: var(--gd-green-primary); color: #FFFFFF;">
    <span class="fw-medium" x-text="message"></span>
    <div class="d-flex align-items-center gap-2 text-nowrap">
        <button type="button" x-show="canPromptInstall" @click="install" class="btn btn-light btn-sm">Instalar</button>
        <button type="button" @click="dismiss" class="btn-close btn-close-white" aria-label="Cerrar"></button>
    </div>
</div>
