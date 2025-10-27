// assets/controllers/page_preloader_controller.js

import { Controller } from '@hotwired/stimulus';

/**
 * Déclenche un lien <link rel="prefetch"> pour précharger le document cible.
 */
export default class extends Controller {
    static values = {
        // L'URL complète de la page à précharger
        url: String
    };

    connect() {
        if (!this.hasUrlValue) {
            console.warn('Preloader: Missing URL value.');
            return;
        }

        // Utiliser un léger délai pour prioriser le rendu initial de la page
        // et pour donner le temps au client Turbo Native de se stabiliser.
        // Cela réduit le risque de bloquer l'UI.
        setTimeout(() => {
            this.preloadDocument(this.urlValue);
        }, 500); // Déclenche le préchargement 500ms après l'affichage de l'élément.
    }

    preloadDocument(url) {
        // 1. Création d'un élément <link>
        const link = document.createElement('link');

        // 2. rel="prefetch" indique au navigateur que cette ressource sera probablement
        // nécessaire pour une navigation future.
        link.rel = 'prefetch';

        // 3. Définition de l'URL cible
        link.href = url;

        // 4. as="document" est un indice de priorité pour le navigateur
        link.as = 'document';

        // 5. Ajout au DOM : Le navigateur initie le téléchargement en tâche de fond.
        document.head.appendChild(link);
        console.log(`Préchargement initié pour le document : ${url}`);
    }
}
