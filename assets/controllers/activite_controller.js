import { Controller} from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";

export default class extends Controller {
    static targets = [
        'scoutSlug', 'scoutInstance', 'emptySection', 'listSection', 'carouselSection', "carouselSection",
        'template', 'listActivite', 'listCarousel', 'carouselTemplate', 'indicator', 'scan'
    ];
    static values = {
        apiUrlList : String,
        activite
    }

    connect() {
        this.remplirChamps();
        this.loadActivites();
        this.bootstrapTabs();
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
            // console.log('Activité en charge....')
            // console.log(profil[0].slug)

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

    async loadActivites(){
        try{
            const profil = await LocalDbController.getAllFromStore('profil');
            const instance = await LocalDbController.getAllFromStore('profil_instance');

            // Si aucun profil alors rediriger vers l'authentification
            if (!profil || profil.length === 0) {
                console.warn("Aucun profil trouvé en local");
                Turbo.visit('/intro')
                return;
            }


            const response = await fetch(this.apiUrlListValue,{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    slug: profil[0].slug,
                    code: profil[0].code,
                    instance: instance && instance.length > 0 ? instance[0].id : null,
                    parentId: instance && instance.length > 0 ? instance[0].parentId : null,
                })
            });

            if (!response.ok) throw new Error('Erreur API');

            const responseData = await response.json();

            const activites = responseData.data || responseData;
            console.log(activites)
            let carouselInt = false;


            this.carouselSectionTarget.classList.add('d-none');
            if (!activites || activites.length === 0){
                this.emptySectionTarget.classList.remove('d-none');
                this.listSectionTarget.classList.add('d-none');
                this.carouselSectionTarget.classList.add('d-none');
                return;
            }


            // Liste des activités
            activites.forEach(activite =>{
                this.affecteActivite(activite);
                if (activite.promotion === true){
                    this.affecteCarousel(activite);
                    let carouselInt = true;
                    this.carouselSectionTarget.classList.remove('d-none');
                }
            })


        } catch (e) {
            console.error("Erreur de lecture de la BD locale :", e);
        }
    }

    affecteActivite(activite) {
        const clone = this.templateTarget.content.cloneNode(true);

        const elInstance = clone.querySelector('.activite-instance');
        if (elInstance) elInstance.textContent = activite.instance.nom || 'Non défini';

        const elTitre = clone.querySelector('.activite-titre');
        if (elTitre) elTitre.textContent = activite.titre || 'Non défini';

        const elPeriode = clone.querySelector('.activite-periode');
        if (elPeriode){
            const dateDebut = activite.dateDebut || 'Non défini';
            const dateFin = activite.dateFin || 'Non d"fini';

            elPeriode.innerHTML =`<i class="bi bi-calendar-event text-body"></i> ${dateDebut} - ${dateFin}`
        }

        const elImage = clone.querySelector('.activite-img');
        if (elImage) elImage.src= activite.affiche

        const elUrlDetail = clone.querySelector('.activite-details-url');
        if (elUrlDetail) elUrlDetail.href= activite.urlShow;

        this.listActiviteTarget.appendChild(clone);
    }

    affecteCarousel(activite) {
        const clone = this.carouselTemplateTarget.content.cloneNode(true);

        const elImgCarousel = clone.querySelector('.img-carousel');
        if (elImgCarousel) elImgCarousel.src = activite.affiche || 'Non défini';

        const elUrlDetail = clone.querySelector('.carousel-link');
        if (elUrlDetail) elUrlDetail.href= activite.urlShow;

        this.listCarouselTarget.appendChild(clone);

    }

    bootstrapTabs() {
        console.log('tabs ouvert')
        // 1. Sélectionner tous les déclencheurs d'onglets (buttons avec data-bs-toggle="tab")
        const triggerTabList = this.element.querySelectorAll('[data-bs-toggle="tab"]');

        // 2. Parcourir la liste pour créer une instance Bootstrap.Tab pour chacun
        triggerTabList.forEach(triggerEl => {
            // S'assurer que l'instance n'a pas déjà été créée pour éviter les erreurs
            // C'est particulièrement utile si le contenu n'est PAS un turbo-frame.
            if (!bootstrap.Tab.getInstance(triggerEl)) {
                // Initialise l'onglet Bootstrap
                new bootstrap.Tab(triggerEl);
            }
        });
    }
}
