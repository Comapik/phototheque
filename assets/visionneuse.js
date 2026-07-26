function initVisionneuse() {
    const dialogue = document.getElementById('visionneuse');
    if (!dialogue) {
        return;
    }

    const image = dialogue.querySelector('img');
    const nom = dialogue.querySelector('.visionneuse__nom');
    const telechargement = dialogue.querySelector('[data-telechargement]');
    const fermer = dialogue.querySelector('[data-fermer-visionneuse]');
    const precedent = dialogue.querySelector('[data-visionneuse-precedent]');
    const suivant = dialogue.querySelector('[data-visionneuse-suivant]');

    let declencheurs = [];
    let indexCourant = -1;

    function afficher(index) {
        const declencheur = declencheurs[index];
        if (!declencheur) {
            return;
        }

        indexCourant = index;
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
        if (precedent && suivant) {
            precedent.hidden = declencheurs.length <= 1;
            suivant.hidden = declencheurs.length <= 1;
        }
    }

    document.querySelectorAll('[data-visionneuse]').forEach((declencheur) => {
        declencheur.addEventListener('click', () => {
            const groupe = declencheur.closest('.grille-photos');
            declencheurs = groupe
                ? Array.from(groupe.querySelectorAll('[data-visionneuse]'))
                : [declencheur];
            afficher(declencheurs.indexOf(declencheur));
            dialogue.showModal();
        });
    });

    precedent?.addEventListener('click', () => {
        afficher((indexCourant - 1 + declencheurs.length) % declencheurs.length);
    });
    suivant?.addEventListener('click', () => {
        afficher((indexCourant + 1) % declencheurs.length);
    });

    fermer?.addEventListener('click', () => dialogue.close());

    dialogue.addEventListener('click', (evenement) => {
        if (evenement.target === dialogue) {
            dialogue.close();
        }
    });

    dialogue.addEventListener('keydown', (evenement) => {
        if (evenement.key === 'ArrowLeft') {
            precedent?.click();
        } else if (evenement.key === 'ArrowRight') {
            suivant?.click();
        }
    });
}

document.addEventListener('turbo:load', initVisionneuse);
