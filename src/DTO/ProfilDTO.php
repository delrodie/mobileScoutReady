<?php

namespace App\DTO;

use App\Entity\Scout;

class ProfilDTO
{
    public array $profil;
    public array $profil_fonction;
    public array $profil_instance;

    public static function fromScout(array $fonctionsScout): ?self
    {
        if (empty($fonctionsScout)){
            return null;
        }

        $dto = new self();
        $scout = $fonctionsScout[0]->getScout();

        $dto->profil = [
            'id' => $scout->getId(),
            'slug' => $scout->getSlug(),
            'code' => $scout->getCode(),
            'matricule' => $scout->getMatricule(),
            'nom' => $scout->getNom(),
            'prenom' => $scout->getPrenom(),
            'dateNaissance' => $scout->getDateNaissance(),
            'sexe' => $scout->getSexe(),
            'statut' => $scout->getStatut(),
            'email' => $scout->getEmail(),
            'photo' => $scout->getPhoto(),
            'qrCodeToken' => $scout->getQrCodeToken(),
            'qrCodeFile' => $scout->getQrCodeFile(),
            'isParent' => $scout->isPhoneParent(),
            'telephone' => $scout->getTelephone(),
        ];

        $fonction = $fonctionsScout[0];

        $dto->profil_fonction = [
            'id' => $fonction->getId(),
            'poste' => $fonction->getPoste(),
            'detailPoste' => $fonction->getDetailPoste(),
            'branche' => $fonction->getBranche(),
            'annee' => $fonction->getAnnee(),
            'validation' => $fonction->isValidation()
        ];

        $instance = $fonctionsScout[0]->getInstance();

        $dto->profil_instance = [
            'id' => $instance->getId(),
            'slug' => $instance->getSlug(),
            'nom' => $instance->getNom(),
            'type' => $instance->getType()?->value,
            'sigle' => $instance->getSigle(),
            //'parent' => $instance->getInstanceParent(),
        ];

        return $dto;
    }
}
