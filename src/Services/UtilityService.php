<?php

namespace App\Services;

use App\Enum\ScoutStatut;

class UtilityService
{
    public function calculAge(string|\DateTimeInterface $dateNaissance): ?int
    {

        if ($dateNaissance instanceof \DateTimeInterface) {
            $naissance = $dateNaissance;
        } else {
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
            $naissance = null;

            foreach ($formats as $format) {
                $naissance = \DateTime::createFromFormat($format, trim($dateNaissance));
                if ($naissance !== false) break;
            }

            if (!$naissance) {
                throw new \InvalidArgumentException("Format de date invalide : '$dateNaissance'.");
            }
        }


        return (new \DateTime())->diff($naissance)->y;
    }


    public function avatar(string|\DateTimeInterface $dateNaissance, string $genre): string
    {
        $age = $this->calculAge($dateNaissance);
        $genre = strtoupper(trim($genre));

        $estMineur = $age < 18;
        $estHomme = $genre === 'HOMME';

        return $estMineur
            ? ($estHomme ? 'Garçon' : 'Fille')
            : ($estHomme ? 'Homme' : 'Femme');
    }



    public function annee(): string
    {
        $anneeEncours = (int) Date('Y');
        $moisEncours = (int) Date('m');

        $debutAnnee = $moisEncours > 9 ? $anneeEncours : $anneeEncours - 1;
        $finAnnee = $moisEncours > 9 ? $anneeEncours + 1 : $anneeEncours;

        return sprintf('%d-%d', $debutAnnee, $finAnnee);
    }

    /**
     * @param $str
     * @return string
     */
    public function validForm($str): string
    {
        return htmlspecialchars(stripslashes(trim($str)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
