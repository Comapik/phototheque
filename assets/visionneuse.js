function initVisionneuse() {
    const dialogue = document.getElementById('visionneuse');
    if (!dialogue) {
        return;
    }

    const image = dialogue.querySelector('img');
    const nom = dialogue.querySelector('.visionneuse__nom');
    const telechargement = dialogue.querySelector('[data-telechargement]');
    const fermer = dialogue.querySelector('[data-fermer-visionneuse]');

    document.querySelectorAll('[data-visionneuse]').forEach((declencheur) => {
        declencheur.addEventListener('click', () => {
            image.src = declencheur.dataset.src;
            image.alt = declencheur.dataset.nom || '';
            if (nom) {
                nom.textContent = declencheur.dataset.nom || '';
            }
            if (telechargement) {
                if (declencheur.dataset.telechargement) {
                    telechargement.href = declencheur.dataset.telechargement;
                    telechargement.hidden = false;
                } else {
                    telechargement.hidden = true;
                }
            }
            dialogue.showModal();
        });
    });

    fermer?.addEventListener('click', () => dialogue.close());

    dialogue.addEventListener('click', (evenement) => {
        if (evenement.target === dialogue) {
            dialogue.close();
        }
    });
}

document.addEventListener('DOMContentLoaded', initVisionneuse);
