import { Controller } from "@hotwired/stimulus";
import localDb from "./local_db_controller.js";

export default class extends Controller {
    static targets = [
        "AvatarLoader", "nomPrenom", "code", "matricule", "qrCodeFile",
        "nom", "prenom", "sexe", "dateNaissance", "telephone", "phoneParent", "email",
        "scoutSlug", "scoutId", "fonctionId", "poste", "detailPoste",
        "branche", "annee", "validation", "instanceId", "instanceSlug",
        "instanceNom", "instanceType", "instanceParent", "parentId", "parentNom",
        "instanceRegion", "instanceASN", "instanceDistrict", "instanceGroupe"
    ];

    connect() {
        console.log("✅ profil_controller connecté");
        this.populateHeader();
    }

    async populateHeader() {
        try {
            const profilData = await localDb.getAllFromStore("profil");
            const fonction = await localDb.getAllFromStore("profil_fonction");
            const instance = await localDb.getAllFromStore("profil_instance");

            if (!profilData.length) {
                console.warn("⚠️ Aucune donnée profil trouvée en local");
                return;
            }

            const profil = profilData[0];
            console.log("📦 Profil local trouvé :", profil);

            this.updateDOM(profil, fonction[0] ?? {}, instance[0] ?? {});
        } catch (error) {
            console.error("❌ Erreur lecture profil local :", error);
        }
    }

    updateDOM(profil, fonction, instance) {
        console.log(`🧾 Chargement du profil : ${profil.nom ?? "?"} ${profil.prenom ?? ""}`);

        // --- Avatar ---
        if (this.hasAvatarLoaderTarget) {
            const newSrc = profil.avatar?.startsWith("/avatar/")
                ? profil.avatar
                : `/avatar/${profil.avatar ?? "avatar_homme.png"}`;

            this.avatarLoaderTarget.dataset.imageLoaderSrcValue = newSrc;

            const imageLoaderController = this.application.getControllerForElementAndIdentifier(
                this.avatarLoaderTarget,
                "image-loader"
            );

            if (imageLoaderController) {
                imageLoaderController.loadImage();
            }
        }

        // --- QrCode --- qrCodeFile
        console.log(`QrCodeFile: ${profil.qrCodeFile}`)
        if (this.hasQrCodeFileTarget) {
            console.log(`Image : ${this.qrCodeFileTarget}`)
            const newSrc = profil.qrCodeFile?.startsWith("/qrcode/")
                ? profil.qrCodeFile
                : `/qrcode/${profil.qrCodeFile ?? "qr-code.png"}`;

            this.qrCodeFileTarget.dataset.imageLoaderSrcValue = newSrc;

            const imageLoaderController = this.application.getControllerForElementAndIdentifier(
                this.qrCodeFileTarget,
                "image-loader"
            );

            if (imageLoaderController) {
                imageLoaderController.loadImage();
            }
        }


        // --- Profil principal ---
        this.setField("nomPrenom", `${profil.nom ?? ""} ${profil.prenom ?? ""}`);
        this.setField("nom", profil.nom);
        this.setField("prenom", profil.prenom);
        this.setField("sexe", profil.sexe);

        if (profil.dateNaissance) {
            const date = new Date(profil.dateNaissance);
            const jour = String(date.getDate()).padStart(2, "0");
            const mois = String(date.getMonth() + 1).padStart(2, "0");
            const annee = date.getFullYear();
            this.setField("dateNaissance", `${jour}-${mois}-${annee}`);
        }

        this.setField("telephone", profil.telephone);
        this.setField("phoneParent", profil.isParent === "true" ? "OUI" : "NON");
        this.setField("email", profil.email);
        this.setField("code", profil.code);
        this.setField("scoutSlug", profil.scoutSlug);
        this.setField("scoutId", profil.scoutId);

        // --- Fonction ---
        this.setField("matricule", profil.matricule);
        this.setField("poste", fonction.poste);
        this.setField("branche", fonction.branche);
        this.setField("annee", fonction.annee);
        this.setField("validation", fonction.validation === "true" ? "Fonction validée" : "Fonction non validée");
        this.setField("detailPoste", fonction.detailPoste)

        // --- Instances hiérarchiques ---
        if (fonction.type === "REGIONAL") {
            this.setField("instanceASN", instance.parentNom);
            this.setField("instanceRegion", instance.nom);
        }

        if (fonction.type === "DISTRICT") {
            this.setField("instanceRegion", instance.parentNom);
            this.setField("instanceDistrict", instance.nom);
        }

        if (fonction.type === "GROUPE" || fonction.type === "UNITE") {
            this.setField("instanceDistrict", instance.parentNom);
            this.setField("instanceGroupe", instance.nom);
        }

        this.hideEmptyWrappers();
    }

    setField(fieldName, value) {
        const targetName = `${fieldName}Target`;
        const hasTarget = `has${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}Target`;

        if (this[hasTarget]) {
            const el = this[targetName];
            const val = (value ?? "").toString().trim();
            el.textContent = val;
            this.toggleVisibility(fieldName, val);
        }
    }

    toggleVisibility(wrapperName, value) {
        const wrapper = this.element.querySelector(`[data-profil-wrapper="${wrapperName}"]`);
        if (wrapper) {
            const visible = value && value.trim() !== "";
            if (visible) {
                wrapper.style.display = "flex";
                wrapper.classList.remove("d-none");
            } else {
                wrapper.style.display = "none";
                wrapper.classList.add("d-none");
            }
            console.log(`🔹 [${wrapperName}] → ${visible ? "visible" : "masqué"} (${value})`);
        }
    }

    hideEmptyWrappers() {
        this.element.querySelectorAll("[data-profil-wrapper]").forEach((wrapper) => {
            // 💡 Cible l'élément 'span' qui a un data-profil-target, qui est la donnée réelle.
            const dataElement = wrapper.querySelector("span[data-profil-target]");

            // Assurez-vous que l'élément cible existe et récupérez son contenu
            const content = dataElement ? dataElement.textContent.trim() : "";

            const visible = content !== "";

            // Applique la visibilité basée uniquement sur le contenu du data-profil-target
            wrapper.style.display = visible ? "flex" : "none";
            wrapper.classList.toggle("d-none", !visible);

            // Optionnel : Vous pouvez aussi enlever la ligne console.log de toggleVisibility
            // pour éviter la confusion, car hideEmptyWrappers est la version finale
            // console.log(`🔹 [${wrapper.dataset.profilWrapper}] → ${visible ? "visible" : "masqué"} (${content})`);
        });
    }
}
