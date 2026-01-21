import { Controller } from '@hotwired/stimulus';
import { PushNotifications } from '@capacitor/push-notifications';
import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';
import { LocalNotifications } from '@capacitor/local-notifications';

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
            // 1. Demander les permissions pour les notifications locales aussi
            await LocalNotifications.requestPermissions();

            // 2. Demander la permission pour push notifications
            let permStatus = await PushNotifications.checkPermissions();
            console.log('📋 Permissions actuelles:', permStatus);

            if (permStatus.receive === 'prompt') {
                console.log('🔔 Demande de permissions...');
                permStatus = await PushNotifications.requestPermissions();
            }

            if (permStatus.receive !== 'granted') {
                console.error('❌ Permission notifications refusée');
                Toast.show({
                    text: '❌ Permissions refusées. Activez-les dans les paramètres.',
                    duration: 'long'
                });
                return;
            }

            console.log('✅ Permissions accordées');

            // 3. Enregistrer pour recevoir les notifications
            await PushNotifications.register();
            console.log('📝 Enregistrement push notifications réussi');

            // 4. Configurer les listeners
            this.setupPushNotificationListeners();

            console.log("✅ Push notifications initialisées");
            Toast.show({
                text: '✅ Notifications activées',
                duration: 'short'
            });

        } catch (error) {
            console.error("❌ Erreur init push notifications:", error);
            Toast.show({
                text: `❌ Erreur: ${error.message}`,
                duration: 'long',
                position: 'bottom'
            });
        }
    }

    setupPushNotificationListeners() {
        console.log('🎧 Configuration des listeners...');

        // ========================================
        // 1. TOKEN FCM REÇU
        // ========================================
        PushNotifications.addListener('registration', (token) => {
            console.log('🔑 ===== FCM TOKEN REÇU =====');
            console.log('Token:', token.value);
            console.log('Longueur:', token.value.length);
            console.log('============================');

            Toast.show({
                text: '🔑 Token Firebase reçu',
                duration: 'short'
            });

            this.saveFcmToken(token.value);
        });

        // ========================================
        // 2. ERREUR D'ENREGISTREMENT
        // ========================================
        PushNotifications.addListener('registrationError', (error) => {
            console.error('❌ ===== ERREUR ENREGISTREMENT FCM =====');
            console.error('Error:', error);
            console.error('========================================');

            Toast.show({
                text: '❌ Erreur Firebase: ' + error.error,
                duration: 'long'
            });
        });

        // ========================================
        // 3. NOTIFICATION REÇUE (APP EN FOREGROUND)
        // ========================================
        PushNotifications.addListener('pushNotificationReceived', async (notification) => {
            console.log('📬 ===== NOTIFICATION REÇUE (FOREGROUND) =====');
            console.log('Notification complète:', notification);
            console.log('Titre:', notification.title);
            console.log('Body:', notification.body);
            console.log('Data:', notification.data);
            console.log('=============================================');

            // AFFICHER UNE NOTIFICATION LOCALE
            // Car les push notifications en foreground ne s'affichent pas automatiquement
            try {
                await LocalNotifications.schedule({
                    notifications: [
                        {
                            title: notification.title || '🔔 Nouvelle notification',
                            body: notification.body || 'Vous avez reçu une notification',
                            id: Date.now(),
                            schedule: { at: new Date(Date.now() + 100) }, // Afficher immédiatement
                            sound: 'default',
                            smallIcon: 'ic_notification',
                            actionTypeId: '',
                            extra: notification.data
                        }
                    ]
                });

                console.log('✅ Notification locale affichée');

                // AFFICHER AUSSI UN TOAST
                Toast.show({
                    text: notification.title || 'Nouvelle notification',
                    duration: 'long',
                    position: 'top'
                });

            } catch (error) {
                console.error('❌ Erreur affichage notification locale:', error);
            }

            // Gérer le contenu de la notification
            this.handleNotificationReceived(notification);
        });

        // ========================================
        // 4. NOTIFICATION CLIQUÉE (APP EN BACKGROUND)
        // ========================================
        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('👆 ===== NOTIFICATION CLIQUÉE (BACKGROUND) =====');
            console.log('Action:', notification);
            console.log('Notification:', notification.notification);
            console.log('Data:', notification.notification.data);
            console.log('===============================================');

            Toast.show({
                text: '📱 Notification ouverte',
                duration: 'short'
            });

            this.handleNotificationAction(notification);
        });

        console.log('✅ Tous les listeners configurés');
    }

    async handleNotificationReceived(notification) {
        const data = notification.data;

        console.log('🔍 Traitement notification, type:', data.type);

        switch (data.type) {
            case 'device_verification':
                console.log('🔐 Type: device_verification');
                console.log('OTP dans notification:', data.otp);

                // Afficher le code OTP de manière visible
                await this.showOtpNotification(data.otp);
                break;

            case 'device_transfer_request':
                console.log('📱 Type: device_transfer_request');
                this.showTransferDialog(data);
                break;

            case 'admin_device_transfer':
                console.log('👨‍💼 Type: admin_device_transfer');
                this.showAdminTransferNotification(data);
                break;

            default:
                console.log('📌 Type: générique');
                console.log('Notification:', notification);
        }
    }

    async showOtpNotification(otp) {
        console.log('📢 Affichage OTP:', otp);

        // 1. Afficher un toast permanent
        Toast.show({
            text: `🔑 Code OTP: ${otp}`,
            duration: 'long',
            position: 'center'
        });

        // 2. Afficher une alerte native
        if (confirm(`🔐 Code de vérification reçu !\n\nVotre code OTP est : ${otp}\n\nVoulez-vous l'utiliser maintenant ?`)) {
            // Rediriger vers la page de vérification ou auto-remplir
            console.log('✅ Utilisateur a confirmé l\'OTP');

            // Si vous êtes sur la page de connexion, auto-remplir
            const otpInput = document.getElementById('otpInput');
            if (otpInput) {
                otpInput.value = otp;
                console.log('✅ OTP auto-rempli dans le champ');
            }
        }

        // 3. Sauvegarder l'OTP pour utilisation ultérieure
        localStorage.setItem('last_otp', otp);
        localStorage.setItem('last_otp_time', Date.now().toString());
    }

    async handleNotificationAction(notification) {
        const data = notification.notification.data;

        console.log('🎬 Action sur notification, type:', data.type);

        // Rediriger selon le type de notification
        if (data.type === 'device_transfer_request') {
            this.showTransferDialog(data);
        } else if (data.type === 'device_verification') {
            // Ouvrir l'app sur la page de vérification
            console.log('📱 Ouverture pour vérification OTP');

            // Si l'app est déjà sur la page, auto-remplir l'OTP
            const otpInput = document.getElementById('otpInput');
            if (otpInput && data.otp) {
                otpInput.value = data.otp;
                Toast.show({
                    text: '✅ Code OTP auto-rempli',
                    duration: 'short'
                });
            }
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
                Toast.show({
                    text: '✅ Transfert approuvé',
                    duration: 'short'
                });
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
                Toast.show({
                    text: '❌ Transfert refusé',
                    duration: 'short'
                });
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
            console.log('Aperçu:', fcmToken.substring(0, 30) + '...');

            Toast.show({
                text: '💾 Token Firebase sauvegardé',
                duration: 'short'
            });

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
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
    }

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
