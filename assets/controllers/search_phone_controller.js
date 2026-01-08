import { Controller } from '@hotwired/stimulus';
import LoadDbController from './local_db_controller.js';


export default class extends Controller {
    static targets = ['form', 'phone'];

    async submit(event) {
        event.preventDefault();

        const form = this.formTarget;
        const formData = new FormData(form);

        // 🔥 Récupérer les infos du device depuis le firebase controller
        const firebaseController = this.application.getControllerForElementAndIdentifier(
            document.body,
            'firebase'
        );

        console.log(firebaseController)

        let deviceInfo = {
            device_id: this.getOrCreateDeviceId(),
            fcm_token: localStorage.getItem('fcm_token') || '',
            device_platform: 'web',
            device_model: navigator.userAgent
        };

        console.log('device')
        console.log(deviceInfo)

        // Si Firebase controller existe, récupérer les vraies infos
        if (firebaseController) {
            deviceInfo = await firebaseController.getDeviceInfoForAuth();
        }

        // Ajouter les infos device au FormData
        formData.append('device_id', deviceInfo.device_id);
        formData.append('fcm_token', deviceInfo.fcm_token);
        formData.append('device_platform', deviceInfo.device_platform);
        formData.append('device_model', deviceInfo.device_model);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            //console.log(` response: ${response}`);

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
                        this.showOtpVerificationDialog(data.profil.telephone);
                        return;

                    case 'new_device':
                        // Nouveau device détecté → attendre approbation
                        this.showNewDeviceDialog(deviceCheck);
                        return;

                    case 'ok':
                        // Device vérifié → continuer normalement
                        break;

                    default:
                        console.warn('Statut device inconnu:', deviceCheck.status);
                }
            }

            if (data.profil.isParent === true){
                console.log("Profile parent")
                Turbo.visit('/intro/choix/profil');
                return;
            }

            await LoadDbController.saveToIndexedDB(data);
            // await this.saveToIndexedDB(data);

            // Redirection vers l’accueil après succès
            Turbo.visit('/accueil');

        } catch (error) {
            console.error("❌ Erreur lors de la soumission :", error);
            alert("Une erreur est survenue. Vérifiez votre connexion.");
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
                alert('✅ Appareil vérifié avec succès !');
                // Recharger les données et continuer
                window.location.reload();
            } else {
                alert('❌ Code OTP invalide ou expiré');
            }

        } catch (error) {
            console.error('Erreur vérification OTP:', error);
            alert('Erreur lors de la vérification');
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
                alert('✅ ' + data.message);
                // Afficher un dialog pour entrer l'OTP admin
                this.showOtpVerificationDialog(phoneNumber);
            }

        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
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
