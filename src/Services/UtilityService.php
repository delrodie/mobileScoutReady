<?php

namespace App\Services;

use App\Enum\ScoutStatut;

class UtilityService
{
    public function calculAge(string $dateNaissance): ?int
    {
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
        $naissance = null;

        foreach ($formats as $format) {
            $naissance = \DateTime::createFromFormat($format, trim($dateNaissance));
            if ($naissance !== false) break;
        }

        if (!$naissance) {
            throw new \InvalidArgumentException("Format de date invalide : '$dateNaissance'.");
        }

        return (new \DateTime())->diff($naissance)->y;
    }

    public function validForm(string $str): string
    {
        return htmlspecialchars(stripcslashes(trim($str)));
    }
}
