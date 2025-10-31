<?php

namespace App\DTO;

use App\Entity\Scout;
use App\Enum\ScoutStatut;
use App\Repository\FonctionRepository;
use App\Repository\InstanceRepository;

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
        $avatar = match($scout->getSexe()){
            "HOMME" => ScoutStatut::ADULTE ? "avatar_homme.png" : "avatar_garcon.png",
            default => ScoutStatut::ADULTE ? "avatar_femme.png" : "avatar_fille.png"
        };

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
            'avatar' => $avatar,
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

        $instance = $fonctionsScout[0]->getInstance(); dump($instance);

        $dto->profil_instance = [
            'id' => $instance->getId(),
            'slug' => $instance->getSlug(),
            'nom' => $instance->getNom(),
            'type' => $instance->getType()?->value,
            'sigle' => $instance->getSigle(),
            'parentId' => $instance->getInstanceParent()?->getId(),
            'parentNom' => $instance->getInstanceParent()?->getNom(),
        ];

        return $dto;
    }
}
