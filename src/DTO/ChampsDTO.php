<?php

namespace App\DTO;

class ChampsDTO
{
    public array $champs;

    public static function listChamps($champs): ?self
    {
        if (empty($champs)){
            return null;
        }

        $dto = new self();

        $dto->champs[] = 0;
        foreach ($champs as $champ){
            $dto->champs[] = [
                'id' => $champ->getId(),
                'titre' => $champ->getTitre(),
                'description' => $champ->getDescription(),
                'media' => "/uploads/champs/{$champ->getMedia()}",
                'urlDetail' => "/champs/{$champ->getId()}",
            ];
        }

        return $dto;
    }
}
