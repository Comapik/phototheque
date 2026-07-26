import { Controller } from '@hotwired/stimulus';

/*
 * Redimensionne les photos dans le navigateur avant l'envoi, pour ne pas
 * transmettre des originaux de plusieurs Mo (photo de téléphone, appareil
 * photo). Le traitement côté serveur (ImageProcessor) reste en place comme
 * filet de sécurité : si le navigateur ne supporte pas l'API utilisée ici,
 * le fichier original part tel quel et sera redimensionné côté serveur.
 */
const LARGEUR_MAX = 2000;
const QUALITE_JPEG = 0.85;

export default class extends Controller {
    static targets = ['fichier'];

    async soumettre(event) {
        // Deuxième passage (après re-soumission ci-dessous) : on laisse partir.
        if (this.element.dataset.redimensionnementFait === '1') {
            return;
        }

        const input = this.fichierTarget;
        if (!input.files || 0 === input.files.length) {
            return;
        }

        event.preventDefault();

        const bouton = this.element.querySelector('button[type="submit"]');
        const texteInitial = bouton ? bouton.textContent : null;
        if (bouton) {
            bouton.disabled = true;
            bouton.textContent = 'Préparation des photos…';
        }

        const dataTransfer = new DataTransfer();
        for (const fichier of input.files) {
            dataTransfer.items.add(await this.redimensionner(fichier));
        }
        input.files = dataTransfer.files;

        if (bouton) {
            bouton.textContent = texteInitial;
        }

        // Marque le formulaire comme déjà traité, puis relance une vraie
        // soumission (requestSubmit, pas submit : on veut que l'événement
        // "submit" reparte pour que la protection CSRF s'exécute).
        this.element.dataset.redimensionnementFait = '1';
        this.element.requestSubmit();
    }

    async redimensionner(fichier) {
        if (!fichier.type.startsWith('image/')) {
            return fichier;
        }

        try {
            const bitmap = await createImageBitmap(fichier, { imageOrientation: 'from-image' });
            const ratio = Math.min(1, LARGEUR_MAX / Math.max(bitmap.width, bitmap.height));

            if (1 === ratio) {
                bitmap.close?.();

                return fichier;
            }

            const largeur = Math.round(bitmap.width * ratio);
            const hauteur = Math.round(bitmap.height * ratio);

            const canvas = document.createElement('canvas');
            canvas.width = largeur;
            canvas.height = hauteur;
            canvas.getContext('2d').drawImage(bitmap, 0, 0, largeur, hauteur);
            bitmap.close?.();

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITE_JPEG));

            return blob ? new File([blob], fichier.name, { type: 'image/jpeg' }) : fichier;
        } catch {
            // API indisponible (vieux navigateur) ou fichier illisible : on
            // laisse partir l'original, le serveur fera le redimensionnement.
            return fichier;
        }
    }
}
