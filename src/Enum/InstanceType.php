<?php

namespace App\Enum;

enum InstanceType: string
{
    case NATION = 'NATION';
    case REGION = 'REGION';
    case DISTRICT = 'DISTRICT';
    case GROUPE = 'GROUPE';

    public function label(): string
    {
        return match ($this) {
            self::NATION => 'Nation',
            self::REGION => 'Région',
            self::DISTRICT => 'District',
            self::GROUPE => 'Groupe',
        };
    }
}
