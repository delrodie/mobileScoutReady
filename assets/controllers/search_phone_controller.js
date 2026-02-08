import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';
import firebaseSmsController  from './firebase_sms_controller.js';

/**
 * Contrôleur de connexion avec SMS OTP (version simplifiée)
 */
export default class extends Controller {
    static targets = ['form', 'phone'];

    async connect() {
        console.log('🔌 Search Phone Controller (SMS Mode)');
    }

    async submit(event) {
        event.preventDefault();

        const form = this.formTarget;
        const formData = new FormData(form);
        const phoneNumber = this.phoneTarget.value;

        // Récupérer les infos du device
        // const firebaseSmsController = this.application.getControllerForElementAndIdentifier(
        //     document.body,
        //     'firebase-sms'
        // );

        console.log('SEARCH_PHONE_CONTROLLER : appel de firebase-sms');
        console.log(firebaseSmsController);

        let deviceInfo = {
            device_id: this.getOrCreateDeviceId(),
            device_platform: 'web',
            device_model: navigator.userAgent
        };

        if (firebaseSmsController) {
            [deviceInfo] = await Promise.all([firebaseSmsController.getDeviceInfo()]);
        }

        // Ajouter les infos du device
        formData.append('device_id', deviceInfo.deviceId);
        formData.append('device_platform', deviceInfo.platform);
        formData.append('device_model', deviceInfo.model);

        console.log('📤 Envoi au serveur:', {
            phone: phoneNumber,
            device_id: deviceInfo.deviceId
        });

        try {
            Toast.show({
                text: '⏳ Vérification...',
                duration: 'short'
            });

            console.log(formData);
            Toast.show({text: form, duration: 'short'});

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            console.log(response);
            Toast.show({text: response, duration: 'short'});

            if (!response.ok) throw new Error("Erreur serveur");

            const data = await response.json();
            console.log('📥 Réponse serveur:', data);

            // Gérer les différents cas
            if (data.status === 'ok') {
                // ✅ Device déjà vérifié
                await this.handleSuccessfulLogin(data);

            } else if (data.status === 'verification_required' || data.status === 'new_device') {
                // 📱 Envoyer SMS et demander vérification
                sessionStorage.setItem('pending_phone', phoneNumber);
                sessionStorage.setItem('pending_login_data', JSON.stringify(data));

                // Envoyer le SMS via Firebase
                if (firebaseSmsController) {
                    const [smsResult] = await Promise.all([firebaseSmsController.sendSmsOtp(phoneNumber)]);

                    if (smsResult.success) {
                        // Afficher le modal de saisie OTP
                        this.showOtpModal(phoneNumber, data);
                    } else {
                        alert('❌ Impossible d\'envoyer le SMS: ' + smsResult.error);
                    }
                } else {
                    alert('❌ Firebase SMS non initialisé');
                }

            } else if (data.status === 'new_user') {
                // 🆕 Nouvel utilisateur
                sessionStorage.setItem('_phone_input', phoneNumber);
                Turbo.visit('/inscription');

            } else {
                throw new Error(data.message || 'Erreur inconnue');
            }

        } catch (error) {
            console.error('❌ Erreur:', error);
            Toast.show({
                text: '❌ ' + error.message,
                duration: 'long'
            });
        }
    }

    /**
     * Affiche le modal de saisie OTP
     */
    showOtpModal(phoneNumber, serverData) {
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.id = 'otpModal';

        const maskedPhone = this.maskPhone(phoneNumber);

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">📱 Code de vérification</h5>
                        <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-envelope"></i>
                            Un code à 6 chiffres a été envoyé par SMS au <strong>${maskedPhone}</strong>
                        </div>

                        <div class="mb-3">
                            <label for="otpInput" class="form-label">Entrez le code</label>
                            <input
                                type="text"
                                class="form-control form-control-lg text-center fs-3"
                                id="otpInput"
                                placeholder="• • • • • •"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                style="letter-spacing: 1rem;"
                            >
                        </div>

                        <div class="alert alert-danger d-none" id="otpError"></div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                ⏱️ Valide ${serverData.otp_expiry || 10} minutes
                            </small>
                            <button type="button" class="btn btn-link btn-sm p-0" id="resendBtn">
                                Renvoyer le code
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Annuler
                        </button>
                        <button type="button" class="btn btn-primary" id="verifyBtn">
                            <i class="bi bi-check-circle"></i> Vérifier
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Auto-focus
        setTimeout(() => {
            document.getElementById('otpInput').focus();
        }, 100);

        // Vérifier le code
        document.getElementById('verifyBtn').addEventListener('click', async () => {
            await this.verifyOtp(phoneNumber);
        });

        // Renvoyer
        document.getElementById('resendBtn').addEventListener('click', async () => {
            await this.resendOtp(phoneNumber);
        });

        // Fermer
        modal.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        });

        // Entrée = vérifier
        document.getElementById('otpInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('verifyBtn').click();
            }
        });
    }

    /**
     * Vérifie l'OTP saisi
     */
    async verifyOtp(phoneNumber) {
        const otpInput = document.getElementById('otpInput');
        const code = otpInput.value.trim();
        const verifyBtn = document.getElementById('verifyBtn');
        const errorDiv = document.getElementById('otpError');

        if (!code || code.length !== 6) {
            errorDiv.textContent = 'Veuillez entrer un code à 6 chiffres';
            errorDiv.classList.remove('d-none');
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Vérification...';
        errorDiv.classList.add('d-none');

        try {
            // ÉTAPE 1: Vérifier avec Firebase
            const firebaseSmsController = this.application.getControllerForElementAndIdentifier(
                document.body,
                'firebase-sms'
            );

            if (!firebaseSmsController) {
                throw new Error('Firebase non initialisé');
            }

            const firebaseResult = await firebaseSmsController.verifySmsOtp(code);

            if (!firebaseResult.success) {
                throw new Error(firebaseResult.error || 'Code invalide');
            }

            console.log('✅ Code validé par Firebase');

            // ÉTAPE 2: Valider côté serveur
            const response = await fetch('/firebase-actions/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: phoneNumber,
                    otp: code
                })
            });

            const serverData = await response.json();
            console.log('📥 Réponse serveur:', serverData);

            if (serverData.status === 'verified') {
                // ✅ SUCCÈS !
                const modal = document.getElementById('otpModal');
                if (modal) {
                    document.body.removeChild(modal);
                }

                Toast.show({
                    text: '✅ Appareil vérifié !',
                    duration: 'short'
                });

                // Continuer le flux de connexion
                await this.continueAfterVerification();

            } else {
                throw new Error('Erreur de vérification serveur');
            }

        } catch (error) {
            console.error('❌ Erreur vérification:', error);

            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="bi bi-check-circle"></i> Vérifier';

            errorDiv.textContent = error.message || 'Code invalide ou expiré';
            errorDiv.classList.remove('d-none');

            // Vider le champ
            otpInput.value = '';
            otpInput.focus();
        }
    }

    /**
     * Renvoie un nouveau code
     */
    async resendOtp(phoneNumber) {
        const resendBtn = document.getElementById('resendBtn');
        const originalText = resendBtn.textContent;

        resendBtn.disabled = true;
        resendBtn.textContent = 'Envoi...';

        try {
            const firebaseSmsController = this.application.getControllerForElementAndIdentifier(
                document.body,
                'firebase-sms'
            );

            if (!firebaseSmsController) {
                throw new Error('Firebase non initialisé');
            }

            const result = await firebaseSmsController.sendSmsOtp(phoneNumber);

            if (result.success) {
                Toast.show({
                    text: '✅ Nouveau code envoyé',
                    duration: 'short'
                });

                // Countdown 60 secondes
                let countdown = 60;
                const interval = setInterval(() => {
                    countdown--;
                    resendBtn.textContent = `Renvoyer (${countdown}s)`;

                    if (countdown <= 0) {
                        clearInterval(interval);
                        resendBtn.disabled = false;
                        resendBtn.textContent = originalText;
                    }
                }, 1000);

            } else {
                throw new Error(result.error);
            }

        } catch (error) {
            console.error('Erreur renvoi:', error);
            Toast.show({
                text: '❌ ' + error.message,
                duration: 'long'
            });
            resendBtn.disabled = false;
            resendBtn.textContent = originalText;
        }
    }

    /**
     * Continue après vérification réussie
     */
    async continueAfterVerification() {
        const pendingData = sessionStorage.getItem('pending_login_data');

        if (!pendingData) {
            window.location.reload();
            return;
        }

        try {
            const data = JSON.parse(pendingData);
            sessionStorage.removeItem('pending_login_data');
            sessionStorage.removeItem('pending_phone');

            if (data.profil && data.profil.isParent === true) {
                Turbo.visit('/intro/choix/profil');
                return;
            }

            await LoadDbController.saveToIndexedDB(data);
            Turbo.visit('/accueil');

        } catch (error) {
            console.error('Erreur continuation:', error);
            window.location.reload();
        }
    }

    /**
     * Connexion réussie sans OTP
     */
    async handleSuccessfulLogin(data) {
        if (data.profil && data.profil.isParent === true) {
            sessionStorage.setItem('_phone_input', this.phoneTarget.value);
            Turbo.visit('/intro/choix/profil');
            return;
        }

        await LoadDbController.saveToIndexedDB(data);
        Toast.show({
            text: '✅ Connexion réussie',
            duration: 'short'
        });
        Turbo.visit('/accueil');
    }

    /**
     * Masque le numéro
     */
    maskPhone(phone) {
        if (phone.length < 4) return phone;
        const start = phone.substring(0, 3);
        const end = phone.substring(phone.length - 4);
        return `${start}***${end}`;
    }

    /**
     * Génère un device ID
     */
    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
    }
}
