import { Controller } from '@hotwired/stimulus';
import { PushNotifications } from '@capacitor/push-notifications';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';
import LocalDbController from './local_db_controller.js';

export default class extends Controller {
    static values = {
        urlEnregistrer: String,  // /api/notification/fcm/enregistrer
        urlBadge: String,
    };

    async connect() {
        // Seulement sur device natif
        if (!Capacitor.isNativePlatform()) {
            console.log('Push notifications: plateforme web, skip.');
            return;
        }
        await this.initialiser();
    }

    async initialiser() {
        // 1. Demander la permission
        const permission = await PushNotifications.requestPermissions();
        if (permission.receive !== 'granted') {
            console.warn('Permission push refusée');
            Toast.show({
                text: "Attention, il faudrait activer la permission de notification pour les recevoir",
                duration: 'short'
            });
            return;
        }

        // 2. S'enregistrer auprès de FCM
        await PushNotifications.register();

        // 3. Récupérer le token FCM et l'envoyer au backend
        PushNotifications.addListener('registration', async (token) => {
            console.log('FCM Token:', token.value);
            await this.#envoyerTokenAuServeur(token.value);
        });

        PushNotifications.addListener('registrationError', (error) => {
            console.error('Erreur enregistrement FCM:', error);
        });

        // 4. Notification reçue en foreground
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('Notification reçue (foreground):', notification);
            // Rafraîchir le badge existant
            this.#rafraichirBadge();
            // Afficher une notification in-app
            this.#afficherNotificationInApp(notification);
        });

        // 5. Tap sur une notification (background/killed)
        PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
            const data = action.notification.data;
            if (data?.url) {
                window.location.href = data.url;
            }
            this.#rafraichirBadge();
        });
    }

    async #envoyerTokenAuServeur(fcmToken) {
        try {
            const profil = await LocalDbController.getAllFromStore('profil');
            if (!profil || profil.length === 0) return;

            const platform = Capacitor.getPlatform(); // 'ios' | 'android'

            await fetch(this.urlEnregistrerValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    slug: profil[0].slug,
                    code: profil[0].code,
                    fcmToken,
                    platform,
                }),
            });
        } catch (error) {
            console.error('Erreur envoi token FCM:', error);
        }
    }

    #rafraichirBadge() {
        const badgeEl = document.querySelector('[data-controller*="notification-badge"]');
        if (!badgeEl) return;
        const ctrl = this.application.getControllerForElementAndIdentifier(
            badgeEl, 'notification-badge'
        );
        ctrl?.rafraichir();
    }

    #afficherNotificationInApp(notification) {
        // Toast simple Bootstrap
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-primary border-0 position-fixed bottom-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${notification.title ?? ''}</strong><br>
                    ${notification.body ?? ''}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 5000 });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
}
