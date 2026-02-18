<?php

namespace App\Services;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Enum\Branche;
use App\Enum\FonctionPoste;
use App\Enum\ScoutStatut;
use App\Repository\FonctionRepository;
use App\Repository\ScoutRepository;
use App\Repository\UtilisateurRepository;

/**
 * Service pour gérer les cibles de notifications prédéfinies
 */
class NotificationCibleService
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
    ) {
    }

    /**
     * Récupère les utilisateurs correspondant à une cible prédéfinie
     */
    public function getUtilisateursParCible(string $cible): array
    {
        return match ($cible) {
            Notification::CIBLE_TOUS_JEUNES => $this->getTousJeunes(),
            Notification::CIBLE_TOUS_ADULTES => $this->getTousAdultes(),
            Notification::CIBLE_CHEFS_UNITES => $this->getChefsUnites(),
            Notification::CIBLE_EQUIPE_REGIONALE => $this->getEquipeRegionale(),
            Notification::CIBLE_CD => $this->getCD(),
            Notification::CIBLE_EQUIPE_DISTRICT => $this->getEquipeDistrict(),
            Notification::CIBLE_CG => $this->getCG(),
            Notification::CIBLE_MAITRISE_GROUPE => $this->getMaitriseGroupe(),
            Notification::CIBLE_CHEFS_OISILLONS => $this->getChefsOisillons(),
            Notification::CIBLE_CHEFS_MEUTE => $this->getChefsMeute(),
            Notification::CIBLE_CHEFS_TROUPE => $this->getChefsTroupe(),
            Notification::CIBLE_CHEFS_GENERATION => $this->getChefsGeneration(),
            Notification::CIBLE_CHEFS_COMMUNAUTE => $this->getChefsCommunaute(),
            Notification::CIBLE_OISILLONS => $this->getOisillons(),
            Notification::CIBLE_LOUVETEAUX => $this->getLouveteaux(),
            Notification::CIBLE_ECLAIREURS => $this->getEclaireurs(),
            Notification::CIBLE_CHEMINOTS => $this->getCheminots(),
            Notification::CIBLE_ROUTIERS => $this->getRoutiers(),
            default => [],
        };
    }

    // ───────────────────────────────────────────────────────────────
    // Méthodes pour chaque cible
    // ───────────────────────────────────────────────────────────────

    /**
     * Tous les jeunes (Oisillons + Louveteaux + Éclaireurs + Cheminots + Routiers)
     */
    private function getTousJeunes()
    {
        return $this->utilisateurRepository->findByScoutStatut((ScoutStatut::JEUNE)->value);
    }

    /**
     * Tous les adultes (tous les chefs + équipes)
     */
    private function getTousAdultes()
    {
        return $this->utilisateurRepository->findByScoutStatut((ScoutStatut::ADULTE)->value);
    }

    /**
     * Tous les chefs d'unités
     */
    private function getChefsUnites(): array
    {
        return $this->utilisateurRepository->findByPoste((FonctionPoste::UNITE)->value);
    }

    /**
     * Équipe régionale
     */
    private function getEquipeRegionale(): array
    {
        return $this->utilisateurRepository->findByPoste((FonctionPoste::REGIONAL)->value);
    }

    /**
     * CD (Commissaires de District)
     */
    private function getCD(): array
    {
        return $this->utilisateurRepository->findByDetailPoste([
            'CD', 'Commissaire de District', 'COMMISSAIRE DE DISTRICT', 'commissaire de district'
        ]);
    }

    /**
     * Équipe de district
     */
    private function getEquipeDistrict(): array
    {
        return $this->utilisateurRepository->findByPoste((FonctionPoste::DISTRICT)->value);
    }

    /**
     * CG (Chefs de Groupe)
     */
    private function getCG(): array
    {
        return $this->utilisateurRepository->findByDetailPoste([
            'CG', 'Chef de Groupe', 'CHEF DE GROUPE', 'chef de groupe'
        ]);
    }

    /**
     * Maîtrise de groupe
     */
    private function getMaitriseGroupe(): array
    {
        return $this->utilisateurRepository->findByPoste((FonctionPoste::GROUPE)->value);
    }

    /**
     * Chefs des oisillons
     */
    private function getChefsOisillons(): array
    {
        return $this->utilisateurRepository->findByDetailPoste([
            'CDBO', 'MICOU', 'COUCOU', 'TACCO', 'CRBO'
        ]);
    }

    /**
     * Chefs de meute
     */
    private function getChefsMeute(): array
    {
        return $this->utilisateurRepository->findByDetailPoste([
            'CDBL', 'AKELA', 'BAGHEERA', 'BALOO', 'RASHKA', 'CM', 'ACM', 'CMA', 'CRBL',
            'Chef de meute', 'chef de meute adjoint', 'assistant chef de meute',
            'commissaire de district branche louveteau',
        ]);
    }

    /**
     * Chefs de troupe
     */
    private function getChefsTroupe(): array
    {
        return $this->utilisateurRepository->findByDetailPoste([
            'CDBE', 'CT', 'CTA', 'ACT', 'CRBE', 'chef de troupe', 'chef de troupe adjoint',
            'assistant chef de troupe', 'commissaire de district branche eclaireur',
            'commissaire regional branche eclaireur'
        ]);
    }

    /**
     * Chefs de génération
     */
    private function getChefsGeneration(): array
    {
        return $this->utilisateurRepository->findByDetailPoste([
            'CRBC', 'CDBC', 'safouin', 'safouin adjoint', 'assistant safouin',
            'commissaire regional branche cheminot', 'commissaire de district branche cheminot'
        ]);
    }

    /**
     * Chefs de communauté
     */
    private function getChefsCommunaute(): array
    {
        return $this->utilisateurRepository->findByDetailPoste([
            'CRBR', 'CDBR', 'CC', 'CCA', 'ACC', 'commissaire regional branche route',
            'commissaire de district branche route', 'chef de communauté', 'chef de communauté adjoint',
            'assistant chef de communauté'
        ]);
    }

    /**
     * Oisillons
     */
    private function getOisillons(): array
    {
        return $this->utilisateurRepository->findByBranche((Branche::OISILLON)->value);
    }

    /**
     * Louveteaux
     */
    private function getLouveteaux(): array
    {
        return $this->utilisateurRepository->findByBranche((Branche::LOUVETEAU)->value);
    }

    /**
     * Éclaireurs
     */
    private function getEclaireurs(): array
    {
        return $this->utilisateurRepository->findByBranche((Branche::ECLAIREUR)->value);
    }

    /**
     * Cheminots
     */
    private function getCheminots(): array
    {
        return $this->utilisateurRepository->findByBranche((Branche::CHEMINOT)->value);
    }

    /**
     * Routiers
     */
    private function getRoutiers(): array
    {
        return $this->utilisateurRepository->findByBranche((Branche::ROUTIER)->value);
    }
}
