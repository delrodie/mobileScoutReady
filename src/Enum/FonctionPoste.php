<?php

namespace App\Enum;

enum FonctionPoste: string
{
    case REGIONAL = 'REGIONAL';
    case DISTRICT = 'DISTRICT';
    case GROUPE = 'GROUPE';
    case UNITE = 'UNITE';

    public function label(): string
    {
        return match($this){
            self::REGIONAL => "Équipe Régionale",
            self::DISTRICT => "Équipe de district",
            self::GROUPE => "Équipe de groupe",
            self::UNITE => "Chef d'unité",
        };
    }
}
