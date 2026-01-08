import { Controller } from '@hotwired/stimulus';
import { PushNotifications } from '@capacitor/push-notifications';
import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';

export default class extends Controller {
    static values = {
        phone: String
    }

    async connect() {
        console.log("🔥 Firebase Controller connecté");

        // Initialiser Firebase Push Notifications
        if (Capacitor.isNativePlatform()) {
            await this.initializePushNotifications();
        } else {
            console.warn("⚠️ Push notifications disponibles uniquement sur mobile");
        }
    }

    async initializePushNotifications() {
        try {
            // Demander la permission
            let permStatus = await PushNotifications.checkPermissions();

            if (permStatus.receive === 'prompt') {
                permStatus = await PushNotifications.requestPermissions();
            }

            if (permStatus.receive !== 'granted') {
                console.error('❌ Permission notifications refusée');
                return;
            }

            // Enregistrer pour recevoir les notifications
            await PushNotifications.register();

            // Écouter les événements
            this.setupPushNotificationListeners();

            console.log("✅ Push notifications initialisées");

        } catch (error) {
            console.error("❌ Erreur init push notifications:", error);
        }
    }

    setupPushNotificationListeners() {
        // Token FCM reçu
        PushNotifications.addListener('registration', (token) => {
            console.log('🔑 FCM Token reçu:', token.value);
            this.saveFcmToken(token.value);
        });

        // Erreur d'enregistrement
        PushNotifications.addListener('registrationError', (error) => {
            console.error('❌ Erreur enregistrement FCM:', error);
        });

        // Notification reçue (app en foreground)
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('📬 Notification reçue:', notification);
            this.handleNotificationReceived(notification);
        });

        // Notification cliquée (app en background)
        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('👆 Notification cliquée:', notification);
            this.handleNotificationAction(notification);
        });
    }

    async handleNotificationReceived(notification) {
        const data = notification.data;

        switch (data.type) {
            case 'device_verification':
                this.showOtpDialog(data.otp);
                break;

            case 'device_transfer_request':
                this.showTransferDialog(data);
                break;

            case 'admin_device_transfer':
                // Pour l'admin uniquement
                this.showAdminTransferNotification(data);
                break;

            default:
                console.log('Notification générique:', notification);
        }
    }

    async handleNotificationAction(notification) {
        const data = notification.notification.data;

        // Rediriger selon le type de notification
        if (data.type === 'device_transfer_request') {
            this.showTransferDialog(data);
        }
    }

    showOtpDialog(otp) {
        // Afficher une dialog pour entrer l'OTP
        const otpInput = prompt(`Entrez le code OTP reçu (${otp}):`);

        if (otpInput) {
            this.verifyOtp(otpInput);
        }
    }

    async verifyOtp(otp) {
        try {
            const response = await fetch('/firebase-actions/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: this.phoneValue,
                    otp: otp
                })
            });

            const data = await response.json();

            if (data.status === 'verified') {
                alert('✅ Appareil vérifié avec succès !');
                Turbo.visit('/accueil');
            } else {
                alert('❌ Code OTP invalide');
            }

        } catch (error) {
            console.error('Erreur vérification OTP:', error);
            alert('Erreur lors de la vérification');
        }
    }

    showTransferDialog(data) {
        const message = `Quelqu'un tente de se connecter depuis un ${data.new_device_platform} (${data.new_device_model}). Autoriser ?`;

        if (confirm(message)) {
            this.approveTransfer(data);
        } else {
            this.denyTransfer();
        }
    }

    async approveTransfer(data) {
        try {
            const response = await fetch('/firebase-actions/approve-transfer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: data.phone,
                    new_device_id: data.new_device_id,
                    new_fcm_token: data.new_fcm_token
                })
            });

            const result = await response.json();

            if (result.status === 'approved') {
                alert('✅ Transfert approuvé');
            }

        } catch (error) {
            console.error('Erreur approbation transfert:', error);
        }
    }

    async denyTransfer() {
        try {
            const response = await fetch('/firebase-actions/deny/transfer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: this.phoneValue
                })
            });

            const result = await response.json();

            if (result.status === 'denied') {
                alert('❌ Transfert refusé');
            }

        } catch (error) {
            console.error('Erreur refus transfert:', error);
        }
    }

    showAdminTransferNotification(data) {
        alert(`⚠️ L'utilisateur ${data.user_phone} demande un transfert.\nCode OTP: ${data.otp}`);
    }

    async saveFcmToken(fcmToken) {
        try {
            // Stocker le token localement
            localStorage.setItem('fcm_token', fcmToken);
            console.log('💾 FCM Token sauvegardé localement');

            // Dispatcher un événement pour d'autres controllers
            window.dispatchEvent(new CustomEvent('fcm-token-ready', {
                detail: { fcmToken }
            }));

        } catch (error) {
            console.error('Erreur sauvegarde FCM token:', error);
        }
    }

    async getDeviceInfo() {
        try {
            const info = await Device.getInfo();
            const id = await Device.getId();

            return {
                deviceId: id.identifier,
                platform: info.platform,
                model: info.model || info.manufacturer,
                osVersion: info.osVersion
            };
        } catch (error) {
            console.error('Erreur récupération info device:', error);
            return {
                deviceId: this.generateFallbackDeviceId(),
                platform: 'web',
                model: navigator.userAgent,
                osVersion: 'unknown'
            };
        }
    }

    generateFallbackDeviceId() {
        // Générer un ID unique pour le web
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
    }

    // Méthode appelée par search_phone_controller
    async getDeviceInfoForAuth() {
        const deviceInfo = await this.getDeviceInfo();
        const fcmToken = localStorage.getItem('fcm_token') || '';

        return {
            device_id: deviceInfo.deviceId,
            fcm_token: fcmToken,
            device_platform: deviceInfo.platform,
            device_model: deviceInfo.model
        };
    }
}
