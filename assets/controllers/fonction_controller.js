import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["district", "groupe", "branche", "districtselected", "groupelist", "brancheselect"];
    static values = { fonction: String };

    connect() {
        console.log("Formulaire chargé");
        this.toggleFields(); // pour init au chargement
        // Désactivons p groupe
        this.groupelistTarget.disabled = true;
        // this.districtselectedTarget.disabled = true;
        this.brancheselectTarget.disabled = true;
    }

    toggleFields(event) {
        // si l'événement existe, on prend sa valeur ; sinon on regarde dans le select
        const fonction = event ? event.target.value : this.element.querySelector("select").value;
        console.log("Fonction sélectionnée :", fonction);

        // cacher tous par défaut
        // this.districtTarget.classList.add("hidden");
        // this.groupeTarget.classList.add("hidden");
        // this.branchTarget.clssList.add('hidden');

        // conditions d’affichage
        if (fonction === "REGIONAL") {
            this.districtselectedTarget.disabled = true;
            this.brancheselectTarget.disabled = true;

            this.districtTarget.classList.add("hidden");
            this.groupeTarget.classList.add("hidden");
            this.brancheTarget.classList.add("hidden");

        } else if (fonction === "Equipe de district") {
            this.districtselectedTarget.disabled = false;
            this.brancheselectTarget.disabled = true;

            this.districtTarget.classList.remove("hidden");
            this.groupeTarget.classList.add("hidden");
            this.brancheTarget.classList.add("hidden");

        } else if (fonction === "GROUPE") {
            this.districtselectedTarget.disabled = false;
            this.brancheselectTarget.disabled = true;

            this.districtTarget.classList.remove("hidden");
            this.groupeTarget.classList.remove("hidden");
            this.brancheTarget.classList.add("hidden");

        } else if (fonction === "UNITE") {
            this.districtselectedTarget.disabled = false;
            this.brancheselectTarget.disabled = false;

            this.districtTarget.classList.remove("hidden");
            this.groupeTarget.classList.remove("hidden");
            this.brancheTarget.classList.remove("hidden");
        }
    }

    async loadGroupes(){
        const districtId = this.districtselectedTarget.value
        // Désactivons p groupe
        this.groupelistTarget.disabled = false;
        this.groupelistTarget.innerHTML = '<option value="">Chargement...</option>'

        if (!districtId) {
            this.groupelistTarget.innerHTML = '<option value="">Sélectionnez votre district d\'abord...</option>'
            return
        }

        try{
            const response = await fetch(`/api/instance/groupes?district=${districtId}`)
            const data = await response.json();

            // Netoyer la liste des groupes
            this.groupelistTarget.innerHTML = '<option value=""> -- Sélectionnez votre groupe -- </option>'

            // Ajouter les nouvelles options
            data.forEach(groupe =>{
                const option = document.createElement('option')
                option.value = groupe.id
                option.textContent = groupe.nom
                this.groupelistTarget.appendChild(option)
            })
        } catch (error) {
            console.log("Erreur los du chargement des groupes : ", error)
        }
    }
}
