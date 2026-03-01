import { Controller } from '@hotwired/stimulus';
import LocalDbController from './local_db_controller.js';
import { Modal } from 'bootstrap';

export default class extends Controller {

    static targets = ['header', 'loading', 'liste', 'vide', 'erreur', 'item',
        'modal', 'modalTitre', 'modalMessage', 'modalDate', 'modalBadge', 'modalFooter', 'modalMeta'];

    static values = {
        urlToutes:        String,
        urlMarquerLue:    String,
        urlLogClic:       String,
        urlMarquerToutes: String,
    };

    connect() {
        this.charger();
        this.currentModal = null;
    }

    disconnect() {
        if (this.currentModal) {
            this.currentModal.dispose();
        }
    }

    async charger() {
        this.#afficher('loading');

        try {
            const profil = await this.#getProfil();
            if (!profil) return;

            const response = await fetch(this.urlToutesValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ slug: profil.slug, code: profil.code }),
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            this.#rendreListe(data.notifications ?? []);

        } catch (error) {
            console.error('Erreur chargement notifications:', error);
            this.#afficher('erreur');
        }
    }

    // ═══════════════════════════════════════════════════════════
    // NOUVEAU : Ouverture du modal au lieu de navigation
    // ═══════════════════════════════════════════════════════════

    async ouvrirModal(event) {
        event.preventDefault();

        const item = event.currentTarget;
        const notificationId = item.dataset.notificationId;

        // Récupérer les données depuis les data attributes
        const notification = {
            id: notificationId,
            titre: item.dataset.titre,
            message: item.dataset.message,
            type: item.dataset.type,
            icone: item.dataset.icone,
            urlAction: item.dataset.urlAction,
            libelleAction: item.dataset.libelleAction,
            creeLe: item.dataset.creeLe,
            estLue: item.classList.contains('notification-lue')
        };

        // Marquer comme lue si pas encore lue
        if (!notification.estLue) {
            await this.#marquerCommeLueApi(notificationId, item);
        }

        // Remplir le modal
        this.#remplirModal(notification);

        // Ouvrir le modal avec Bootstrap
        if (!this.currentModal) {
            this.currentModal = new Modal(this.modalTarget);
        }
        this.currentModal.show();
    }

    #remplirModal(notif) {
        // Titre
        this.modalTitreTarget.textContent = notif.titre;

        // Badge type
        const badgeClass = this.#getBadgeClass(notif.type);
        this.modalBadgeTarget.innerHTML = `<span class="type-badge ${badgeClass}">${this.#getTypeLabel(notif.type)}</span>`;

        // Icône + Date
        const icone = notif.icone || this.#getIconeParType(notif.type);
        const dateFormatee = this.#formaterDateComplete(notif.creeLe);
        this.modalMetaTarget.innerHTML = `
            <i class="${icone} me-2"></i>
            <small>${dateFormatee}</small>
        `;

        // Message complet
        this.modalMessageTarget.textContent = notif.message;

        // Footer avec bouton d'action
        if (notif.urlAction && notif.urlAction !== '#') {
            this.modalFooterTarget.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="${notif.urlAction}" class="btn btn-primary" data-turbo="false">
                    ${notif.libelleAction || 'Voir'}
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            `;
        } else {
            this.modalFooterTarget.innerHTML = `
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
            `;
        }
    }

    async #marquerCommeLueApi(notificationId, item) {
        try {
            const profil = await this.#getProfil();
            if (!profil) return;

            const url = this.urlMarquerLueValue.replace('__ID__', notificationId);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ slug: profil.slug, code: profil.code }),
            });

            const data = await response.json();

            if (data.success) {
                // Mise à jour visuelle
                item.classList.replace('notification-non-lue', 'notification-lue');
                item.querySelector('.badge-non-lu')?.remove();
                this.#mettreAJourBadgeHeader(data.countNonLues);
            }

        } catch (error) {
            console.error('Erreur marquage comme lu:', error);
        }
    }

    async marquerToutesLues() {
        try {
            const profil = await this.#getProfil();
            if (!profil) return;

            const response = await fetch(this.urlMarquerToutesValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ slug: profil.slug, code: profil.code }),
            });

            const data = await response.json();

            if (data.success) {
                this.itemTargets.forEach(item => {
                    if (item.classList.contains('notification-non-lue')) {
                        item.classList.replace('notification-non-lue', 'notification-lue');
                    }
                    item.querySelector('.badge-non-lu')?.remove();
                });
                this.#mettreAJourBadgeHeader(0);
            }

        } catch (error) {
            console.error('Erreur marquage toutes comme lues:', error);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Rendu HTML de la liste
    // ═══════════════════════════════════════════════════════════

    #rendreListe(notifications) {
        if (notifications.length === 0) {
            this.#afficher('vide');
            return;
        }

        this.listeTarget.innerHTML = notifications
            .map(notif => this.#rendreItem(notif))
            .join('');

        this.#afficher('liste');
    }

    #rendreItem(notif) {
        const classeEtat    = notif.estLue ? 'notification-lue' : 'notification-non-lue';
        const badgeNouv     = !notif.estLue
            ? `<span class="badge bg-primary badge-non-lu ms-2">Nouveau</span>`
            : '';
        const icone         = this.#rendreIcone(notif);
        const message       = this.#escapeHtml(notif.message ?? '');
        const messageShort  = message.length > 120 ? message.slice(0, 120) + '…' : message;
        const temps = this.#formaterTemps(notif.creeLe);

        // ✅ On met toutes les données dans des data attributes pour le modal
        return `
            <div class="list-group-item list-group-item-action ${classeEtat} position-relative"
                 data-notification-id="${notif.id}"
                 data-titre="${this.#escapeHtml(notif.titre ?? '')}"
                 data-message="${this.#escapeHtml(notif.message ?? '')}"
                 data-type="${notif.type}"
                 data-icone="${notif.icone || ''}"
                 data-url-action="${notif.urlAction || '#'}"
                 data-libelle-action="${this.#escapeHtml(notif.libelleAction ?? '')}"
                 data-cree-le="${notif.creeLe}"
                 data-notification-list-target="item"
                 data-action="click->notification-list#ouvrirModal"
                 role="button"
                 tabindex="0">

                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        ${icone}
                        <h6 class="mb-1 d-inline">${this.#escapeHtml(notif.titre ?? '')}</h6>
                        ${badgeNouv}
                    </div>
                    <small class="text-muted text-nowrap ms-2">${temps}</small>
                </div>

                <p class="mb-1 mt-2 text-muted fsize-13">${messageShort}</p>
            </div>
        `;
    }

    #rendreIcone(notif) {
        if (notif.icone) {
            return `<i class="${this.#escapeHtml(notif.icone)} me-2"></i>`;
        }
        const cls = this.#getIconeParType(notif.type);
        return `<i class="${cls} me-2"></i>`;
    }

    #getIconeParType(type) {
        const map = {
            success: 'bi bi-check-circle-fill text-success',
            warning: 'bi bi-exclamation-triangle-fill text-warning',
            danger:  'bi bi-exclamation-circle-fill text-danger',
        };
        return map[type] ?? 'bi bi-info-circle-fill text-info';
    }

    #getBadgeClass(type) {
        const map = {
            info:    'bg-info text-dark',
            success: 'bg-success text-white',
            warning: 'bg-warning text-dark',
            danger:  'bg-danger text-white',
        };
        return map[type] ?? 'bg-secondary text-white';
    }

    #getTypeLabel(type) {
        const map = {
            info: 'Information',
            success: 'Succès',
            warning: 'Avertissement',
            danger: 'Important',
        };
        return map[type] ?? 'Notification';
    }

    // ═══════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════

    #afficher(zone) {
        ['loading', 'liste', 'vide', 'erreur'].forEach(t => {
            const cible = `${t}Target`;
            if (this[`has${this.#capitalise(t)}Target`]) {
                this[cible].style.display = 'none';
            }
        });

        if (this[`has${this.#capitalise(zone)}Target`]) {
            this[`${zone}Target`].style.display = 'block';
        }

        if (this.hasHeaderTarget) {
            this.headerTarget.style.display = (zone === 'liste') ? 'flex' : 'none';
        }
    }

    #mettreAJourBadgeHeader(count) {
        const badgeEl = document.querySelector('[data-controller*="notification-badge"]');
        if (!badgeEl) return;

        const ctrl = this.application.getControllerForElementAndIdentifier(
            badgeEl, 'notification-badge'
        );
        ctrl?.mettreAJourBadge(count);
    }

    async #getProfil() {
        const profils = await LocalDbController.getAllFromStore('profil');
        if (!profils || profils.length === 0) {
            console.warn('Aucun profil local trouvé.');
            this.#afficher('erreur');
            return null;
        }
        return profils[0];
    }

    #formaterTemps(dateString) {
        if (!dateString) return '';
        const diff = Math.floor((Date.now() - new Date(dateString)) / 60000);
        if (diff < 1)    return "À l'instant";
        if (diff < 60)   return `Il y a ${diff} min`;
        if (diff < 1440) return `Il y a ${Math.floor(diff / 60)} h`;
        return `Il y a ${Math.floor(diff / 1440)} j`;
    }

    #formaterDateComplete(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    #escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    #capitalise(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}
