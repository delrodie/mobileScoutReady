import { Controller } from "@hotwired/stimulus";
import { CapacitorBarcodeScanner } from '@capacitor/barcode-scanner';
import LocalDbController from "./local_db_controller.js";
import flasher from "@flasher/flasher";

export default class extends Controller {
    static values = {activiteId: String};
    static targets = ["modal"];

    connect() {
        console.log("Scanner controller connecté");
        this.isScanning = false;
    }

    disconnect() {
        this.stopScan();
    }

    async openModal() {
        console.log("Tentative d'ouverture du modal et du scanner");

        try {
            // Afficher le modal d'abord
            this.modalTarget.classList.remove('d-none');
            this.modalTarget.classList.add('show', 'd-flex');
            document.body.classList.add('modal-open');

            // Attendre un peu que le modal soit visible
            setTimeout(async () => {
                await this.startScan();
            }, 300);

        } catch (error) {
            console.error("Erreur lors de l'ouverture du modal: ", error);
            flasher.error("Erreur lors de l'ouverture du scanner");
            this.closeModal();
        }
    }

    async startScan() {
        console.log("Démarrage du scan...");

        try {
            // 1. Vérifier si BarcodeScanner est disponible
            if (!CapacitorBarcodeScanner || !CapacitorBarcodeScanner.checkPermission) {
                console.error("BarcodeScanner n'est pas disponible");
                flasher.error("Scanner non disponible. Vérifiez l'installation.");
                return;
            }

            // 2. Vérifier les permissions
            console.log("Vérification des permissions...");
            const status = await CapacitorBarcodeScanner.checkPermission({ force: true });
            console.log("Statut des permissions:", status);

            if (status.granted) {
                console.log("Permissions accordées, démarrage du scanner...");

                // Cacher l'arrière-plan de l'app
                await CapacitorBarcodeScanner.hideBackground();
                document.body.style.background = "transparent";

                // Préparer le scanner
                await CapacitorBarcodeScanner.prepare();

                // Définir les formats à scanner
                const supportedFormats = await CapacitorBarcodeScanner.getSupportedFormats();
                console.log("Formats supportés:", supportedFormats);

                // Démarrer le scan avec options
                this.isScanning = true;
                const result = await CapacitorBarcodeScanner.startScan({
                    targetedFormats: [
                        'QR_CODE',
                        'DATA_MATRIX',
                        'UPC_E',
                        'UPC_A',
                        'EAN_8',
                        'EAN_13',
                        'CODE_128',
                        'CODE_39',
                        'CODE_93',
                        'CODABAR',
                        'ITF'
                    ]
                });

                console.log("Résultat du scan:", result);

                if (result.hasContent) {
                    console.log("QR Code détecté:", result.content);
                    await this.stopScan();
                    await this.closeModal();
                    await this.sendPointage(result.content);
                }

            } else if (status.denied) {
                console.log("Permissions refusées définitivement");
                flasher.error("L'accès à la caméra a été refusé définitivement. Veuillez l'activer dans les paramètres de l'application.");

                // Optionnel : ouvrir les paramètres de l'application
                if (CapacitorBarcodeScanner.openAppSettings) {
                    await CapacitorBarcodeScanner.openAppSettings();
                }
                this.closeModal();

            } else {
                console.log("Demande de permissions...");
                const requestStatus = await CapacitorBarcodeScanner.requestPermission();
                console.log("Résultat de la demande:", requestStatus);

                if (requestStatus.granted) {
                    console.log("Permissions accordées après demande");
                    // Redémarrer le scan
                    await this.startScan();
                } else {
                    console.log("Permissions refusées après demande");
                    flasher.error("Permission de la caméra refusée. Impossible de scanner.");
                    this.closeModal();
                }
            }

        } catch (error) {
            console.error("Erreur détaillée lors du scan:", error);

            // Vérifier le type d'erreur
            if (error.message && error.message.includes('user closed')) {
                console.log("L'utilisateur a fermé le scanner");
                this.closeModal();
            } else if (error.message && error.message.includes('Permission denied')) {
                flasher.error("Permission refusée. Veuillez autoriser l'accès à la caméra.");
                this.closeModal();
            } else if (error.message && error.message.includes('No camera')) {
                flasher.error("Aucune caméra disponible sur cet appareil.");
                this.closeModal();
            } else {
                flasher.error("Erreur lors de l'accès à la caméra: " + error.message);
                this.closeModal();
            }
        }
    }

    async stopScan() {
        if (!this.isScanning) return;

        console.log("Arrêt du scanner...");
        try {
            await CapacitorBarcodeScanner.stopScan();
            await CapacitorBarcodeScanner.showBackground();
            document.body.style.background = "";
            this.isScanning = false;
            console.log("Scanner arrêté");
        } catch (error) {
            console.error("Erreur lors de l'arrêt du scanner:", error);
        }
    }

    async closeModal() {
        console.log("Fermeture du modal");
        await this.stopScan();

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
