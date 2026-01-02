// assets/controllers/profile_sync_controller.js
import { Controller } from "@hotwired/stimulus";
import LocalDbController, { DB_NAME, DB_VERSION } from "./local_db_controller.js";
import { Toast } from "@capacitor/toast";

export default class extends Controller {
    static targets = ["form"];

    async handleSubmission(event) {
        event.preventDefault();

        if (!navigator.onLine) {
            alert("Impossible de faire la mise à jour car vous êtes hors connexion. Veuillez vous connecter à Internet pour synchroniser votre profil.");
            return; // On arrête tout ici
        }

        const form = event.currentTarget;
        const formData = new FormData(form);

        // 1. Affichage de VOTRE loader personnalisé (celui avec le spinner blanc sur fond noir)
        LocalDbController.showLoader();

        try {
            // 2. Envoi des données au serveur (SQL)
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error("Erreur serveur");

            const result = await response.json();

            if (result.status === 'success') {
                console.log('Data synchronisation')
                console.log(result.data)
                // 3. Mise à jour de la base locale avec les constantes importées
                await this.updateIndexedDB(result.data);

                Toast.show({text: "Mise à jour et synchronisation effectuées avec succès! ", duration: 'long', position: 'bottom'})

                // 4. Redirection vers le profil
                window.location.href = result.redirect;
            } else {
                alert("Erreur : " + result.message);
                LocalDbController.hideLoader();
            }
        } catch (error) {
            console.error("Erreur de synchronisation :", error);
            LocalDbController.hideLoader();
            alert("Impossible de synchroniser les données. Vérifiez votre connexion.");
        }
    }

    updateIndexedDB(data) {
        return new Promise((resolve, reject) => {
            // Utilisation des constantes centralisées
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onsuccess = (event) => {
                const db = event.target.result;
                const transaction = db.transaction(['profil'], 'readwrite');
                const store = transaction.objectStore('profil');

                // Mise à jour de l'entrée (put remplace si l'ID existe)
                const putRequest = store.put(data);

                putRequest.onsuccess = () => resolve();
                putRequest.onerror = () => reject("Erreur lors de l'écriture locale");
            };

            request.onerror = () => reject("Impossible d'ouvrir la base locale");
        });
    }
}
