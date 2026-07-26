function initOnglets() {
    const boutons = document.querySelectorAll('[data-onglet]');
    const panneaux = document.querySelectorAll('[data-onglet-panel]');

    boutons.forEach((bouton) => {
        bouton.addEventListener('click', () => {
            const cible = bouton.dataset.onglet;

            boutons.forEach((b) => b.setAttribute('aria-selected', String(b === bouton)));
            panneaux.forEach((panneau) => {
                panneau.hidden = panneau.dataset.ongletPanel !== cible;
            });
        });
    });
}

document.addEventListener('turbo:load', initOnglets);
