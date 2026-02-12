import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';
import { Device } from '@capacitor/device';

/**
 * Contrôleur de connexion avec code PIN (pas de Firebase!)
 */
export default class extends Controller {
    static targets = ['form', 'phone'];

    async connect() {
        console.log('🔌 Search Phone Controller - Mode PIN');
    }

    async submit(event) {
        event.preventDefault();

        const form = this.formTarget;
        const formData = new FormData(form);
        const phoneNumber = this.phoneTarget.value;

        // 📱 Récupérer device info
        const deviceInfo = await this.getDeviceInfo();

        // Ajouter au formulaire
        formData.append('device_id', deviceInfo.deviceId);
        formData.append('device_platform', deviceInfo.platform);
        formData.append('device_model', deviceInfo.model);

        console.log('📤 Envoi au serveur:', {
            phone: phoneNumber,
            device_id: deviceInfo.deviceId
        });

        try {
            this.showToast('⏳ Vérification...');

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error("Erreur serveur");

            const data = await response.json();
            console.log('📥 Réponse serveur:', data);

            // Sauvegarder le numéro
            sessionStorage.setItem('pending_phone', phoneNumber);
            sessionStorage.setItem('pending_device_id', deviceInfo.deviceId);

            // 🎯 GÉRER LES DIFFÉRENTS CAS

            if (data.status === 'ok' || data.device_check?.status === 'ok') {
                // ✅ CONNEXION DIRECTE
                console.log('✅ Device vérifié - connexion directe');
                await this.handleSuccessfulLogin(data);

            } else if (data.device_check?.status === 'pin_creation_required') {
                // 🆕 CRÉER PIN
                console.log('🆕 Création PIN requise');
                sessionStorage.setItem('pending_login_data', JSON.stringify(data));
                this.showPinCreationModal(phoneNumber);

            } else if (data.device_check?.requires_pin) {
                // 🔐 DEMANDER PIN
                console.log('🔐 PIN requis');
                sessionStorage.setItem('pending_login_data', JSON.stringify(data));
                this.showPinVerificationModal(phoneNumber, data.device_check);

            } else if (data.status === 'new_user') {
                // 🆕 INSCRIPTION
                console.log('🆕 Nouvel utilisateur');
                sessionStorage.setItem('_phone_input', phoneNumber);
                Turbo.visit('/inscription');

            } else {
                throw new Error(data.message || 'Erreur inconnue');
            }

        } catch (error) {
            console.error('❌ Erreur:', error);
            this.showToast('❌ ' + error.message);
        }
    }

    /**
     * Affiche le modal de CRÉATION de PIN
     */
    showPinCreationModal(phoneNumber) {
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.9)';
        modal.id = 'pinModal';

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-lock"></i>
                            Créer votre code PIN
                        </h5>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i>
                            Créez un code PIN à <strong>4 chiffres</strong> pour sécuriser votre compte
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Code PIN (4 chiffres)</label>
                            <input
                                type="password"
                                class="form-control form-control-lg text-center fs-3"
                                id="pinInput"
                                placeholder="••••"
                                maxlength="4"
                                pattern="[0-9]{4}"
                                inputmode="numeric"
                                style="letter-spacing: 1rem; font-weight: bold;"
                                autofocus
                            >
                            <small class="text-muted">Entrez 4 chiffres de votre choix</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirmer le PIN</label>
                            <input
                                type="password"
                                class="form-control form-control-lg text-center fs-3"
                                id="pinConfirmInput"
                                placeholder="••••"
                                maxlength="4"
                                pattern="[0-9]{4}"
                                inputmode="numeric"
                                style="letter-spacing: 1rem; font-weight: bold;"
                            >
                        </div>

                        <div class="alert alert-danger d-none" id="pinError"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary w-100" id="createPinBtn">
                            <i class="bi bi-check-circle"></i> Créer le PIN
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Auto-focus
        setTimeout(() => {
            document.getElementById('pinInput')?.focus();
        }, 200);

        // Bouton créer
        document.getElementById('createPinBtn').addEventListener('click', () => {
            this.createPin(phoneNumber);
        });

        // Enter = créer
        modal.querySelectorAll('input').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    document.getElementById('createPinBtn').click();
                }
            });
        });
    }

    /**
     * Affiche le modal de VÉRIFICATION de PIN
     */
    showPinVerificationModal(phoneNumber, deviceCheck) {
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0,0,0,0.9)';
        modal.id = 'pinModal';

        const isNewDevice = deviceCheck.status === 'new_device_pin_required';

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header ${isNewDevice ? 'bg-warning' : 'bg-primary'} text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-lock"></i>
                            ${isNewDevice ? 'Nouveau device détecté' : 'Code PIN requis'}
                        </h5>
                    </div>

                    <div class="modal-body">
                        ${isNewDevice ? `
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Attention!</strong><br>
                                Connexion depuis un nouveau device détectée
                            </div>
                        ` : ''}

                        <div class="alert alert-info mb-3">
                            <i class="bi bi-key"></i>
                            Entrez votre code PIN à <strong>4 chiffres</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Code PIN</label>
                            <input
                                type="password"
                                class="form-control form-control-lg text-center fs-3"
                                id="pinInput"
                                placeholder="••••"
                                maxlength="4"
                                pattern="[0-9]{4}"
                                inputmode="numeric"
                                style="letter-spacing: 1rem; font-weight: bold;"
                                autofocus
                            >
                        </div>

                        <div class="alert alert-danger d-none" id="pinError"></div>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <a href="#" id="forgotPinLink">PIN oublié?</a>
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Annuler
                        </button>
                        <button type="button" class="btn btn-primary" id="verifyPinBtn">
                            <i class="bi bi-check-circle"></i> Vérifier
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Auto-focus
        setTimeout(() => {
            document.getElementById('pinInput')?.focus();
        }, 200);

        // Bouton vérifier
        document.getElementById('verifyPinBtn').addEventListener('click', () => {
            this.verifyPin(phoneNumber);
        });

        // Lien oublié
        document.getElementById('forgotPinLink').addEventListener('click', (e) => {
            e.preventDefault();
            alert('Contactez un administrateur pour réinitialiser votre PIN');
        });

        // Bouton annuler
        modal.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        });

        // Enter = vérifier
        document.getElementById('pinInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('verifyPinBtn').click();
            }
        });
    }

    /**
     * Crée le PIN
     */
    async createPin(phoneNumber) {
        const pinInput = document.getElementById('pinInput');
        const pinConfirmInput = document.getElementById('pinConfirmInput');
        const pin = pinInput.value.trim();
        const pinConfirm = pinConfirmInput.value.trim();
        const errorDiv = document.getElementById('pinError');
        const createBtn = document.getElementById('createPinBtn');

        // Validation
        if (!pin || pin.length !== 4) {
            errorDiv.textContent = 'Le PIN doit contenir 4 chiffres';
            errorDiv.classList.remove('d-none');
            pinInput.focus();
            return;
        }

        if (pin !== pinConfirm) {
            errorDiv.textContent = 'Les codes PIN ne correspondent pas';
            errorDiv.classList.remove('d-none');
            pinConfirmInput.focus();
            return;
        }

        // Désactiver le bouton
        createBtn.disabled = true;
        createBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Création...';
        errorDiv.classList.add('d-none');

        try {
            const response = await fetch('/pincode/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: phoneNumber,
                    pin: pin
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                // ✅ SUCCÈS
                const modal = document.getElementById('pinModal');
                if (modal) {
                    document.body.removeChild(modal);
                }

                this.showToast('✅ PIN créé avec succès!');

                // Continuer avec les données reçues
                await this.continueWithUserData(data.user_data);

            } else {
                throw new Error(data.error || 'Erreur création PIN');
            }

        } catch (error) {
            console.error('❌ Erreur:', error);

            createBtn.disabled = false;
            createBtn.innerHTML = '<i class="bi bi-check-circle"></i> Créer le PIN';

            errorDiv.textContent = error.message;
            errorDiv.classList.remove('d-none');
        }
    }

    /**
     * Vérifie le PIN
     */
    async verifyPin(phoneNumber) {
        const pinInput = document.getElementById('pinInput');
        const pin = pinInput.value.trim();
        const errorDiv = document.getElementById('pinError');
        const verifyBtn = document.getElementById('verifyPinBtn');
        const deviceId = sessionStorage.getItem('pending_device_id');

        // Validation
        if (!pin || pin.length !== 4) {
            errorDiv.textContent = 'Entrez un code à 4 chiffres';
            errorDiv.classList.remove('d-none');
            pinInput.focus();
            return;
        }

        // Désactiver le bouton
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Vérification...';
        errorDiv.classList.add('d-none');

        try {
            const response = await fetch('/pincode/verify-pin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    phone: phoneNumber,
                    pin: pin,
                    device_id: deviceId
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                // ✅ SUCCÈS
                const modal = document.getElementById('pinModal');
                if (modal) {
                    document.body.removeChild(modal);
                }

                this.showToast('✅ PIN vérifié!');

                // Continuer
                await this.continueWithUserData(data.user_data);

            } else {
                throw new Error(data.error || 'PIN incorrect');
            }

        } catch (error) {
            console.error('❌ Erreur:', error);

            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="bi bi-check-circle"></i> Vérifier';

            errorDiv.textContent = error.message;
            errorDiv.classList.remove('d-none');

            pinInput.value = '';
            pinInput.focus();
        }
    }

    /**
     * Continue avec les données utilisateur
     */
    async continueWithUserData(userData) {
        // Parent?
        if (userData.profil && userData.profil.isParent === true) {
            Turbo.visit('/intro/choix/profil');
            return;
        }

        // Sauvegarder et rediriger
        await LoadDbController.saveToIndexedDB(userData);

        this.showToast('✅ Connexion réussie');
        Turbo.visit('/accueil');
    }

    /**
     * Connexion réussie sans PIN
     */
    async handleSuccessfulLogin(data) {
        if (data.profil && data.profil.isParent === true) {
            console.log("Choix du profil car parent...")
            Turbo.visit('/intro/choix/profil');
            return;
        }

        await LoadDbController.saveToIndexedDB(data);
        this.showToast('✅ Connexion réussie');
        Turbo.visit('/accueil');
    }

    /**
     * Récupère device info
     */
    async getDeviceInfo() {
        try {
            const info = await Device.getInfo();
            const id = await Device.getId();

            return {
                deviceId: id.identifier,
                platform: info.platform,
                model: info.model || info.manufacturer
            };
        } catch (error) {
            console.warn('⚠️ Device API error:', error);
            return {
                deviceId: this.getOrCreateDeviceId(),
                platform: 'web',
                model: navigator.userAgent
            };
        }
    }

    /**
     * Génère device ID
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
     * Affiche toast
     */
    showToast(message) {
        if (Capacitor.isNativePlatform()) {
            Toast.show({
                text: message,
                duration: 'short'
            });
        } else {
            console.log('📢', message);
        }
    }
}
