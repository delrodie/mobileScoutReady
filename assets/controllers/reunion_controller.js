import { Controller} from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";

export default class extends Controller {
    static targets =[
        'scoutSlug', 'scoutInstance', 'reunionTemplate', 'emptyReunionSection',
        'listReunion'
    ];
    static values = {
        apiUrlReunionList: String
    }


    connect() {
        this.remplirChamps();
        this.loadReunions();
    }

    async remplirChamps() {
        try {
            const profil = await LocalDbController.getAllFromStore('profil');
            const instance = await LocalDbController.getAllFromStore('profil_instance')

            // Si aucun profil alors rediriger vers l'authentification
            if (!profil || profil.length === 0){
                console.warn("Auncun profil trouvé en local");
                Turbo.visit('/intro');
                return;
            }

            if (this.hasScoutSlugTarget){
                this.scoutSlugTarget.value = profil[0].slug;
            }

            if (this.hasScoutInstanceTarget){ console.log(`Instance: ${instance[0].id}`)
                this.scoutInstanceTarget.value = instance[0].id
            }
        } catch (e){
            console.error("Erreur de lecture de la BD locale: ", e);
        }
    }

    async loadReunions() {
        console.log('Chargement de la liste des reunions')
        try{
            const profil = await LocalDbController.getAllFromStore('profil');
            const instance = await LocalDbController.getAllFromStore('profil_instance');

            // Si aucun profil alors rediriger vers l'authentification
            if (!profil || profil.length === 0){
                console.warn("Aucun profil en local")
                Turbo.visit('/intro');
                return;
            }

            const response = await fetch(this.apiUrlReunionListValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    slug: profil[0].slug,
                    code: profil[0].code,
                    instance: instance && instance.length > 0 ? instance[0].id : null,
                    parentId: instance && instance.length > 0 ? instance[0].parentId : null,
                })
            });

            if(!response.ok) throw new Error('Erreur API');

            console.log('Reponses reunions:')

            const responseData = await response.json();
            const reunions = responseData.data || responseData;

            console.log(reunions);

            if (!reunions || reunions.length === 0){
                this.emptyReunionSectionTarget.classList.remove('d-none');
                this.listReunionTarget.classList.add('d-none');
                return;
            }
            reunions.forEach(reunion =>{
                this.affecteReunion(reunion);
            })
        } catch (e) {
            console.error("Erreur : ", e);
        }
    }

    affecteReunion(reunion) {
        const clone = this.reunionTemplateTarget.content.cloneNode(true);

        const elInstance = clone.querySelector('.reunion-instance');
        if (elInstance) elInstance.textContent = reunion.instance.nom || 'Non défini';

        const elChamp = clone.querySelector('.reunion-champ');
        if (elChamp) elChamp.textContent = reunion.champs.titre || 'Non défini';

        const elTitre = clone.querySelector('.reunion-titre');
        if (elTitre) elTitre.textContent = reunion.titre || 'Non défini';

        const elObjectif = clone.querySelector('.reunion-objectif');
        if (elObjectif) elObjectif.textContent = reunion.objectif || 'Non défini';

        const elLieu = clone.querySelector('.reunion-lieu');
        if (elLieu) elLieu.textContent = reunion.lieu || 'Non défini';

        const elCible = clone.querySelector('.reunion-cible');
        if (elCible) elCible.textContent = reunion.cible || 'Non défini';

        const elPeriode = clone.querySelector('.reunion-periode');
        if (elPeriode){
            const date = reunion.dateAt || 'Non défini';
            const heureDebut = reunion.heureDebut || 'Non d"fini';
            const heureFin = reunion.heureFin || 'Non d"fini';

            elPeriode.innerHTML =`<i class="bi bi-calendar-event text-body"></i> ${date} de ${heureDebut} à ${heureFin}`
        }

        console.log(reunion.urlShow)
        const elUrlDetail = clone.querySelector('.reunion-details-url');
        if (elUrlDetail) elUrlDetail.href= reunion.urlShow;

        this.listReunionTarget.appendChild(clone);
    }
}
