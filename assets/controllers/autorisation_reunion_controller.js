import { Controller} from "@hotwired/stimulus";
import LocalDbController from "./local_db_controller.js";

export default class extends Controller {
    static targets = [
        'scan', 'btnAction', 'statistiques', 'participantCount', 'noteMoyen'
    ];
    static values = {
        reunionId: String,
    }

    connect() {
        this.userAccess();
        this.interractionValues()
    }

    async userAccess() {
        try{
            const profil = await LocalDbController.getAllFromStore('profil');
            const apiUrl = `/api/reunion/autorisation`

            // Si aucun profil alors rediriger vers l'authentification
            if (!profil || profil.length === 0) {
                console.warn("Aucun profil trouvé en local");
                Turbo.visit('/intro')
                return;
            }

            console.log('Reunion ID: ', this.reunionIdValue)

            const response = await fetch(apiUrl,{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    slug: profil[0].slug,
                    code: profil[0].code,
                    reunion: this.reunionIdValue
                })
            });

            if (!response.ok) throw new Error('Erreur API');

            const responseData = await response.json();

            const autorisation = responseData.data || responseData;
            console.log(autorisation)
            console.log('Access: ', autorisation.access)

            this.scanTarget.classList.add('d-none');
            this.btnActionTarget.classList.add('d-none');

            if (autorisation.access === true) { console.log("Vérifié true")
                this.scanTarget.classList.remove('d-none');

                // Désactivation des boutons d'actions
                if (autorisation.role === "CREATEUR"){
                    this.btnActionTarget.classList.remove('d-none');
                }

            }

        } catch (e) {
            this.scanTarget.classList.add('d-none');
            console.error('Erreur user access : ', e)
        }
    }

     async interractionValues(){
        try{
            const urlGetNombre = `/api/reunion/nombre/${this.reunionIdValue}`;

            const response = await fetch(urlGetNombre, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    reunion: this.reunionIdValue
                })
            });
            console.log('statistques', this.reunionIdValue)
            console.log(response);

            if (!response.ok) throw new Error('Erreur API');

            const responseData = await  response.json();
           // const nombre = responseData.data || responseData;

            // console.log('Nombre', nombre.ok)
            console.log(responseData)

            if (this.hasParticipantCountTarget){
                this.participantCountTarget.textContent = responseData.participant || 'ND';
            }

            if (this.hasnoteMoyenTarget){
                this.noteMoyenTarget.textContent = responseData.note || 'ND' ;
            }

        }catch (e) {
            console.error("Interraction: ", e)
        }
    }
}
