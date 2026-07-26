import { Controller } from '@hotwired/stimulus';

/*
 * Redimensionne les photos dans le navigateur avant l'envoi, pour ne pas
 * transmettre des originaux de plusieurs Mo (photo de téléphone, appareil
 * photo). Le traitement côté serveur (ImageProcessor) reste en place comme
 * filet de sécurité : si le navigateur ne supporte pas l'API utilisée ici,
 * le fichier original part tel quel et sera redimensionné côté serveur.
 *
 * Pour un gros volume de photos, l'envoi est aussi découpé en petits lots
 * envoyés successivement (fetch), afin de ne pas dépasser les limites du
 * serveur (post_max_size, max_file_uploads, temps d'exécution) : voir
 * EvenementController::upload() / Admin\EvenementController::photos()
 * (en-tête "X-Upload-Lot: intermediaire"). Seul le dernier lot est soumis
 * "pour de vrai" (requestSubmit), afin de garder la redirection normale du
 * formulaire (messages, onglet actif, etc.).
 */
const LARGEUR_MAX = 2000;
const QUALITE_JPEG = 0.85;
const TAILLE_LOT = 15;

export default class extends Controller {
    static targets = ['fichier'];

    async soumettre(event) {
        // Deuxième passage (après re-soumission du dernier lot) : on laisse partir.
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
        const csrfField = this.element.querySelector('input[name="_csrf_token"]');
        const csrfToken = csrfField ? csrfField.value : '';

        const lots = this.decouperEnLots(Array.from(input.files), TAILLE_LOT);
        const erreurs = [];

        try {
            for (let i = 0; i < lots.length - 1; i++) {
                this.afficherProgression(bouton, i + 1, lots.length);

                const fichiers = [];
                for (const fichier of lots[i]) {
                    fichiers.push(await this.redimensionner(fichier));
                }

                const resultat = await this.envoyerLotIntermediaire(fichiers, csrfToken);
                erreurs.push(...(resultat.erreurs || []));
            }
        } catch (erreur) {
            if (bouton) {
                bouton.disabled = false;
                bouton.textContent = texteInitial;
            }
            alert('L\'envoi a été interrompu (problème réseau ou serveur). Merci de réessayer.');

            return;
        }

        if (erreurs.length > 0) {
            alert(erreurs.join('\n'));
        }

        this.afficherProgression(bouton, lots.length, lots.length);

        const dernierLot = lots[lots.length - 1] || [];
        const dataTransfer = new DataTransfer();
        for (const fichier of dernierLot) {
            dataTransfer.items.add(await this.redimensionner(fichier));
        }
        input.files = dataTransfer.files;

        // Marque le formulaire comme déjà traité, puis relance une vraie
        // soumission (requestSubmit, pas submit : on veut que l'événement
        // "submit" reparte pour que la protection CSRF s'exécute).
        this.element.dataset.redimensionnementFait = '1';
        this.element.requestSubmit();
    }

    afficherProgression(bouton, lotActuel, nbLots) {
        if (!bouton) {
            return;
        }

        bouton.disabled = true;
        bouton.textContent = nbLots > 1
            ? `Envoi des photos… (lot ${lotActuel}/${nbLots})`
            : 'Préparation des photos…';
    }

    decouperEnLots(fichiers, taille) {
        const lots = [];
        for (let i = 0; i < fichiers.length; i += taille) {
            lots.push(fichiers.slice(i, i + taille));
        }

        return lots.length ? lots : [[]];
    }

    async envoyerLotIntermediaire(fichiers, csrfToken) {
        const donnees = new FormData();
        for (const fichier of fichiers) {
            donnees.append('photos[]', fichier);
        }
        donnees.append('_csrf_token', csrfToken);

        const reponse = await fetch(this.element.action, {
            method: 'POST',
            body: donnees,
            headers: { 'X-Upload-Lot': 'intermediaire' },
        });

        if (!reponse.ok) {
            throw new Error(`Échec de l'envoi d'un lot (HTTP ${reponse.status})`);
        }

        return reponse.json();
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
