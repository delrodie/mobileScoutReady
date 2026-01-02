<?php

namespace App\Enum;

enum StageFormation: string
{
    // Stage de base Niveau 1
    case AITCHWE = "AITCHWE";
    case LOGODJO = "LOGODJO";

    // Stage de base Niveau 2
    case KAFO = "KAFO";
    case KLADIGNAN = "KLADIGNAN";

    // Stage avancé niveau 1
    case NANDJELET = "NANDJELET";

    // Stage avancé niveau 2
    case GNEKPA = "GNEKPA";
    case STAPPRO = "STAPPRO";
    case COMPAGNON_EMMAUS = "COMPAGNON D'EMMAUS ";

    // Stage avancé niveau 3
    case STAFA = "STAFA";

    // Stage avancé niveau 4
    case STIFF = "STIFF";

    public static function stageBaseNiveau1(): array
    {
        return [self::AITCHWE, self::LOGODJO];
    }

    public static function stageBaseNiveau2(): array
    {
        return [self::KAFO, self::KLADIGNAN];
    }

    public static function stageAvanceNiveau1(): array
    {
        return [self::NANDJELET];
    }

    public static function stageAvanceNiveau2(): array
    {
        return [self::GNEKPA, self::STAPPRO, self::COMPAGNON_EMMAUS];
    }

    public static function stageAvanceNiveau3(): array
    {
        return [self::STAFA];
    }

    public static function stageAvanceNiveau4():array
    {
        return [self::STIFF];
    }
}
