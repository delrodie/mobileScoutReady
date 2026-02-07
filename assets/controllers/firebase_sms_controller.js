import { Controller } from '@hotwired/stimulus';
import { initializeApp } from 'firebase/app';
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from 'firebase/auth';
import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';
import { firebaseConfig } from "../../assets/firebaseConfig.js";

/**
 * Contrôleur Firebase SIMPLIFIÉ pour SMS OTP
 * Gère l'envoi et la vérification des SMS via Firebase Auth
 */
export default class extends Controller {
    static values = {
        phone: String
    }

    async connect() {
        console.log("📱 Firebase SMS Controller connecté");

        // Initialiser Firebase
        this.initializeFirebase();
    }

    /**
     * Initialise Firebase avec votre configuration
     */
    initializeFirebase() {
        try {
            // ✅ VOTRE CONFIGURATION FIREBASE ICI
            // Allez sur Firebase Console > Paramètres du projet > Config
            const configFirebase = {
                apiKey: firebaseConfig.apiKey, // "VOTRE_API_KEY",
                authDomain: firebaseConfig.authDomain, // "VOTRE_PROJECT_ID.firebaseapp.com",
                projectId: firebaseConfig.projectId, // "VOTRE_PROJECT_ID",
                storageBucket: firebaseConfig.storageBucket, // "VOTRE_PROJECT_ID.appspot.com",
                messagingSenderId: firebaseConfig.messagingSenderId, // "VOTRE_MESSAGING_SENDER_ID",
                appId: firebaseConfig.appId // "VOTRE_APP_ID"
            };

            console.log(configFirebase);

            // Initialiser Firebase
            const app = initializeApp(configFirebase);
            this.auth = getAuth(app);
            this.auth.languageCode = 'fr'; // SMS en français

            console.log('✅ Firebase Auth initialisé');

        } catch (error) {
            console.error('❌ Erreur initialisation Firebase:', error);
            Toast.show({
                text: '❌ Erreur Firebase',
                duration: 'long'
            });
        }
    }

    /**
     * Envoie un code OTP par SMS
     * Cette méthode est appelée depuis search_phone_controller
     */
    async sendSmsOtp(phoneNumber) {
        try {
            console.log('📤 Envoi SMS OTP pour:', phoneNumber);

            // Formater le numéro au format international
            const formattedPhone = this.formatPhoneNumber(phoneNumber);
            console.log('📱 Numéro formaté:', formattedPhone);

            // Configurer reCAPTCHA (nécessaire pour le web, pas pour mobile)
            if (!Capacitor.isNativePlatform()) {
                await this.setupRecaptcha();
            }

            Toast.show({
                text: '📨 Envoi du SMS...',
                duration: 'short'
            });

            // ✅ ENVOYER LE SMS via Firebase
            const confirmationResult = await signInWithPhoneNumber(
                this.auth,
                formattedPhone,
                this.recaptchaVerifier || undefined
            );

            // Sauvegarder pour vérification ultérieure
            window.confirmationResult = confirmationResult;

            console.log('✅ SMS envoyé avec succès');

            Toast.show({
                text: '✅ SMS envoyé !',
                duration: 'short'
            });

            // Notifier que le SMS a été envoyé
            window.dispatchEvent(new CustomEvent('sms-otp-sent', {
                detail: { phoneNumber: formattedPhone }
            }));

            return {
                success: true,
                phoneNumber: formattedPhone
            };

        } catch (error) {
            console.error('❌ Erreur envoi SMS:', error);

            // Messages d'erreur personnalisés
            let errorMessage = 'Erreur lors de l\'envoi du SMS';

            if (error.code === 'auth/invalid-phone-number') {
                errorMessage = 'Numéro de téléphone invalide';
            } else if (error.code === 'auth/too-many-requests') {
                errorMessage = 'Trop de tentatives. Réessayez dans quelques minutes';
            } else if (error.code === 'auth/quota-exceeded') {
                errorMessage = 'Quota SMS dépassé';
            }

            Toast.show({
                text: `❌ ${errorMessage}`,
                duration: 'long'
            });

            return {
                success: false,
                error: errorMessage
            };
        }
    }

    /**
     * Vérifie le code OTP saisi par l'utilisateur
     */
    async verifySmsOtp(code) {
        try {
            console.log('🔍 Vérification code OTP');

            if (!window.confirmationResult) {
                throw new Error('Session expirée, veuillez redemander un code');
            }

            Toast.show({
                text: '⏳ Vérification...',
                duration: 'short'
            });

            // ✅ VÉRIFIER LE CODE avec Firebase
            const result = await window.confirmationResult.confirm(code);

            // Récupérer l'ID token (optionnel, pour auth serveur)
            const idToken = await result.user.getIdToken();

            console.log('✅ Code vérifié par Firebase');
            console.log('👤 UID:', result.user.uid);
            console.log('📞 Phone:', result.user.phoneNumber);

            Toast.show({
                text: '✅ Code validé !',
                duration: 'short'
            });

            return {
                success: true,
                uid: result.user.uid,
                phoneNumber: result.user.phoneNumber,
                idToken: idToken
            };

        } catch (error) {
            console.error('❌ Erreur vérification:', error);

            let errorMessage = 'Code invalide';

            if (error.code === 'auth/invalid-verification-code') {
                errorMessage = 'Code incorrect';
            } else if (error.code === 'auth/code-expired') {
                errorMessage = 'Code expiré';
            } else if (error.message) {
                errorMessage = error.message;
            }

            Toast.show({
                text: `❌ ${errorMessage}`,
                duration: 'long'
            });

            return {
                success: false,
                error: errorMessage
            };
        }
    }

    /**
     * Configure reCAPTCHA pour le web
     * (Pas nécessaire sur mobile natif)
     */
    async setupRecaptcha() {
        if (this.recaptchaVerifier) {
            return; // Déjà configuré
        }

        try {
            // Créer le conteneur si absent
            if (!document.getElementById('recaptcha-container')) {
                const container = document.createElement('div');
                container.id = 'recaptcha-container';
                document.body.appendChild(container);
            }

            this.recaptchaVerifier = new RecaptchaVerifier(
                this.auth,
                'recaptcha-container',
                {
                    'size': 'invisible',
                    'callback': () => {
                        console.log('✅ reCAPTCHA résolu');
                    }
                }
            );

            await this.recaptchaVerifier.render();
            console.log('✅ reCAPTCHA configuré');

        } catch (error) {
            console.error('❌ Erreur reCAPTCHA:', error);
        }
    }

    /**
     * Formate le numéro au format international (+225XXXXXXXXXX)
     */
    formatPhoneNumber(phoneNumber) {
        let phone = phoneNumber.replace(/[^0-9]/g, '');

        if (phone.startsWith('0')) {
            phone = '225' + phone.substring(1);
        }

        if (!phone.startsWith('225')) {
            phone = '225' + phone;
        }

        return '+' + phone;
    }

    /**
     * Récupère les infos du device
     */
    async getDeviceInfo() {
        try {
            const info = await Device.getInfo();
            const id = await Device.getId();

            return {
                deviceId: id.identifier,
                platform: info.platform,
                model: info.model || info.manufacturer,
                osVersion: info.osVersion
            };
        } catch (error) {
            console.error('Erreur device info:', error);
            return {
                deviceId: this.generateDeviceId(),
                platform: 'web',
                model: navigator.userAgent,
                osVersion: 'unknown'
            };
        }
    }

    /**
     * Génère un ID device pour le web
     */
    generateDeviceId() {
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'web_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
    }

    /**
     * Déconnexion Firebase
     */
    async signOut() {
        try {
            await this.auth.signOut();
            window.confirmationResult = null;
            console.log('👋 Déconnexion Firebase');
        } catch (error) {
            console.error('Erreur déconnexion:', error);
        }
    }
}
