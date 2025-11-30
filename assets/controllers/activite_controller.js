import { Controller} from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";

export default class extends Controller {
    static targets = ['scoutSlug', 'scoutInstance'];

    connect() {
        this.remplirChamps();
    }

    async remplirChamps() {
        try {
            const profil = await LocalDbController.getAllFromStore('profil');
            const instance = await LocalDbController.getAllFromStore('profil_instance');

            // Si aucun profil alors rediriger vers l'authentification
            if (!profil || profil.length === 0) {
                console.warn("Aucun profil trouvé en local");
                Turbo.visit('/intro')
                return;
            }
            console.log('Activité en charge....')
            console.log(profil[0].slug)

            if (this.hasScoutSlugTarget){
                this.scoutSlugTarget.value = profil[0].slug;
            }

            if (this.hasScoutInstanceTarget){
                this.scoutInstanceTarget.value = instance[0].id;
            }
        } catch (e) {
            console.error("Erreur de lecture de la BD locale :", e);
        }
    }
}
