import { Controller } from "@hotwired/stimulus";
import localDb from "./local_db_controller.js";

export default class extends Controller {
    static targets = ["avatar", "nom", "code", "fonctions", "instances"];

    connect() {
        this.populateHeader();
        console.log('header_controller')
    }

    async populateHeader() {
        try {
            const profilData = await localDb.getAllFromStore("profil");
            const fonction = await localDb.getAllFromStore("profil_fonction");
            const instance = await localDb.getAllFromStore("profil_instance");

            if (!profilData.length) {
                console.warn("Aucune donnée profil trouvée en local");
                return;
            }

            const profil = profilData[0]; // un seul profil local attendu
            this.updateDOM(profil, fonction, instance);
        } catch (error) {
            console.error("Erreur lecture header local :", error);
        }
    }

    updateDOM(profil, fonction, instance) {
        console.log(`Mon profil dataLocal : ${profil.code}`)
        if (this.hasAvatarTarget) {
            this.avatarTarget.src = profil.avatar ?? "/assets/img/avatar/avatar_homme.png";
        }

        if (this.hasNomTarget) {
            this.nomTarget.textContent = `${profil.nom ?? ""} ${profil.prenom ?? ""}`;
        }

        if (this.hasCodeTarget) {
            this.codeTarget.textContent = `${profil.code ?? ""}`;
        }

        if (this.hasFonctionsTarget) {
            this.fonctionsTarget.textContent = fonction.poste ?? "";
        }

        if (this.hasInstancesTarget) {
            this.instancesTarget.textContent = instance.nom ?? "";
        }
    }
}
