import { Controller } from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";
import flasher from "@flasher/flasher";
import { Toast } from "@capacitor/toast";

export default class extends Controller {
    static values = {reunionId: String};
    static targets = ["modal", "scannerContainer", "nativeMessage", "webContainer", "loading"];

    scanner = null;
    isNative = false;
    html5QrCode = null; // Pour stocker l'instance du scanner web

    connect() {
        console.log("Scanner controller connecté");

        // Détecter l'environnement
        this.isNative = this.isCapacitorNative();
        console.log("Environnement:", this.isNative ? "Natif" : "Web");

        this.setupTurboEvents();
    }

    disconnect() {
        this.stopScan();
    }

    isCapacitorNative() {
        // Vérifier si nous sommes dans un environnement Capacitor natif
        return typeof Capacitor !== 'undefined' &&
            Capacitor.isNative &&
            typeof Capacitor.Plugins !== 'undefined' &&
            Capacitor.Plugins.BarcodeScanner;
    }

    async openModal() {
        console.log("Tentative d'ouverture du scanner");

        try {
            // Afficher le modal
            this.modalTarget.classList.remove('d-none');
            this.modalTarget.classList.add('show', 'd-flex');
            document.body.classList.add('modal-open');

            // Afficher l'état de chargement
            this.showElement('loading');
            this.hideElement('nativeMessage');
            this.hideElement('webContainer');

            // Démarrer le scan selon l'environnement
            setTimeout(async () => {
                if (this.isNative) {
                    await this.startNativeScan();
                } else {
                    await this.startWebScan();
                }
            }, 300);

        } catch (error) {
            console.error("Erreur lors de l'ouverture du modal:", error);
            flasher.error("Erreur lors de l'ouverture du scanner");
            this.closeModal();
        }
    }

    async startNativeScan() {
        try {
            console.log("Démarrage du scan natif...");

            // Cacher le chargement et afficher le message natif
            this.hideElement('loading');
            this.showElement('nativeMessage');

            // Importer dynamiquement le scanner Capacitor
            const { BarcodeScanner } = await import('@capacitor/barcode-scanner');

            // Vérifier les permissions
            const status = await BarcodeScanner.checkPermission({ force: true });
            console.log("Statut permissions:", status);

            if (status.granted) {
                console.log("Permissions accordées, démarrage...");

                // IMPORTANT: Avec Capacitor, le scanner s'ouvre en plein écran
                // Il va cacher votre interface web
                await BarcodeScanner.hideBackground();

                // Préparer et démarrer le scanner
                await BarcodeScanner.prepare();

                const result = await BarcodeScanner.startScan({
                    targetedFormats: ['QR_CODE', 'EAN_13', 'CODE_128']
                });

                console.log("Résultat scan:", result);

                if (result.hasContent) {
                    console.log("QR Code détecté:", result.content);
                    // Arrêter le scanner et revenir à l'interface
                    await BarcodeScanner.stopScan();
                    await BarcodeScanner.showBackground();
                    this.closeModal();
                    await this.sendPointage(result.content);
                }

            } else if (status.denied) {
                flasher.error("Accès à la caméra refusé. Activez-la dans les paramètres.");
                if (BarcodeScanner.openAppSettings) {
                    await BarcodeScanner.openAppSettings();
                }
                this.closeModal();
            } else {
                const requestStatus = await BarcodeScanner.requestPermission();
                console.log("Demande permission:", requestStatus);
                if (requestStatus.granted) {
                    // Recommencer le scan
                    await this.startNativeScan();
                } else {
                    flasher.error("Permission refusée");
                    Toast.show({text: "Permission réfusée", duration: 'long', position: 'bottom'})
                    this.closeModal();
                }
            }
        } catch (error) {
            console.error("Erreur détaillée scan natif:", error);

            // Analyser l'erreur
            if (error.message && error.message.includes('user closed')) {
                console.log("Utilisateur a annulé");
            } else if (error.message && error.message.includes('No camera')) {
                flasher.error("Aucune caméra disponible");
                Toast.show({text: "Aucune caméra", duration: 'long', position: 'bottom'})
            } else if (error.message && error.message.includes('Permission')) {
                flasher.error("Permission caméra requise");
                Toast.show({text: "Permission caméra requise", duration: 'long', position: 'bottom'});
            } else {
                flasher.error("Erreur scanner: " + error.message);
                Toast.show({text: "Erreur scanner : "+ error.message, duration: 'long', position: 'bottom'});
            }

            // Essayez de restaurer l'interface
            try {
                const { BarcodeScanner } = await import('@capacitor/barcode-scanner');
                await BarcodeScanner.showBackground();
            } catch (e) {
                console.error("Erreur restauration:", e);
            }

            this.closeModal();
        }
    }

    async startWebScan() {
        try {
            console.log("Démarrage du scan web...");

            // Cacher le chargement et afficher le container web
            this.hideElement('loading');
            this.showElement('webContainer');

            // Importer dynamiquement le scanner web
            const { Html5Qrcode } = await import('html5-qrcode');

            // Vérifier si l'élément existe
            const scannerElement = document.getElementById('qr-reader');
            if (!scannerElement) {
                throw new Error("Élément scanner non trouvé");
            }

            // Créer une instance de Html5Qrcode
            this.html5QrCode = new Html5Qrcode("qr-reader");

            // Démarrer le scan automatiquement
            const qrCodeSuccessCallback = (decodedText) => {
                console.log("QR Code détecté:", decodedText);
                this.stopWebScan();
                this.closeModal();
                this.sendPointage(decodedText);
            };

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                rememberLastUsedCamera: true,
                supportedScanTypes: [0], // 0 = camera, 1 = fichier
                showTorchButtonIfSupported: true,
                showZoomSliderIfSupported: true,
                defaultZoomValueIfSupported: 2
            };

            // Démarrer la caméra et le scan
            await this.html5QrCode.start(
                { facingMode: "environment" }, // Utiliser la caméra arrière
                config,
                qrCodeSuccessCallback,
                (errorMessage) => {
                    // Ne pas afficher les erreurs normales d'arrêt
                    if (errorMessage && !errorMessage.includes("NotFoundException") && !errorMessage.includes("NotAllowedError")) {
                        console.log("Erreur de scan:", errorMessage);
                    }
                }
            );

            console.log("Scanner web démarré avec succès");

        } catch (error) {
            console.error("Erreur initialisation scan web:", error);

            if (error.message && error.message.includes('NotAllowedError')) {
                flasher.error("Permission caméra refusée dans le navigateur");

            } else if (error.message && error.message.includes('NotFoundError')) {
                flasher.error("Aucune caméra disponible");
            } else {
                flasher.error("Erreur scan web: " + error.message);
            }

            this.closeModal();
        }
    }

    stopWebScan() {
        if (this.html5QrCode) {
            try {
                this.html5QrCode.stop().then(() => {
                    console.log("Scanner web arrêté");
                    this.html5QrCode.clear();
                    this.html5QrCode = null;
                }).catch(err => {
                    console.error("Erreur lors de l'arrêt du scanner web:", err);
                });
            } catch (error) {
                console.error("Erreur arrêt scanner web:", error);
            }
        }
    }

    stopScan() {
        // Arrêter le scanner web si actif
        if (!this.isNative) {
            this.stopWebScan();
        }
    }

    closeModal() {
        console.log("Fermeture du modal");

        this.stopScan();

        // Masquer la modale
        this.modalTarget.classList.add('d-none');
        this.modalTarget.classList.remove('show', 'd-flex');
        document.body.classList.remove('modal-open');

        // Réinitialiser les états
        this.hideAllElements();
    }

    // Méthodes utilitaires pour gérer l'affichage
    showElement(elementName) {
        const element = this[`${elementName}Target`];
        if (element) {
            element.classList.remove('d-none');
        }
    }

    hideElement(elementName) {
        const element = this[`${elementName}Target`];
        if (element) {
            element.classList.add('d-none');
        }
    }

    hideAllElements() {
        this.hideElement('loading');
        this.hideElement('nativeMessage');
        this.hideElement('webContainer');
    }

    async sendPointage(code) {
        console.log("Envoi du pointage avec code:", code);
        const reunionId = this.reunionIdValue;
        const profil = await LocalDbController.getAllFromStore('profil');
        const url = `/pointage/reunion`;

        if (!profil || profil.length === 0) {
            console.warn("Aucun profil trouvé en local");
            Turbo.visit('/intro')
            return;
        }

        try {
            console.log("Envoi de la requête au serveur...");
            const response = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams({
                    reunion: reunionId,
                    code: code,
                    pointeur: profil[0].code,
                }),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
            });

            console.log("Réponse reçue:", response.status);

            // Essayer de parser la réponse en JSON
            let data;
            try {
                data = await response.json();
                console.log("Données reçues:", data);
                if (data.message){
                    Toast.show({text: data.message, duration: 'long', position: 'bottom'});
                }
            } catch (e) {
                // Si la réponse n'est pas du JSON, on lance une erreur avec le texte de la réponse
                const text = await response.text();
                throw new Error(text || `Erreur HTTP: ${response.status}`);
            }

            // Si le statut HTTP n'est pas OK, mais qu'on a du JSON, on le traite quand même
            if (!response.ok && data.message) {
                // On traite l'erreur comme une réponse normale avec message
                this.handlePointageResponse(data, response.status);
                return;
            }

            if (!response.ok) {
                throw new Error(data.message || `Erreur HTTP: ${response.status}`);
            }

            // Si tout est OK, traiter la réponse
            this.handlePointageResponse(data, response.status);

        } catch (error) {
            console.error("Erreur lors de l'envoi du pointage:", error);
            this.handlePointageError(error);
        }
    }

    handlePointageResponse(data, statusCode) {
        console.log("Traitement de la réponse:", data, "Status:", statusCode);

        // Fermer le modal d'abord
        this.closeModal();

        // Utiliser un timeout pour s'assurer que le modal est fermé
        setTimeout(() => {
            if (data.status === 'success') {
                // Afficher le message de succès
                flasher.success(data.message || 'Opération réussie');
                Toast.show({text: data.message || 'Opération réussie', duration: 'long', position: 'bottom'});

                // Attendre un peu avant la redirection pour que le message soit visible
                setTimeout(() => {
                    const targetUrl = `/reunion/${this.reunionIdValue}`;
                    Turbo.visit(targetUrl, { action: 'replace', method: 'get' });
                }, 1500);

            } else if (data.status === 'warning') {
                flasher.warning(data.message || 'Attention');
                Toast.show({text: data.message || 'Attention', duration: 'long', position: 'bottom'});
            } else if (data.status === 'error') {
                flasher.error(data.message || 'Erreur');
                Toast.show({text: data.message || 'Erreur', duration: 'long', position: 'bottom'});
            } else {
                // Si le statut n'est pas défini, vérifier le code HTTP
                if (statusCode >= 400 && statusCode < 500) {
                    flasher.warning(data.message || `Erreur client: ${statusCode}`);
                    Toast.show({text: data.message || `Erreur client: ${statusCode}`, duration: 'long', position: 'bottom'});
                } else if (statusCode >= 500) {
                    flasher.error(data.message || `Erreur serveur: ${statusCode}`);
                    Toast.show({text: data.message || `Erreur serveur: ${statusCode}`, duration: 'long', position: 'bottom'});
                } else {
                    flasher.info(data.message || 'Réponse inattendue');
                    Toast.show({text: data.message || 'Réponse inattendue', duration: 'long', position: 'bottom'});
                }
            }
        }, 300);
    }

    handlePointageError(error) {
        console.error("Erreur détaillée:", error);

        // Fermer le modal d'abord
        this.closeModal();

        // Utiliser un timeout pour s'assurer que le modal est fermé
        setTimeout(() => {
            let message = error.message || 'Erreur lors de l\'envoi du pointage';

            // Extraire le message du JSON si l'erreur en contient
            try {
                if (error.message && error.message.includes('{')) {
                    const jsonMatch = error.message.match(/\{.*\}/);
                    if (jsonMatch) {
                        const errorData = JSON.parse(jsonMatch[0]);
                        message = errorData.message || message;
                    }
                }
            } catch (e) {
                // Ignorer si ce n'est pas du JSON valide
            }

            flasher.error(message);
        }, 300);
    }

    handlePointageResponse(data) {
        // Fermer le modal d'abord
        this.closeModal();

        // Utiliser un timeout pour s'assurer que le modal est fermé
        setTimeout(() => {
            if (data.status === 'success') {
                const targetUrl = `/reunion/${this.reunionIdValue}`;
                Turbo.visit(targetUrl, { action: 'replace', method: 'get' });
            } else if (data.status === 'warning') {
                this.showFlashMessage(data.message, 'warning');
            } else {
                this.showFlashMessage(data.message || 'Erreur inconnue', 'error');
            }
        }, 300);
    }

    handlePointageError(error) {
        // Fermer le modal d'abord
        this.closeModal();

        // Utiliser un timeout pour s'assurer que le modal est fermé
        setTimeout(() => {
            this.showFlashMessage(error.message || 'Erreur lors de l\'envoi du pointage', 'error');
        }, 300);
    }

    showFlashMessage(message, type = 'info') {
        // Utiliser flasher selon le type
        switch(type) {
            case 'warning':
                flasher.warning(message);
                break;
            case 'error':
                flasher.error(message);
                break;
            case 'success':
                flasher.success(message);
                break;
            default:
                flasher.info(message);
        }

        // Alternative : créer une notification personnalisée
        this.createCustomNotification(message, type);
    }

// Méthode alternative pour les notifications
    createCustomNotification(message, type = 'info') {
        // Créer un élément de notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

        // Ajouter au DOM
        const container = document.getElementById('flash-container') || this.createFlashContainer();
        container.appendChild(notification);

        // Auto-supprimer après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    createFlashContainer() {
        // Créer un container pour les flashs s'il n'existe pas
        let container = document.getElementById('flash-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'flash-container';
            container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999999;
            width: 350px;
        `;
            document.body.appendChild(container);
        }
        return container;
    }


    setupTurboEvents() {
        // Intercepter les réponses AJAX de Turbo
        document.addEventListener('turbo:before-fetch-response', (event) => {
            const response = event.detail.fetchResponse;
            const contentType = response.contentType;

            if (contentType && contentType.includes('application/json')) {
                response.json().then(data => {
                    if (data.message) {
                        // Fermer le modal s'il est ouvert
                        this.closeModal();

                        // Afficher le message selon le statut
                        setTimeout(() => {
                            if (data.status === 'success') {
                                flasher.success(data.message);
                            } else if (data.status === 'warning') {
                                flasher.warning(data.message);
                            } else if (data.status === 'error') {
                                flasher.error(data.message);
                            }
                        }, 300);
                    }
                });
            }
        });
    }
}
