import { Controller } from "@hotwired/stimulus";

const DB_NAME = 'db_scoutready';

/**
 * Contrôleur responsable de la déconnexion :
 * - Supprime entièrement la base locale IndexedDB
 * - Redirige vers /intro/phone
 */
export default class extends Controller {
    static values = {
        redirectUrl: String
    }

    connect() {
        console.log("🚪 LogoutController connecté");
    }

    async logout(event) {
        event.preventDefault();

        // Étape 1 : suppression de la base IndexedDB
        try {
            await this.deleteLocalDatabase(DB_NAME);
            console.log("🧹 Base locale supprimée avec succès !");
        } catch (err) {
            console.error("Erreur suppression IndexedDB :", err);
        }

        // Étape 2 : redirection
        const redirectTo = this.redirectUrlValue || "/intro/phone";
        window.location.href = redirectTo;
    }

    deleteLocalDatabase(name) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.deleteDatabase(name);

            request.onsuccess = () => resolve(true);
            request.onerror = (event) => reject(event.target.error);
            request.onblocked = () => {
                console.warn("⚠️ Suppression bloquée : la base est encore ouverte dans un autre onglet.");
                reject("Blocked");
            };
        });
    }
}
