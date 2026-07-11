import './bootstrap';
import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

const PHOTO_MAX_SIDE = 1600;
const PHOTO_JPEG_QUALITY = 0.7;

// Redimensiona y comprime la foto en el propio dispositivo antes de subirla
// (AGENT.md §7): reduce ancho de banda y disco en el hosting compartido.
// Si algo falla (navegador antiguo, etc.) se sube el archivo original tal cual.
window.compressPhotoInput = async function (input) {
    const file = input.files[0];
    if (!file) return;

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, PHOTO_MAX_SIDE / Math.max(bitmap.width, bitmap.height));

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', PHOTO_JPEG_QUALITY));
        if (!blob) return;

        const compressed = new File([blob], 'evidencia.jpg', { type: 'image/jpeg' });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressed);
        input.files = dataTransfer.files;
    } catch (error) {
        console.error('No se pudo comprimir la foto, se sube el archivo original.', error);
    }
};

Alpine.start();
