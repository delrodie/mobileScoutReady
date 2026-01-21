import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';
import { PushNotifications } from '@capacitor/push-notifications';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';

export default class extends Controller {
    static targets = ['form', 'phone', 'permissionStatus'];

    async connect() {
        console.log('🔌 Search Phone Controller connecté');

        // Vérifier l'état des permissions au chargement
        await this.checkNotificationPermissions();

        // Écouter l'événement de token prêt
        window.addEventListener('fcm-token-ready', (e) => {
            console.log('✅ Token FCM prêt:', e.detail.fcmToken);
            this.updatePermissionStatus('granted');
        });
    }

    async checkNotificationPermissions() {
        if (!Capacitor.isNativePlatform()) {
            console.log('📱 Web platform - pas de vérification native');
            this.updatePermissionStatus('web');
            return;
        }

        try {
            const permStatus = await PushNotifications.checkPermissions();
            console.log('🔍 Statut permissions:', permStatus);

            this.updatePermissionStatus(permStatus.receive);

            if (permStatus.receive === 'denied') {
                this.showPermissionDeniedWarning();
            } else if (permStatus.receive === 'prompt') {
                this.showPermissionPrompt();
            } else if (permStatus.receive === 'granted') {
                // Vérifier si le token existe déjà
                const token = localStorage.getItem('fcm_token');
                if (!token) {
                    console.warn('⚠️ Permission accordée mais pas de token');
                    await this.requestNotificationSetup();
                }
            }

        } catch (error) {
            console.error('❌ Erreur vérification permissions:', error);
            Toast.show({
                text: '⚠️ Impossible de vérifier les permissions',
                duration: 'long'
            });
        }
    }

    updatePermissionStatus(status) {
        // Mettre à jour l'interface si un élément de statut existe
        if (this.hasPermissionStatusTarget) {
            const statusMessages = {
                'granted': '✅ Notifications activées',
                'denied': '❌ Notifications désactivées',
                'prompt': '⏸️ En attente d\'autorisation',
                'web': '🌐 Mode web (notifications limitées)'
            };

            const statusColors = {
                'granted': 'success',
                'denied': 'danger',
                'prompt': 'warning',
                'web': 'info'
            };

            this.permissionStatusTarget.innerHTML = `
                <div class="alert alert-${statusColors[status]} alert-sm">
                    ${statusMessages[status]}
                </div>
            `;
        }
    }

    showPermissionDeniedWarning() {
        const message = `
            ❌ Les notifications sont désactivées.

            Pour recevoir les codes OTP, veuillez :
            1. Aller dans les paramètres de l'application
            2. Activer les notifications
            3. Redémarrer l'application
        `;

        if (confirm(message + '\n\nOuvrir les paramètres maintenant ?')) {
            // Sur certaines plateformes, on peut ouvrir les paramètres
            Toast.show({
                text: 'Veuillez activer les notifications dans les paramètres',
                duration: 'long'
            });
        }
    }

    async showPermissionPrompt() {
        const shouldRequest = confirm(
            '📱 Cette application a besoin des notifications pour vous envoyer des codes de vérification.\n\n' +
            'Autoriser les notifications ?'
        );

        if (shouldRequest) {
            await this.requestNotificationSetup();
        }
    }

    async requestNotificationSetup() {
        try {
            Toast.show({
                text: '⏳ Configuration des notifications...',
                duration: 'short'
            });

            const permStatus = await PushNotifications.requestPermissions();

            if (permStatus.receive === 'granted') {
                console.log('✅ Permission accordée');
                await PushNotifications.register();

                Toast.show({
                    text: '✅ Notifications activées',
                    duration: 'short'
                });

                this.updatePermissionStatus('granted');
            } else {
                console.warn('⚠️ Permission refusée');
                this.updatePermissionStatus('denied');
                this.showPermissionDeniedWarning();
            }

        } catch (error) {
            console.error('❌ Erreur demande permissions:', error);
            Toast.show({
                text: '❌ Erreur lors de la configuration',
                duration: 'long'
            });
        }
    }

    async submit(event) {
        event.preventDefault();

        // 🔥 Vérifier les permissions AVANT la soumission
        const permissionsOk = await this.ensureNotificationsEnabled();

        if (!permissionsOk) {
            console.warn('⚠️ Soumission annulée - permissions manquantes');
            return;
        }

        const form = this.formTarget;
        const formData = new FormData(form);

        // 🔥 Récupérer les infos du device depuis le firebase controller
        const firebaseController = this.application.getControllerForElementAndIdentifier(
            document.body,
            'firebase'
        );

        console.log('🔥 Firebase Controller:', firebaseController);

        let deviceInfo = {
            device_id: this.getOrCreateDeviceId(),
            fcm_token: localStorage.getItem('fcm_token') || '',
            device_platform: 'web',
            device_model: navigator.userAgent
        };

        console.log('📱 Device Info initial:', deviceInfo);

        // Si Firebase controller existe, récupérer les vraies infos
        if (firebaseController) {
            try {
                deviceInfo = await firebaseController.getDeviceInfoForAuth();
                console.log('📱 Device Info depuis Firebase:', deviceInfo);
            } catch (error) {
                console.error('❌ Erreur récupération device info:', error);
            }
        }

        // ⚠️ Vérifier que le token n'est pas vide
        if (!deviceInfo.fcm_token && Capacitor.isNativePlatform()) {
            console.error('❌ Token FCM manquant!');

            const retry = confirm(
                '⚠️ Impossible de récupérer le token de notification.\n\n' +
                'Cela peut arriver si :\n' +
                '- Les notifications ne sont pas activées\n' +
                '- L\'application n\'est pas connectée à Firebase\n\n' +
                'Voulez-vous réessayer ?'
            );

            if (retry) {
                await this.requestNotificationSetup();
                // Attendre 2 secondes pour laisser le temps au token d'arriver
                await new Promise(resolve => setTimeout(resolve, 2000));
                deviceInfo.fcm_token = localStorage.getItem('fcm_token') || '';

                if (!deviceInfo.fcm_token) {
                    alert('❌ Toujours impossible de récupérer le token.\nVeuillez redémarrer l\'application.');
                    return;
                }
            } else {
                return;
            }
        }

        // Ajouter les infos device au FormData
        formData.append('device_id', deviceInfo.device_id);
        formData.append('fcm_token', deviceInfo.fcm_token);
        formData.append('device_platform', deviceInfo.device_platform);
        formData.append('device_model', deviceInfo.device_model);

        console.log('📤 Envoi au serveur:', {
            device_id: deviceInfo.device_id,
            fcm_token: deviceInfo.fcm_token ? `${deviceInfo.fcm_token.substring(0, 20)}...` : 'VIDE',
            device_platform: deviceInfo.device_platform,
            device_model: deviceInfo.device_model
        });

        try {
            Toast.show({
                text: '⏳ Connexion en cours...',
                duration: 'short'
            });

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error("Erreur serveur");

            const data = await response.json();

            if (data.status === 'nouveau'){
                Turbo.visit('/inscription');
                return;
            }

            console.log("✅ Données reçues du backend:", data);

            // 🔥 Vérifier le statut du device
            if (data.device_check) {
                const deviceCheck = data.device_check;

                switch (deviceCheck.status) {
                    case 'verification_required':
                        // Premier device ou nouveau device → attendre OTP
                        Toast.show({
                            text: '📬 Code OTP envoyé',
                            duration: 'long'
                        });
                        this.showOtpVerificationDialog(data.profil.telephone);
                        return;

                    case 'new_device':
                        // Nouveau device détecté → attendre approbation
                        Toast.show({
                            text: '🔔 Notification envoyée sur votre ancien appareil',
                            duration: 'long'
                        });
                        this.showNewDeviceDialog(deviceCheck);
                        return;

                    case 'ok':
                        // Device vérifié → continuer normalement
                        Toast.show({
                            text: '✅ Connexion réussie',
                            duration: 'short'
                        });
                        break;

                    default:
                        console.warn('Statut device inconnu:', deviceCheck.status);
                }
            }

            if (data.profil.isParent === true){
                console.log("Profile parent");
                Turbo.visit('/intro/choix/profil');
                return;
            }

            await LoadDbController.saveToIndexedDB(data);

            // Redirection vers l'accueil après succès
            Turbo.visit('/accueil');

        } catch (error) {
            console.error("❌ Erreur lors de la soumission :", error);
            Toast.show({
                text: '❌ Erreur de connexion',
                duration: 'long'
            });
            alert("Une erreur est survenue. Vérifiez votre connexion.");
        }
    }

    async ensureNotificationsEnabled() {
        if (!Capacitor.isNativePlatform()) {
            // Sur web, on continue sans vérification
            return true;
        }

        try {
            const permStatus = await PushNotifications.checkPermissions();

            if (permStatus.receive === 'granted') {
                return true;
            }

            if (permStatus.receive === 'prompt') {
                const requested = await PushNotifications.requestPermissions();
                if (requested.receive === 'granted') {
                    await PushNotifications.register();
                    // Attendre que le token arrive
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    return true;
                }
            }

            if (permStatus.receive === 'denied') {
                const openSettings = confirm(
                    '❌ Les notifications sont désactivées.\n\n' +
                    'Vous ne pourrez pas recevoir les codes de vérification.\n\n' +
                    'Activer les notifications dans les paramètres ?'
                );

                if (openSettings) {
                    Toast.show({
                        text: 'Veuillez activer les notifications puis redémarrer l\'app',
                        duration: 'long',
                        position: 'center'
                    });
                }

                return false;
            }

            return false;

        } catch (error) {
            console.error('❌ Erreur vérification permissions:', error);
            // En cas d'erreur, on continue quand même (pour le web)
            return true;
        }
    }

    showOtpVerificationDialog(phoneNumber) {
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">🔐 Vérification de l'appareil</h5>
                    </div>
                    <div class="modal-body">
                        <p>Un code OTP a été envoyé sur votre appareil.</p>
                        <div class="mb-3">
                            <label for="otpInput" class="form-label">Entrez le code OTP :</label>
                            <input type="text" class="form-control" id="otpInput"
                                   maxlength="6" placeholder="000000" autofocus>
                        </div>
                        <div class="alert alert-info" role="alert">
                            ⏱️ Code valide pendant 10 minutes
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" id="verifyOtpBtn">Vérifier</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Event listeners
        document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
            const otp = document.getElementById('otpInput').value;
            await this.verifyOtp(phoneNumber, otp);
            document.body.removeChild(modal);
        });

        modal.querySelector('[data-dismiss="modal"]').addEventListener('click', () => {
            document.body.removeChild(modal);
        });
    }

    showNewDeviceDialog(deviceCheck) {
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">📱 Nouvel appareil détecté</h5>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            <strong>⚠️ Attention</strong><br>
                            ${deviceCheck.message}
                        </div>
                        <p>Veuillez approuver la connexion depuis votre ancien appareil.</p>
                        ${deviceCheck.show_no_access_option ? `
                            <hr>
                            <p class="text-muted small">
                                Vous n'avez plus accès à votre ancien téléphone ?
                            </p>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        ${deviceCheck.show_no_access_option ? `
                            <button type="button" class="btn btn-warning" id="noAccessBtn">
                                Je n'ai plus accès à l'ancien téléphone
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-primary" id="waitApprovalBtn">
                            En attente d'approbation...
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Event listeners
        if (deviceCheck.show_no_access_option) {
            document.getElementById('noAccessBtn').addEventListener('click', async () => {
                await this.handleNoAccessToOldDevice();
                document.body.removeChild(modal);
            });
        }

        modal.querySelector('[data-dismiss="modal"]').addEventListener('click', () => {
            document.body.removeChild(modal);
        });

        // Polling pour vérifier si le transfert a été approuvé
        this.pollTransferApproval();
    }

    async verifyOtp(phoneNumber, otp) {
        try {
            const response = await fetch('/firebase-actions/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: phoneNumber,
                    otp: otp
                })
            });

            const data = await response.json();

            if (data.status === 'verified') {
                Toast.show({
                    text: '✅ Appareil vérifié',
                    duration: 'short'
                });
                window.location.reload();
            } else {
                Toast.show({
                    text: '❌ Code OTP invalide',
                    duration: 'long'
                });
            }

        } catch (error) {
            console.error('Erreur vérification OTP:', error);
            Toast.show({
                text: '❌ Erreur de vérification',
                duration: 'long'
            });
        }
    }

    async handleNoAccessToOldDevice() {
        try {
            const phoneNumber = this.phoneTarget.value;

            const response = await fetch('/firebase-actions/no-access/old/device', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: phoneNumber
                })
            });

            const data = await response.json();

            if (data.status === 'admin_notified') {
                Toast.show({
                    text: '✅ Administrateur notifié',
                    duration: 'long'
                });
                this.showOtpVerificationDialog(phoneNumber);
            }

        } catch (error) {
            console.error('Erreur:', error);
            Toast.show({
                text: '❌ Une erreur est survenue',
                duration: 'long'
            });
        }
    }

    pollTransferApproval() {
        // Vérifier toutes les 5 secondes si le transfert a été approuvé
        const intervalId = setInterval(async () => {
            // TODO: Ajouter un endpoint pour vérifier le statut
            // Pour l'instant, on arrête après 2 minutes
            clearInterval(intervalId);
        }, 5000);

        // Arrêter après 2 minutes
        setTimeout(() => {
            clearInterval(intervalId);
        }, 120000);
    }

    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
    }
}
