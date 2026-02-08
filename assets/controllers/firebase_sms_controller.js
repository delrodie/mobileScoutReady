import { Controller } from '@hotwired/stimulus';
import { initializeApp } from 'firebase/app';
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from 'firebase/auth';
import { Device } from '@capacitor/device';
import { Capacitor } from '@capacitor/core';
import { Toast } from '@capacitor/toast';
import {firebaseConfig} from "../firebaseConfig.js";

/**
 * Contrôleur Firebase pour SMS OTP
 * Gère UNIQUEMENT l'envoi et la vérification des SMS
 */
export default class extends Controller {
    async connect() {
        console.log("📱 Firebase SMS Controller connecté");

        // Initialiser Firebase
        this.initializeFirebase();
    }

    initializeFirebase() {
        try {
            // ⚙️ CONFIGURATION FIREBASE
            // Récupérez ces valeurs depuis: Firebase Console > Paramètres du projet > Config
            // const configFirebase = {
            //     apiKey: firebaseConfig.apiKey, //"VOTRE_API_KEY_ICI",
            //     authDomain: "VOTRE_PROJECT_ID.firebaseapp.com",
            //     projectId: "VOTRE_PROJECT_ID",
            //     storageBucket: "VOTRE_PROJECT_ID.appspot.com",
            //     messagingSenderId: "VOTRE_SENDER_ID",
            //     appId: "VOTRE_APP_ID"
            // };

            // Initialiser Firebase
            const app = initializeApp(firebaseConfig);
            this.auth = getAuth(app);
            this.auth.languageCode = 'fr';

            console.log('✅ Firebase Auth initialisé');

        } catch (error) {
            console.error('❌ Erreur init Firebase:', error);
        }
    }

    /**
     * Envoie un SMS OTP via Firebase
     * Appelé par search_phone_controller
     */
    async sendSmsOtp(phoneNumber) {
        try {
            console.log('📤 Envoi SMS OTP pour:', phoneNumber);

            const formattedPhone = this.formatPhoneNumber(phoneNumber);
            console.log('📱 Numéro formaté:', formattedPhone);

            // Configurer reCAPTCHA (web uniquement)
            if (!Capacitor.isNativePlatform()) {
                await this.setupRecaptcha();
            }

            Toast.show({
                text: '📨 Envoi du SMS...',
                duration: 'short'
            });

            // ✅ ENVOYER LE SMS
            const confirmationResult = await signInWithPhoneNumber(
                this.auth,
                formattedPhone,
                this.recaptchaVerifier || undefined
            );

            // Sauvegarder pour vérification
            window.confirmationResult = confirmationResult;

            console.log('✅ SMS envoyé avec succès');

            Toast.show({
                text: '✅ SMS envoyé !',
                duration: 'short'
            });

            return {
                success: true,
                phoneNumber: formattedPhone
            };

        } catch (error) {
            console.error('❌ Erreur envoi SMS:', error);

            let errorMessage = 'Erreur lors de l\'envoi du SMS';

            if (error.code === 'auth/invalid-phone-number') {
                errorMessage = 'Numéro invalide';
            } else if (error.code === 'auth/too-many-requests') {
                errorMessage = 'Trop de tentatives. Réessayez plus tard';
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
     * Vérifie le code OTP saisi
     */
    async verifySmsOtp(code) {
        try {
            console.log('🔍 Vérification code OTP');

            if (!window.confirmationResult) {
                throw new Error('Session expirée');
            }

            Toast.show({
                text: '⏳ Vérification...',
                duration: 'short'
            });

            // ✅ VÉRIFIER LE CODE
            const result = await window.confirmationResult.confirm(code);

            console.log('✅ Code vérifié par Firebase');
            console.log('👤 UID:', result.user.uid);

            Toast.show({
                text: '✅ Code validé !',
                duration: 'short'
            });

            return {
                success: true,
                uid: result.user.uid,
                phoneNumber: result.user.phoneNumber
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
     * Configure reCAPTCHA (web uniquement)
     */
    async setupRecaptcha() {
        if (this.recaptchaVerifier) {
            return;
        }

        try {
            if (!document.getElementById('recaptcha-container')) {
                const container = document.createElement('div');
                container.id = 'recaptcha-container';
                document.body.appendChild(container);
            }

            this.recaptchaVerifier = new RecaptchaVerifier(
                this.auth,
                'recaptcha-container',
                { 'size': 'invisible' }
            );

            await this.recaptchaVerifier.render();
            console.log('✅ reCAPTCHA configuré');

        } catch (error) {
            console.error('❌ Erreur reCAPTCHA:', error);
        }
    }

    /**
     * Formate le numéro au format international
     */
    formatPhoneNumber(phoneNumber) {
        let phone = phoneNumber.replace(/[^0-9]/g, '');

        if (!phone.startsWith('0')) {
            //phone = '225' + phone.substring(1);
            Toast.show({text: "Le numero de telephone est incorrect", duration: 'short'})
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
     * Génère un ID device
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
     * Déconnexion
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
