import { Controller } from "@hotwired/stimulus";
import localDb from "./local_db_controller.js";

export default class extends Controller {
    static targets = [
        "avatar", "nomPrenom", "code", "fonctions", "instances",
        "nom", "prenom", "sexe", "dateNaissance", "telephone", "phoneParent", "email"
    ];

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

        if (this.hasNomPrenomTarget) {
            this.nomPrenomTarget.textContent = `${profil.nom ?? ""} ${profil.prenom ?? ""}`;
        }

        if(this.hasNomTarget){
            this.nomTarget.textContent = `${profil.nom ?? ""}`;
        }

        if (this.hasPrenomTarget){
            this.prenomTarget.textContent = `${profil.prenom ?? ""}`;
        }

        if (this.hasSexeTarget){
            this.sexeTarget.textContent = `${profil.sexe ?? ""}`;
        }

        if (this.hasDateNaissanceTarget && profil.dateNaissance) {
            const date = new Date(profil.dateNaissance);
            const jour = String(date.getDate()).padStart(2, '0');
            const mois = String(date.getMonth() + 1).padStart(2, '0'); // Les mois commencent à 0
            const annee = date.getFullYear();
            this.dateNaissanceTarget.textContent = `${jour}-${mois}-${annee}`;
        }


        if (this.hasTelephoneTarget){
            this.telephoneTarget.textContent = `${profil.telephone ?? ""}`;
        }

        if (this.hasPhoneParentTarget){
            this.phoneParentTarget.textContent = `${profil.isParent === 'true' ? 'OUI' : 'NON'}`;
        }

        if (this.hasEmailTarget){
            this.emailTarget.textContent = `${profil.email ?? ""}`;
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
