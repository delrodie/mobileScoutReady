import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';

/**
 * Contrôleur de connexion avec SMS OTP
 * VERSION FINALE - Adaptée au flux complet
 */
export default class extends Controller {
    static targets = ['form', 'phone'];

    async connect() {
        console.log('🔌 Search Phone Controller - SMS Mode');
    }

    async submit(event) {
        event.preventDefault();

        const form = this.formTarget;
        const formData = new FormData(form);
        const phoneNumber = this.phoneTarget.value;

        // 📱 Récupérer les infos du device
        const firebaseController = this.getFirebaseController();
        const deviceInfo = await this.getDeviceInfo(firebaseController);

        // Ajouter au formulaire
        formData.append('device_id', deviceInfo.deviceId);
        formData.append('device_platform', deviceInfo.platform);
        formData.append('device_model', deviceInfo.model);

        console.log('📤 Envoi au serveur:', {
            phone: phoneNumber,
            device_id: deviceInfo.deviceId,
            platform: deviceInfo.platform
        });

        try {
            Toast.show({
                text: '⏳ Vérification...',
                duration: 'short'
            });

            // 🌐 Appel au serveur
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error("Erreur serveur");

            const data = await response.json();
            console.log('📥 Réponse serveur:', data);

            // 🎯 GÉRER LES DIFFÉRENTS CAS

            // CAS 1: ✅ DEVICE VÉRIFIÉ → Connexion directe
            if (data.status === 'ok' && data.device_check?.status === 'ok') {
                console.log('✅ Device vérifié - connexion directe');
                await this.handleSuccessfulLogin(data);
                return;
            }

            // CAS 2: 📱 VÉRIFICATION OTP REQUISE
            if (data.device_check?.requires_otp) {
                console.log('📱 Vérification OTP requise');

                // Sauvegarder les données en session
                sessionStorage.setItem('pending_phone', phoneNumber);
                sessionStorage.setItem('pending_login_data', JSON.stringify(data));

                // Envoyer le SMS via Firebase
                if (firebaseController) {
                    const smsResult = await firebaseController.sendSmsOtp(phoneNumber);

                    if (smsResult.success) {
                        // Afficher le modal de saisie OTP
                        this.showOtpModal(phoneNumber, data.device_check);
                    } else {
                        this.showError('Impossible d\'envoyer le SMS: ' + smsResult.error);
                    }
                } else {
                    this.showError('Service SMS non disponible');
                }
                return;
            }

            // CAS 3: 🆕 NOUVEL UTILISATEUR → Inscription
            if (data.status === 'new_user') {
                console.log('🆕 Nouvel utilisateur - redirection inscription');
                sessionStorage.setItem('_phone_input', phoneNumber);
                Turbo.visit('/inscription');
                return;
            }

            // CAS 4: ❌ ERREUR
            throw new Error(data.message || 'Erreur inconnue');

        } catch (error) {
            console.error('❌ Erreur:', error);
            this.showError(error.message);
        }
    }

    /**
     * Affiche le modal de saisie OTP
     */
    showOtpModal(phoneNumber, deviceCheck) {
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.id = 'otpModal';

        const maskedPhone = this.maskPhone(phoneNumber);
        const isNewDevice = deviceCheck.status === 'new_device';

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-lock"></i>
                            ${isNewDevice ? 'Nouveau device détecté' : 'Code de vérification'}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ${isNewDevice ? `
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Attention</strong><br>
                                Connexion depuis un nouveau device détectée
                            </div>
                        ` : ''}

                        <div class="alert alert-info mb-3">
                            <i class="bi bi-envelope"></i>
                            Un code à 6 chiffres a été envoyé par SMS au <strong>${maskedPhone}</strong>
                        </div>

                        <div class="mb-3">
                            <label for="otpInput" class="form-label fw-bold">Entrez le code</label>
                            <input
                                type="text"
                                class="form-control form-control-lg text-center fs-3"
                                id="otpInput"
                                placeholder="• • • • • •"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                style="letter-spacing: 1rem; font-weight: bold;"
                                autofocus
                            >
                            <small class="text-muted">Code à 6 chiffres</small>
                        </div>

                        <div class="alert alert-danger d-none" id="otpError"></div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-clock"></i>
                                Valide ${deviceCheck.otp_expiry || 10} minutes
                            </small>
                            <button type="button" class="btn btn-link btn-sm p-0" id="resendBtn">
                                <i class="bi bi-arrow-repeat"></i> Renvoyer
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
            document.getElementById('otpInput')?.focus();
        }, 200);

        // Event listeners
        document.getElementById('verifyBtn').addEventListener('click', () => {
            this.verifyOtp(phoneNumber);
        });

        document.getElementById('resendBtn').addEventListener('click', () => {
            this.resendOtp(phoneNumber);
        });

        modal.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        });

        // Enter = vérifier
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

        // Validation
        if (!code || code.length !== 6) {
            errorDiv.textContent = 'Veuillez entrer un code à 6 chiffres';
            errorDiv.classList.remove('d-none');
            otpInput.focus();
            return;
        }

        // Désactiver le bouton
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Vérification...';
        errorDiv.classList.add('d-none');

        try {
            // ÉTAPE 1: Vérifier avec Firebase
            const firebaseController = this.getFirebaseController();

            if (!firebaseController) {
                throw new Error('Service Firebase non disponible');
            }

            const firebaseResult = await firebaseController.verifySmsOtp(code);

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
            console.log('📥 Validation serveur:', serverData);

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

                // Continuer le flux
                await this.continueAfterVerification();

            } else {
                throw new Error('Échec validation serveur');
            }

        } catch (error) {
            console.error('❌ Erreur vérification:', error);

            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="bi bi-check-circle"></i> Vérifier';

            errorDiv.textContent = error.message || 'Code invalide ou expiré';
            errorDiv.classList.remove('d-none');

            otpInput.value = '';
            otpInput.focus();
        }
    }

    /**
     * Renvoie un nouveau code
     */
    async resendOtp(phoneNumber) {
        const resendBtn = document.getElementById('resendBtn');
        const originalHtml = resendBtn.innerHTML;

        resendBtn.disabled = true;
        resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Envoi...';

        try {
            const firebaseController = this.getFirebaseController();

            if (!firebaseController) {
                throw new Error('Service Firebase non disponible');
            }

            const result = await firebaseController.sendSmsOtp(phoneNumber);

            if (result.success) {
                Toast.show({
                    text: '✅ Nouveau code envoyé',
                    duration: 'short'
                });

                // Countdown 60 secondes
                let countdown = 60;
                const interval = setInterval(() => {
                    countdown--;
                    resendBtn.innerHTML = `<i class="bi bi-clock"></i> Renvoyer (${countdown}s)`;

                    if (countdown <= 0) {
                        clearInterval(interval);
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = originalHtml;
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
            resendBtn.innerHTML = originalHtml;
        }
    }

    /**
     * Continue après vérification réussie
     */
    async continueAfterVerification() {
        const pendingData = sessionStorage.getItem('pending_login_data');

        if (!pendingData) {
            console.warn('⚠️ Pas de données en attente');
            window.location.reload();
            return;
        }

        try {
            const data = JSON.parse(pendingData);
            sessionStorage.removeItem('pending_login_data');
            sessionStorage.removeItem('pending_phone');

            console.log('💾 Données récupérées:', data);

            // Parent → Choix profil
            if (data.profil && data.profil.isParent === true) {
                console.log('👨‍👩‍👧 Profil parent - redirection');
                Turbo.visit('/intro/choix/profil');
                return;
            }

            // Sauvegarder et rediriger
            await LoadDbController.saveToIndexedDB(data);

            Toast.show({
                text: '✅ Connexion réussie',
                duration: 'short'
            });

            Turbo.visit('/accueil');

        } catch (error) {
            console.error('❌ Erreur continuation:', error);
            window.location.reload();
        }
    }

    /**
     * Connexion réussie sans OTP
     */
    async handleSuccessfulLogin(data) {
        console.log('✅ Connexion autorisée');

        // Parent
        if (data.profil && data.profil.isParent === true) {
            sessionStorage.setItem('_phone_input', this.phoneTarget.value);
            Turbo.visit('/intro/choix/profil');
            return;
        }

        // Scout
        await LoadDbController.saveToIndexedDB(data);

        Toast.show({
            text: '✅ Connexion réussie',
            duration: 'short'
        });

        Turbo.visit('/accueil');
    }

    /**
     * Récupère le contrôleur Firebase
     */
    getFirebaseController() {
        const firebaseElement = document.querySelector('[data-controller~="firebase-sms"]');

        if (firebaseElement) {
            return this.application.getControllerForElementAndIdentifier(
                firebaseElement,
                'firebase-sms'
            );
        }

        console.warn('⚠️ Contrôleur firebase-sms non trouvé');
        return null;
    }

    /**
     * Récupère les infos du device
     */
    async getDeviceInfo(firebaseController) {
        if (firebaseController) {
            try {
                return await firebaseController.getDeviceInfo();
            } catch (error) {
                console.error('Erreur getDeviceInfo:', error);
            }
        }

        // Fallback
        return {
            deviceId: this.getOrCreateDeviceId(),
            platform: 'web',
            model: navigator.userAgent
        };
    }

    /**
     * Génère ou récupère un device ID
     */
    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
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
     * Affiche une erreur
     */
    showError(message) {
        Toast.show({
            text: '❌ ' + message,
            duration: 'long'
        });
    }
}
