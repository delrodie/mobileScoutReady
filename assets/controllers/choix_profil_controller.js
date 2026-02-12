import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';
import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';

/**
 * Contrôleur pour la sélection de profil (parent avec plusieurs comptes)
 * Vérifie le device/PIN pour CHAQUE profil sélectionné
 */
export default class extends Controller {

    async connect() {
        console.log('🔌 Choix Profil Controller - Mode PIN');
    }

    /**
     * Intercepte le clic sur un profil
     */
    async select(event) {
        event.preventDefault();

        const link = event.currentTarget;
        const href = link.getAttribute('href');
        const slug = href.split('/').pop();

        console.log('👤 Profil sélectionné:', slug);
        console.log('🔗 URL:', href);

        // Récupérer device info
        const deviceInfo = await this.getDeviceInfo();

        console.log('📱 Device info:', deviceInfo);

        try {
            this.showToast('⏳ Vérification...');

            // 1. Appeler le serveur avec device info
            const formData = new URLSearchParams();
            formData.append('device_id', deviceInfo.deviceId);
            formData.append('device_platform', deviceInfo.platform);
            formData.append('device_model', deviceInfo.model);

            const response = await fetch(href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData
            });

            if (!response.ok) throw new Error("Erreur serveur");

            const data = await response.json();
            console.log('📥 Réponse serveur:', data);

            // Sauvegarder pour les modals
            sessionStorage.setItem('pending_profil_data', JSON.stringify(data));
            sessionStorage.setItem('pending_device_id', deviceInfo.deviceId);
            sessionStorage.setItem('pending_phone', data.phone);

            // 2. Gérer selon device_check
            const deviceCheck = data.device_check;

            if (!deviceCheck) {
                throw new Error('Pas de vérification device');
            }

            if (deviceCheck.status === 'ok') {
                // ✅ Device vérifié - connexion directe
                console.log('✅ Device vérifié - connexion directe');
                await this.loginWithProfil(data);

            } else if (deviceCheck.status === 'pin_creation_required') {
                // 🆕 Créer PIN
                console.log('🆕 Création PIN requise');
                this.showPinCreationModal(data.phone);

            } else if (deviceCheck.requires_pin) {
                // 🔐 Demander PIN
                console.log('🔐 PIN requis');
                this.showPinVerificationModal(data.phone, deviceCheck);

            } else {
                throw new Error('Statut device inconnu: ' + deviceCheck.status);
            }

        } catch (error) {
            console.error('❌ Erreur:', error);
            this.showToast('❌ ' + error.message);
        }
    }

    /**
     * Affiche modal création PIN
     */
    showPinCreationModal(phone) {
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
                            Créez un code PIN à <strong>4 chiffres</strong> pour sécuriser ce profil
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

        setTimeout(() => document.getElementById('pinInput')?.focus(), 200);

        document.getElementById('createPinBtn').addEventListener('click', () => {
            this.createPin(phone);
        });

        modal.querySelectorAll('input').forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    document.getElementById('createPinBtn').click();
                }
            });
        });
    }

    /**
     * Affiche modal vérification PIN
     */
    showPinVerificationModal(phone, deviceCheck) {
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
                                Connexion depuis un nouveau device pour ce profil
                            </div>
                        ` : ''}

                        <div class="alert alert-info mb-3">
                            <i class="bi bi-key"></i>
                            Entrez le code PIN de <strong>ce profil</strong>
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

        setTimeout(() => document.getElementById('pinInput')?.focus(), 200);

        document.getElementById('verifyPinBtn').addEventListener('click', () => {
            this.verifyPin(phone);
        });

        modal.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modal);
            });
        });

        document.getElementById('pinInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('verifyPinBtn').click();
            }
        });
    }

    /**
     * Crée le PIN
     */
    async createPin(phone) {
        const pinInput = document.getElementById('pinInput');
        const pinConfirmInput = document.getElementById('pinConfirmInput');
        const pin = pinInput.value.trim();
        const pinConfirm = pinConfirmInput.value.trim();
        const errorDiv = document.getElementById('pinError');
        const createBtn = document.getElementById('createPinBtn');

        if (!pin || pin.length !== 4) {
            errorDiv.textContent = 'Le PIN doit contenir 4 chiffres';
            errorDiv.classList.remove('d-none');
            pinInput.focus();
            return;
        }

        if (!/^\d{4}$/.test(pin)) {
            errorDiv.textContent = 'Le PIN doit contenir uniquement des chiffres';
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
                body: JSON.stringify({ phone, pin })
            });

            const data = await response.json();

            if (data.status === 'success') {
                const modal = document.getElementById('pinModal');
                if (modal) document.body.removeChild(modal);

                this.showToast('✅ PIN créé avec succès!');

                // Utiliser les données du profil en session
                const profilData = JSON.parse(sessionStorage.getItem('pending_profil_data') || '{}');
                await this.loginWithProfil(profilData);

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
    async verifyPin(phone) {
        const pinInput = document.getElementById('pinInput');
        const pin = pinInput.value.trim();
        const errorDiv = document.getElementById('pinError');
        const verifyBtn = document.getElementById('verifyPinBtn');
        const deviceId = sessionStorage.getItem('pending_device_id');

        if (!pin || pin.length !== 4) {
            errorDiv.textContent = 'Entrez un code à 4 chiffres';
            errorDiv.classList.remove('d-none');
            pinInput.focus();
            return;
        }

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
                body: JSON.stringify({ phone, pin, device_id: deviceId })
            });

            const data = await response.json();

            if (data.status === 'success') {
                const modal = document.getElementById('pinModal');
                if (modal) document.body.removeChild(modal);

                this.showToast('✅ PIN vérifié!');

                // Utiliser les données du profil en session
                const profilData = JSON.parse(sessionStorage.getItem('pending_profil_data') || '{}');
                await this.loginWithProfil(profilData);

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
     * Connexion avec le profil sélectionné
     */
    async loginWithProfil(profilData) {
        try {
            console.log('💾 Sauvegarde profil:', profilData);

            // Sauvegarder en IndexedDB
            await LoadDbController.saveToIndexedDB(profilData);

            this.showToast('✅ Connexion réussie');

            // Nettoyer session
            sessionStorage.removeItem('pending_profil_data');
            sessionStorage.removeItem('pending_device_id');
            sessionStorage.removeItem('pending_phone');

            // Rediriger
            Turbo.visit('/accueil');

        } catch (error) {
            console.error('❌ Erreur sauvegarde:', error);
            this.showToast('❌ Erreur de connexion');
        }
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
            console.warn('⚠️ Device API error, fallback:', error);
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
