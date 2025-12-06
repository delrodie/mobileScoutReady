import { Controller } from "@hotwired/stimulus";
import { Camera } from '@capacitor/camera';
import ScannerController from "../js/utils/ScannerController.js";
import LocalDbController from "./local_db_controller.js";
import flasher from "@flasher/flasher";

export default class extends Controller {
    static values = {activiteId: String};
    static targets = ["modal"];

    connect() {
        this.scanner = new ScannerController({
            webContainerId: "qr-reader",
            onScan: this.scanSuccess.bind(this),
        });
    }

    disconnect() {
        this.scanner.stop();
    }

    // MODIFIÉ : Nouvelle méthode pour ouvrir la modale et démarrer le scan
    async openModal() {
        try{
            const permission = await Camera.checkPermissions();

            if (permission.camera !== 'granted') {
                const requestResult = await Camera.requestPermissions({ permissions: ['camera'] });

                if (requestResult.camera !== 'granted') {
                    // L'utilisateur a refusé. Affichez une alerte ou un message d'erreur.
                    flasher.error("Permission de la caméra refusée. Impossible de scanner.");
                    return; // Stoppe l'ouverture de la modale et du scanner
                }
            }

            // Étape B : Si la permission est GRANTED, ouvrez la modale et démarrez le scanner

            // 1. Retire d-none et ajoute show/d-flex
            this.modalTarget.classList.remove('d-none');
            this.modalTarget.classList.add('show', 'd-flex');
            document.body.classList.add('modal-open');

            // 2. Démarre le scanner (avec le délai pour la stabilisation du DOM)
            setTimeout(() => {
                this.scanner.start();
            }, 100);
        } catch (e) {
            console.error("Erreur lors de la gestion des permissions de la caméra: ", e);
        }

    }

    // NOUVEAU : Nouvelle méthode pour fermer la modale et arrêter le scan
    closeModal() {
        // 1. Arrête le scanner
        this.scanner.stop();

        // 2. Masque l'élément en ajoutant d-none et retirant show/d-flex
        this.modalTarget.classList.add('d-none');
        this.modalTarget.classList.remove('show', 'd-flex');

        // OPTIONNEL : Si vous avez ajouté la classe 'modal-open'
        document.body.classList.remove('modal-open');
    }

    // startScan() {
    //     this.scanner.start();
    // }

    async scanSuccess(code){
        this.closeModal();
        await this.sendPointage(code);
    }

    processScan(code) {
        const activiteId = this.activiteIdValue;

        console.log("QR détecté :", code);
        const url = `/pointage/?activite=${this.activiteIdValue}`;

        // Exemple : envoyer vers Symfony
        fetch(url, {
            method: "POST",
            body: new URLSearchParams({ code})
        });
    }

    async sendPointage(code) {
        const activiteId = this.activiteIdValue;
        const profil =  await LocalDbController.getAllFromStore('profil');
        const url = `/pointage/`;

        // Si aucun profil alors rediriger vers l'authentification
        if (!profil || profil.length === 0) {
            console.warn("Aucun profil trouvé en local");
            Turbo.visit('/intro')
            return;
        }

        try{
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
            if(!response.ok){
                throw  new Error(`Erreur HTTP: ${response.status}`)
            }

            const data = await response.json();
            if (data.status === 'success') {
                // Correction de l'URL (si ce n'est pas déjà fait)
                const targetUrl = `/activites/${this.activiteIdValue}`;

                // SOLUTION: Forcer Turbo à utiliser la méthode GET
                Turbo.visit(targetUrl, { action: 'replace', method: 'get' });

                return;
            }
            else if (data.status === 'warning') flasher.warning(data.message);
            else flasher.error(data.message);
        } catch (error){
            // console.error();
            flasher.error("Erreur lors de l'envoi du pointage: ", error)
        }
    }
}
