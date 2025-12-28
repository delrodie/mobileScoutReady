// scanner_activite_controller.js
import { Controller } from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";
import flasher from "@flasher/flasher";

export default class extends Controller {
    static values = {activiteId: String};
    static targets = ["modal", "scannerContainer"];

    scanner = null;
    isNative = false;
    html5QrCode = null;

    connect() {
        console.log("Scanner controller connecté");

        // Détecter l'environnement
        this.isNative = this.isCapacitorNative();
        console.log("Environnement détecté:", this.isNative ? "Natif Capacitor" : "Navigateur Web");

        if (this.isNative) {
            console.log("Utilisera le scanner natif");
        } else {
            console.log("Utilisera le scanner web");
        }
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

            // Importer dynamiquement le scanner Capacitor
            const { BarcodeScanner } = await import('@capacitor/barcode-scanner');

            // Vérifier les permissions
            const status = await BarcodeScanner.checkPermission({ force: true });

            if (status.granted) {
                await BarcodeScanner.hideBackground();
                document.body.style.background = "transparent";

                await BarcodeScanner.prepare();

                const result = await BarcodeScanner.startScan({
                    targetedFormats: ['QR_CODE']
                });

                if (result.hasContent) {
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
                if (requestStatus.granted) {
                    await this.startNativeScan();
                } else {
                    flasher.error("Permission refusée");
                    this.closeModal();
                }
            }
        } catch (error) {
            console.error("Erreur scan natif:", error);

            if (error.message && error.message.includes('user closed')) {
                console.log("Utilisateur a annulé");
            } else {
                flasher.error("Erreur scan: " + error.message);
            }
            this.closeModal();
        }
    }

    async startWebScan() {
        try {
            console.log("Démarrage du scan web...");

            // Importer dynamiquement le scanner web
            const { Html5QrcodeScanner } = await import('html5-qrcode');

            // Configuration du scanner web
            this.scanner = new Html5QrcodeScanner(
                "qr-reader",
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    rememberLastUsedCamera: true,
                    supportedScanTypes: [0, 1] // 0 = camera, 1 = fichier
                },
                false // verbose
            );

            // Démarrer le scan
            this.scanner.render(
                (decodedText) => {
                    console.log("QR Code détecté:", decodedText);
                    if (this.scanner) {
                        this.scanner.clear();
                    }
                    this.closeModal();
                    this.sendPointage(decodedText);
                },
                (error) => {
                    // Ignorer les erreurs de scan (arrêt normal)
                    console.log("Scan arrêté ou erreur:", error);
                }
            );

        } catch (error) {
            console.error("Erreur scan web:", error);
            flasher.error("Erreur scan web: " + error.message);
            this.closeModal();
        }
    }

    stopScan() {
        // Arrêter le scanner selon le type
        if (this.isNative) {
            // Pour Capacitor, on ne peut pas arrêter de l'extérieur
            // C'est géré par le plugin lui-même
        } else {
            // Arrêter le scanner web
            if (this.scanner) {
                this.scanner.clear();
                this.scanner = null;
            }
        }
    }

    closeModal() {
        this.stopScan();

        // Masquer la modale
        this.modalTarget.classList.add('d-none');
        this.modalTarget.classList.remove('show', 'd-flex');
        document.body.classList.remove('modal-open');
        document.body.style.background = "";
    }

    async sendPointage(code) {
        console.log("Envoi du pointage avec code:", code);
        const activiteId = this.activiteIdValue;
        const profil = await LocalDbController.getAllFromStore('profil');
        const url = `/pointage/`;

        // Si aucun profil alors rediriger vers l'authentification
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
