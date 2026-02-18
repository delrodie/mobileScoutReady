import { Controller } from '@hotwired/stimulus';
import LocalDbController from './local_db_controller.js';

export default class extends Controller {
    static targets = ['count'];
    static values = {
        url: String,
        refreshInterval: { type: Number, default: 30000 } // 30 secondes
    };

    connect() {
        this.chargerCompteur();
        this.demarrerPolling();
    }

    disconnect() {
        this.arreterPolling();
    }

    async chargerCompteur() {
        try {
            const profil = await LocalDbController.getAllFromStore('profil');
            if (!profil || profil.length === 0) {
                console.warn("Aucun profil local trouvé.");
                return;
            }

            console.log('Notification badge');

            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    slug: profil[0].slug,
                    code: profil[0].code,
                })
            });

            const data = await response.json();
            console.warn('*** NOTIFICATION ***')
            console.log(data)
            this.mettreAJourBadge(data.count);
        } catch (error) {
            console.error('Erreur chargement compteur notifications:', error);
        }
    }

    mettreAJourBadge(count) {
        if (this.hasCountTarget) {
            this.countTarget.textContent = count;

            // Affiche/masque le badge selon le count
            if (count > 0) {
                this.countTarget.style.display = 'inline-block';
            } else {
                this.countTarget.style.display = 'none';
            }
        }

        // Émet un événement personnalisé pour d'autres controllers
        this.dispatch('countUpdated', { detail: { count } });
    }

    demarrerPolling() {
        this.pollingInterval = setInterval(() => {
            this.chargerCompteur();
        }, this.refreshIntervalValue);
    }

    arreterPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
    }

    // Méthode appelée par d'autres controllers pour forcer le refresh
    rafraichir() {
        this.chargerCompteur();
    }
}
