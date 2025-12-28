import { Controller } from "@hotwired/stimulus";
import { CapacitorBarcodeScanner } from '@capacitor/barcode-scanner';
import LocalDbController from "./local_db_controller.js";
import flasher from "@flasher/flasher";

export default class extends Controller {
    static values = {activiteId: String};
    static targets = ["modal", "scannerContainer"];

    connect() {
        // Pas besoin d'initialiser ScannerController avec BarcodeScanner
    }

    disconnect() {
        this.stopScan();
    }

    async openModal() {
        try {
            // Vérifier les permissions
            const status = await CapacitorBarcodeScanner.checkPermission({ force: true });

            if (status.granted) {
                // Permissions accordées, ouvrir la modale et démarrer le scan
                this.modalTarget.classList.remove('d-none');
                this.modalTarget.classList.add('show', 'd-flex');
                document.body.classList.add('modal-open');

                // Démarrer le scan avec un léger délai
                setTimeout(() => {
                    this.startScan();
                }, 100);
            } else if (status.denied) {
                // Permissions refusées de façon permanente
                flasher.error("L'accès à la caméra a été refusé définitivement. Veuillez l'activer dans les paramètres de l'application.");
                await CapacitorBarcodeScanner.openAppSettings();
            } else {
                // Demander les permissions
                const requestStatus = await CapacitorBarcodeScanner.requestPermission();

                if (requestStatus.granted) {
                    this.modalTarget.classList.remove('d-none');
                    this.modalTarget.classList.add('show', 'd-flex');
                    document.body.classList.add('modal-open');

                    setTimeout(() => {
                        this.startScan();
                    }, 100);
                } else {
                    flasher.error("Permission de la caméra refusée. Impossible de scanner.");
                }
            }
        } catch (error) {
            console.error("Erreur lors de la gestion des permissions: ", error);
            flasher.error("Erreur lors de l'accès à la caméra");
        }
    }

    async startScan() {
        try {
            // Cacher le fond de l'application
            await CapacitorBarcodeScanner.hideBackground();

            // Préparer le scanner
            await CapacitorBarcodeScanner.prepare();

            // Démarrer le scan
            const result = await CapacitorBarcodeScanner.startScan();

            if (result.hasContent) {
                // Un QR code a été détecté
                this.stopScan();
                this.closeModal();
                await this.sendPointage(result.content);
            }
        } catch (error) {
            console.error("Erreur lors du scan: ", error);

            // Si l'utilisateur annule le scan
            if (error.message === 'user closed the scanner') {
                this.closeModal();
            } else {
                flasher.error("Erreur lors du scan: " + error.message);
                this.closeModal();
            }
        }
    }

    async stopScan() {
        try {
            await CapacitorBarcodeScanner.stopScan();
            await CapacitorBarcodeScanner.showBackground();
        } catch (error) {
            console.error("Erreur lors de l'arrêt du scan: ", error);
        }
    }

    closeModal() {
        this.stopScan();

        // Masquer la modale
        this.modalTarget.classList.add('d-none');
        this.modalTarget.classList.remove('show', 'd-flex');
        document.body.classList.remove('modal-open');
    }

    async sendPointage(code) {
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

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`)
            }

            const data = await response.json();
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
            console.error("Erreur lors de l'envoi du pointage: ", error);
            flasher.error("Erreur lors de l'envoi du pointage");
        }
    }
}
