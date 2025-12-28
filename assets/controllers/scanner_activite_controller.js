import { Controller } from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";
import flasher from "@flasher/flasher";

export default class extends Controller {
    static values = {activiteId: String};
    static targets = ["modal", "scannerContainer", "nativeMessage", "webContainer", "loading"];

    scanner = null;
    isNative = false;
    html5QrCode = null; // Pour stocker l'instance du scanner web

    connect() {
        console.log("Scanner controller connecté");

        // Détecter l'environnement
        this.isNative = this.isCapacitorNative();
        console.log("Environnement:", this.isNative ? "Natif" : "Web");
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
            } else if (error.message && error.message.includes('Permission')) {
                flasher.error("Permission caméra requise");
            } else {
                flasher.error("Erreur scanner: " + error.message);
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
        const activiteId = this.activiteIdValue;
        const profil = await LocalDbController.getAllFromStore('profil');
        const url = `/pointage/`;

        if (!profil || profil.length === 0) {
            console.warn("Aucun profil trouvé en local");
            Turbo.visit('/intro')
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams({
                    activite: activiteId,
                    code: code,
                    pointeur: profil[0].code,
                }),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
            });

            console.log("Réponse reçue:", response.status);

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`)
            }

            const data = await response.json();
            console.log("Données reçues:", data);

            if (data.status === 'success') {
                const targetUrl = `/activites/${this.activiteIdValue}`;
                Turbo.visit(targetUrl, { action: 'replace', method: 'get' });
                return;
            } else if (data.status === 'warning') {
                flasher.warning(data.message);
            } else {
                flasher.error(data.message);
            }
        } catch (error) {
            console.error("Erreur lors de l'envoi du pointage:", error);
            flasher.error("Erreur lors de l'envoi du pointage: " + error.message);
        }
    }
}
