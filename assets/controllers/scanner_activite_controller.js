import { Controller } from "@hotwired/stimulus";
import ScannerController from "../js/utils/ScannerController.js";
import LocalDbController from "./local_db_controller.js";
import AutorisationController  from "./autorisation_controller.js";
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
    openModal() {
        this.modalTarget.classList.remove('d-none'); // Affiche la modale (Cache Bootstrap : d-none)
        this.scanner.start(); // Démarre le scanner
    }

    // NOUVEAU : Nouvelle méthode pour fermer la modale et arrêter le scan
    closeModal() {
        this.scanner.stop(); // Arrête le scanner (important pour libérer la caméra)
        this.modalTarget.classList.add('d-none'); // Masque la modale
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
                AutorisationController.interractionValues();
                Turbo.visit('/activites/', activiteId);
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
