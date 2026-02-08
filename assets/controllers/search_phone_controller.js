import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';

/**
 * Contrôleur de connexion avec SMS OTP
 * Fusionne la logique DTO existante et la vérification de device
 */
export default class extends Controller {
    static targets = ['form', 'phone', 'otpContainer', 'otpInput'];

    get firebaseSms() {
        const element = document.querySelector('[data-controller="firebase-sms"]');
        return element ? this.application.getControllerForElementAndIdentifier(element, 'firebase-sms') : null;
    }

    async submit(event) {
        event.preventDefault();

        const phoneNumber = this.phoneTarget.value;

        // Récupération des infos du device via le contrôleur firebase-sms ou fallback
        let deviceInfo;
        if (this.firebaseSms) {
            deviceInfo = await this.firebaseSms.getDeviceInfo();
        } else {
            deviceInfo = {
                deviceId: localStorage.getItem('device_id') || 'web_' + Date.now(),
                platform: 'web',
                model: navigator.userAgent
            };
        }

        try {
            const response = await fetch('/intro/phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: phoneNumber,
                    device_id: deviceInfo.deviceId,
                    device_platform: deviceInfo.platform,
                    device_model: deviceInfo.model
                })
            });

            // GESTION CRUCIALE : Si le serveur renvoie du HTML (Erreur 500 ou Turbo Frame)
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") === -1) {
                const text = await response.text();

                // Si c'est un Turbo-Frame d'erreur (ton code PHP original)
                if (text.includes('turbo-frame')) {
                    const frame = document.querySelector('turbo-frame#search_results');
                    if (frame) frame.innerHTML = text;
                    return;
                }

                console.error("Le serveur a renvoyé du HTML au lieu de JSON. Vérifiez la console PHP.");
                return;
            }

            const data = await response.json();

            // CAS 1 : Nouveau Device -> Lancer le SMS
            if (data.status === 'new_device' || data.requires_otp) {
                this.startSmsVerification(data.phone);
                return;
            }

            // CAS 2 : Accès Direct -> Charger les DTO et rediriger
            if (data.status === 'ok') {
                await this.handleSuccessfulLogin(data);
            }

        } catch (error) {
            console.error('Erreur lors de la soumission:', error);
            Toast.show({ text: 'Erreur de connexion au serveur' });
        }
    }

    /**
     * Affiche l'interface SMS
     */
    startSmsVerification(phone) {
        if (this.firebaseSms) {
            this.formTarget.classList.add('d-none');
            this.otpContainerTarget.classList.remove('d-none');
            this.firebaseSms.sendOtp(phone);
        }
    }

    /**
     * Gère la connexion réussie et sauvegarde les DTO
     */
    async handleSuccessfulLogin(data) {
        // Sauvegarde dans IndexedDB (ProfilDTO, ChampsDTO, etc.)
        await LoadDbController.saveToIndexedDB(data);

        if (data.profil && data.profil.isParent === true) {
            sessionStorage.setItem('_phone_input', this.phoneTarget.value);
            Turbo.visit('/intro/choix/profil');
        } else {
            Toast.show({ text: '✅ Connexion réussie' });
            Turbo.visit('/accueil');
        }
    }
}
