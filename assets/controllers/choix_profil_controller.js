import { Controller } from '@hotwired/stimulus';
import LocalDbController from './local_db_controller.js';

export default class extends Controller {
    static targets = ['container'];

    async select(event) {
        event.preventDefault();
        const url = event.currentTarget.getAttribute('href');
        console.log(url)

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error("Erreur lors de la récupération du profil");

            const data = await response.json();
            console.log("✅ Données profil reçues:", data);

            // ✅ Appel centralisé
            await LocalDbController.saveToIndexedDB(data);

            Turbo.visit('/accueil');

        } catch (error) {
            console.error("❌ Erreur sélection profil :", error);
            alert("Impossible de charger ce profil. Veuillez réessayer.");
        }
    }
}
