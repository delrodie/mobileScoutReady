import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = [
        "district",
        "groupe",
        "branche",
        "districtselected",
        "groupelist",
        "brancheselect"
    ];

    static values = {
        fonction: String,
        statut: String // 👈 on ajoute le statut transmis depuis Twig
    };

    connect() {
        console.log("Formulaire chargé");
        this.toggleFields();
        this.groupelistTarget.disabled = true;
        this.brancheselectTarget.setAttribute('readonly', 'readonly');
        // console.log(this.statutValue);
    }

    toggleFields(event) {
        const fonction = event ? event.target.value : this.element.querySelector("select").value;
        console.log("Fonction sélectionnée :", fonction);

        // Cache et nettoie tout
        this.hideAndUnrequire(this.districtTarget);
        this.hideAndUnrequire(this.groupeTarget);
        this.hideAndUnrequire(this.brancheTarget);

        if (fonction === "DISTRICT") {
            this.showAndRequire(this.districtTarget);
        } else if (fonction === "GROUPE") {
            this.showAndRequire(this.districtTarget);
            this.showAndRequire(this.groupeTarget);
        } else if (fonction === "UNITE") {
            this.showAndRequire(this.districtTarget);
            this.showAndRequire(this.groupeTarget);
            this.showAndRequire(this.brancheTarget);
            this.brancheselectTarget.removeAttribute("readonly");
        }
    }

    showAndRequire(target) {
        target.classList.remove("d-none");
        const input = target.querySelector("select, input");
        if (input) {
            input.removeAttribute("disabled");
            input.setAttribute("required", "required");
        }
    }

    hideAndUnrequire(target) {
        target.classList.add("d-none");
        const input = target.querySelector("select, input");
        if (input) {
            input.setAttribute("disabled", "disabled");
            input.removeAttribute("required");
        }
    }

    async loadGroupes() {
        const districtId = this.districtselectedTarget.value;
        this.groupelistTarget.disabled = false;
        this.groupelistTarget.innerHTML = '<option value="">Chargement...</option>';

        if (!districtId) {
            this.groupelistTarget.innerHTML = '<option value="">Sélectionnez votre district d\'abord...</option>';
            return;
        }

        try {
            const response = await fetch(`/api/instance/groupes?district=${districtId}`);
            const data = await response.json();
            this.groupelistTarget.innerHTML = '<option value=""> -- Sélectionnez votre groupe -- </option>';
            data.forEach(groupe => {
                const option = document.createElement('option');
                option.value = groupe.id;
                option.textContent = groupe.nom;
                this.groupelistTarget.appendChild(option);
            });
        } catch (error) {
            console.log("Erreur lors du chargement des groupes : ", error);
        }
    }

    // ✅ Validation complète avant soumission
    validateBeforeSubmit(event) {
        const fonction = this.element.querySelector("select[name='_fonction']")?.value || null;
        const statut = this.statutValue || null;

        console.log(statut);

        const invalidFields = [];

        // Vérifie District, Groupe, Branche selon la fonction
        if (fonction === "DISTRICT" || fonction === "GROUPE" || fonction === "UNITE") {
            const districtInput = this.districtTarget.querySelector("select");
            if (districtInput && !districtInput.value) invalidFields.push(districtInput);
        }

        if (fonction === "GROUPE" || fonction === "UNITE") {
            const groupeInput = this.groupeTarget.querySelector("select");
            if (groupeInput && !groupeInput.value) invalidFields.push(groupeInput);
        }

        if (fonction === "UNITE") {
            const brancheInput = this.brancheTarget.querySelector("select");
            if (brancheInput && !brancheInput.value) invalidFields.push(brancheInput);
        }

        // ✅ Vérifie le champ Poste si statut == ADULTE
        if (statut === "ADULTE") {
            const posteInput = document.querySelector("#inscriptionPostedetails");
            console.log(posteInput);
            if (posteInput && !posteInput.value.trim()) invalidFields.push(posteInput);
        }

        // Gestion visuelle et message
        invalidFields.forEach((input) => {
            input.classList.add("is-invalid");
        });

        if (invalidFields.length > 0) {
            event.preventDefault();
            alert("Veuillez remplir tous les champs obligatoires avant de continuer.");
        }
    }
}
