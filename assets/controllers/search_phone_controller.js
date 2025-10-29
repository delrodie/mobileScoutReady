import { Controller } from '@hotwired/stimulus';
import localDb from "./local_db_controller.js";

const DB_NAME = 'db_scoutready'
const DB_VERSION = 1

export default class extends Controller {
    static targets = ['form', 'phone']

    async submit(event) {
        event.preventDefault()

        const form = this.formTarget
        const formData = new FormData(form)
        console.log(formData);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })

            if (!response.ok) throw new Error("Erreur serveur")

            const data = await response.json()
            console.log("✅ Données reçues du backend:", data)

            await this.saveToIndexedDB(data)
            // await localDb.saveProfilData(data)

            // Redirection vers /accueil après succès
            // window.location.href="/accueil"
            Turbo.visit('/accueil')

        } catch (error) {
            console.error("❌ Erreur lors de la soumission :", error)
            alert("Une erreur est survenue. Vérifiez votre connexion.")
        }
    }

    async saveToIndexedDB(data) {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION)

            request.onupgradeneeded = (event) => {
                const db = event.target.result
                db.createObjectStore('profil', { keyPath: 'slug' })
                db.createObjectStore('profil_fonction', { keyPath: 'id' })
                db.createObjectStore('profil_instance', { keyPath: 'id' })
            }

            request.onsuccess = (event) => {
                const db = event.target.result
                const tx = db.transaction(['profil', 'profil_fonction', 'profil_instance'], 'readwrite')

                // Nettoyage avant nouvelle insertion
                tx.objectStore('profil').clear()
                tx.objectStore('profil_fonction').clear()
                tx.objectStore('profil_instance').clear()

                // Insertion
                tx.objectStore('profil').put(data.profil)
                tx.objectStore('profil_fonction').put(data.profil_fonction)
                tx.objectStore('profil_instance').put(data.profil_instance)

                tx.oncomplete = () => {
                    console.log("💾 Données sauvegardées avec succès dans IndexedDB")
                    resolve()
                }

                tx.onerror = (e) => reject(e.target.error)
            }

            request.onerror = (e) => reject(e.target.error)
        })
    }
}
