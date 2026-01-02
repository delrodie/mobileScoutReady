import { Controller } from "@hotwired/stimulus";
import localDbController, { DB_NAME, DB_VERSION } from "./local_db_controller.js";
import { Toast } from "@capacitor/toast";

export default class extends Controller {
    static targets = [
        "initialisation", "tableau", "urlAdd", "urlEdit", "urlDelete",
        "branche", "isFormation", "baseNv1", "baseNv1Annee", "baseNv2", "baseNv2Annee",
        "avanceNv1", "avanceNv1Annee", "avanceNv2", "avanceNv2Annee", "avanceNv3", "avanceNv3Annee",
        "avanceNv4", "avanceNv4Annee", "certification",
        "showDateBaseNv1", "showDateBaseNv2", "showDateAvanceNv1", "showDateAvanceNv2", "showDateAvanceNv3", "showDateAvanceNv4"
    ];

    connect(){
        // console.log('Initalisation des infos complementaires');
        this.verifProfil();
        this.formulaire();
    }

    async verifProfil() {
        try{
            const profil = await localDbController.getAllFromStore('profil');
            const infos_complementaire = await localDbController.getAllFromStore('profil_infocomplementaire');

            if (!infos_complementaire.length){
                Toast.show({text: "Veuillez enregistrer vos informations complementaires", duration:'short', position:"bottom"})
                console.warn("Auncune information complementaire enregistrée")
                console.log(profil[0]);

                if (this.hasInitialisationTarget){
                    this.initialisationTarget.classList.remove("d-none");
                    this.tableauTarget.classList.add("d-none");

                    if (this.hasUrlAddTarget){
                        this.urlAddTarget.href=`/profil-edit/${profil[0].slug}/infos-complementaires`;
                    }
                }
            }else{
                this.tableauTarget.classList.remove("d-none");

                this.hasUrlEditTarget ? this.urlEditTarget.href = `/profil-edit/${profil[0].slug}/infos-complementaires` : "#";

                if (this.hasBrancheTarget) this.brancheTarget.innerText = infos_complementaire[0].branche
                if (this.hasIsFormationTarget) {
                    let formation = infos_complementaire[0].isFormation === false ? 'NON' : 'OUI';
                    this.isFormationTarget.innerText = formation;
                }

                if (this.hasBaseNv1Target) this.baseNv1Target.innerText = infos_complementaire[0].stageBaseNiveau1 ?? 'Aucun';
                if (this.hasShowDateBaseNv1Target){
                    infos_complementaire[0].stageBaseNiveau1
                        ? this.showDateBaseNv1Target.classList.remove('d-none')
                        : this.showDateBaseNv1Target.classList.add('d-none');

                    this.baseNv1AnneeTarget.innerText = infos_complementaire[0].anneeBaseNiveau1 ?? 'N/D'
                }

                if (this.hasBaseNv2Target) this.baseNv2Target.innerText = infos_complementaire[0].stageBaseNiveau2 ?? 'Aucun';
                if (this.hasShowDateBaseNv2Target){
                    infos_complementaire[0].stageBaseNiveau2
                        ? this.showDateBaseNv2Target.classList.remove('d-none')
                        : this.showDateBaseNv2Target.classList.add('d-none');

                    this.baseNv2AnneeTarget.innerText = infos_complementaire[0].anneeBaseNiveau2 ?? 'N/D'
                }

                if (this.hasAvanceNv1Target) this.avanceNv1Target.innerText = infos_complementaire[0].stageAvanceNiveau1 ?? 'Aucun';
                if (this.hasShowDateAvanceNv1Target){
                    infos_complementaire[0].stageAvanceNiveau1
                        ? this.showDateAvanceNv1Target.classList.remove('d-none')
                        : this.showDateAvanceNv1Target.classList.add('d-none');

                    this.avanceNv1AnneeTarget.innerText = infos_complementaire[0].anneeAvanceNiveau1 ?? 'N/D'
                }

                if (this.hasAvanceNv2Target) this.avanceNv2Target.innerText = infos_complementaire[0].stageAvanceNiveau2 ?? 'Aucun';
                if (this.hasShowDateAvanceNv2Target){
                    infos_complementaire[0].stageAvanceNiveau2
                        ? this.showDateAvanceNv2Target.classList.remove('d-none')
                        : this.showDateAvanceNv2Target.classList.add('d-none');

                    this.avanceNv2AnneeTarget.innerText = infos_complementaire[0].anneeAvanceNiveau2 ?? 'N/D'
                }

                if (this.hasAvanceNv3Target) this.avanceNv3Target.innerText = infos_complementaire[0].stageAvanceNiveau3 ?? 'Aucun';
                if (this.hasShowDateAvanceNv3Target){
                    infos_complementaire[0].stageAvanceNiveau3
                        ? this.showDateAvanceNv3Target.classList.remove('d-none')
                        : this.showDateAvanceNv3Target.classList.add('d-none');

                    this.avanceNv3AnneeTarget.innerText = infos_complementaire[0].anneeAvanceNiveau3 ?? 'N/D'
                }

                if (this.hasAvanceNv4Target) this.avanceNv4Target.innerText = infos_complementaire[0].stageAvanceNiveau4 ?? 'Aucun';
                if (this.hasShowDateAvanceNv4Target){
                    infos_complementaire[0].stageAvanceNiveau4
                        ? this.showDateAvanceNv4Target.classList.remove('d-none')
                        : this.showDateAvanceNv4Target.classList.add('d-none');

                    this.avanceNv4AnneeTarget.innerText = infos_complementaire[0].anneeAvanceNiveau4 ?? 'N/D'
                }
            }

            console.log("Information complementaires ")
        } catch (e) {
            Toast.show({text: "Erreur de vérification des infos complementaires", duration: 'long', position: "bottom" })
            console.error(" Erreur de vérification infos complementaires :, e");
        }
    }

    formulaire() {
        // 1. État initial : désactiver tous les champs de formation au chargement
        this.toggleAllFormations(this.isFormationTarget.checked);

        // 2. Listener sur l'interrupteur "Avez-vous déjà participé à une formation ?"
        this.isFormationTarget.addEventListener('change', (e) => {
            this.toggleAllFormations(e.target.checked);
        });

        // 3. Listeners sur chaque sélecteur de stage pour gérer l'année
        const stages = [
            { select: "baseNv1", year: "baseNv1Annee" },
            { select: "baseNv2", year: "baseNv2Annee" },
            { select: "avanceNv1", year: "avanceNv1Annee" },
            { select: "avanceNv2", year: "avanceNv2Annee" },
            { select: "avanceNv3", year: "avanceNv3Annee" },
            { select: "avanceNv4", year: "avanceNv4Annee" }
        ];

        stages.forEach(group => {
            if (this[`has${this.capitalize(group.select)}Target`]) {
                const selectEl = this[`${group.select}Target`];

                selectEl.addEventListener('change', () => {
                    this.updateYearStatus(group.select, group.year);
                });
            }
        });
    }

    // Sauvegarde des informations complementaires
    async save(event)
    {
        event.preventDefault();

        if (!this.certificationTarget.checked){
            Toast.show({ text: "Vous devez certifier l'exactitude des informations", duration: "short"});
            return;
        }

        if(!navigator.onLine){
            Toast.show({
                text: "Connexion internet requise pour la sauvgarde.",
                duration: "long"
            });
            return;
        }

        const formElement = event.target.closest('form');
        const formData = new FormData(formElement);

        if(this.isFormationTarget.checked){
            formData.set('_complementIsFormation', 'on');
        } else{
            formData.delete('_complementIsFormation');
        }

        // Affiche le loader
        localDbController.showLoader();

        try{
            console.log("Soumission du formulaire")
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error("Erreur serveur lors de la sauvegarde");

            const result = await response.json();
            console.log('==== RESULTAT ====')
            console.warn(result)

            if(result.status === 'success'){
                console.log("Sauvegarde serveur réussie. Synchronisation locale encours....")

                await localDbController.saveToIndexedDB(result.data);

                Toast.show({
                    text: "Informations enregistrées et synchronisées !",
                    duration: 'short',
                    position: 'bottom'
                });

                setTimeout(() => {
                    if(result.redirect){
                        window.location.href = result.redirect;
                    }else {
                        window.location.href = "/profil";
                    }
                }, 1000);
            } else {
                throw new Error("Erreur serveur lors de la sauvegarde");
            }
        } catch (e) {
            console.error("Erreur save:", e.message);
            localDbController.hideLoader();
            Toast.show({text: 'Erreur: ' + e.message, duration: "long"})
        }
    }

    // Active ou désactive l'ensemble des champs liés aux formations
    toggleAllFormations(isEnabled) {
        const fields = [
            'baseNv1', 'baseNv1Annee', 'baseNv2', 'baseNv2Annee',
            'avanceNv1', 'avanceNv1Annee', 'avanceNv2', 'avanceNv2Annee',
            'avanceNv3', 'avanceNv3Annee', 'avanceNv4', 'avanceNv4Annee'
        ];

        fields.forEach(field => {
            if (this[`has${this.capitalize(field)}Target`]) {
                const element = this[`${field}Target`];
                element.disabled = !isEnabled;

                // Si on désactive tout, on retire aussi le caractère obligatoire
                if (!isEnabled) {
                    element.required = false;
                    element.value = ""; // Optionnel : réinitialise la valeur
                }
            }
        });

        // Si activé, on recalcule l'état des années selon les sélections actuelles
        if (isEnabled) {
            this.formulaire(); // Relance la logique de dépendance sélecteur/année
        }
    }

    // Gère l'activation et l'obligation de l'année pour un stage précis
    updateYearStatus(selectTargetName, yearTargetName) {
        const selectEl = this[`${selectTargetName}Target`];
        const yearEl = this[`${yearTargetName}Target`];

        if (selectEl.value && selectEl.value !== "") {
            yearEl.disabled = false;
            yearEl.required = true;
        } else {
            yearEl.disabled = true;
            yearEl.required = false;
            yearEl.value = "";
        }
    }

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}
